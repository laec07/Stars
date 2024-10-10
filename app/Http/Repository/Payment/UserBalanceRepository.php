<?php

namespace App\Http\Repository\Payment;

use App\Contract\PaymentInterface;
use App\Enums\MessageType;
use App\Enums\PaymentType;
use App\Enums\ServicePaymentStatus;
use App\Enums\ServiceStatus;
use App\Http\Repository\Booking\BookingRepository;
use App\Http\Repository\SmsNotification\SmsNotificationRepository;
use App\Http\Repository\UtilityRepository;
use App\Models\Booking\SchServiceBooking;
use App\Models\Booking\SchServiceBookingInfo;
use App\Models\Customer\CmnCustomer;
use App\Models\Employee\SchEmployee;
use App\Models\Payment\CmnStripeApiConfig;
use App\Models\User;
use App\Notifications\ServiceBookingNotification;
use App\Notifications\ServiceOrderNotification;
use Carbon\Carbon;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use PayPalHttp\HttpException;

class UserBalanceRepository implements PaymentInterface
{
    public function checkout($amount, $paymentType, $refNo)
    {
        try {
            if (!auth()->check())
                throw new ErrorException(translate("You can't make payment by user balance without login try another one"));
                
            $userBalance = auth()->user()->balance();
            if ($userBalance == null)
                throw new ErrorException(translate('You do not have enough balance in your account'));

            $user = User::where('id', auth()->id())->first();
            $rtr = $user->userBalance()->create([
                'balanceable_type' => PaymentType::UserBalance,
                'amount' => -$amount,
                'user_id' => auth()->id(),
                'balance_type' => 0,
                'status' => 1
            ]);
            Session::put('user_balance_order_info', [
                'paymentType' => $paymentType,
                'refNo' => $refNo,
                'serviceAmount' => (-$amount),
                'trnId' => $rtr->id,
                'currency' => '',
                'customerEmail' => ''
            ]);
            return ['status' => 1, 'redirectUrl' => route('user.balance.payment.done')];
        } catch (Exception $ex) {
            return ['status' => 1, 'redirectUrl' => route('user.balance.payment.cancel')];
        }
    }

    //no need to pass response param
    public function updateServicePaymentInfo($response)
    {
        $serviceBoockedInfoId = Session::get("user_balance_order_info")['refNo'];

        $bookedServiceInfo = SchServiceBookingInfo::where('id', $serviceBoockedInfoId)->first();

        $bookedService = SchServiceBooking::where('sch_service_booking_info_id', $serviceBoockedInfoId)->get();

        $totalServiceAmount = $bookedServiceInfo->total_amount;
        foreach ($bookedService as $service) {
            $paidAmount = floatval($service->service_amount) * (floatval(Session::get("user_balance_order_info")['serviceAmount']) / floatval($totalServiceAmount));
            $service->paid_amount = $paidAmount;
            $service->payment_status = ServicePaymentStatus::Paid;
            $service->status = ServiceStatus::Approved;
            $service->update();
        }

        $bookedServiceInfo->payments()->create(
            [
                'payment_type' => PaymentType::UserBalance,
                'payment_amount' => Session::get("user_balance_order_info")['serviceAmount'],
                'payment_fee' => 0,
                'currency_code' => 'user_balance',
                'payee_email_address' => 'user_balance',
                'payee_crd_no' => 'user_balance',
                'payment_create_time' => now(),
                'payment_details' => '',
                'order_id' => Session::get("user_balance_order_info")['trnId']
            ]
        );

        //email confirm notification
        $customer = CmnCustomer::where('id', $bookedServiceInfo->cmn_customer_id)->select('phone_no', 'email', 'full_name')->first();
        if (UtilityRepository::isEmailConfigured()) {
            $user = '';
            if (auth()->check()) {
                $user = auth()->user();
            } else {
                $user = User::first();
                $user->email = $customer->email;
                $user->phone_no = $customer->phone_no;
                $user->full_name = $customer->full_name;
            }
            $bookingRepo = new BookingRepository();
            Notification::send($user, new ServiceOrderNotification($bookingRepo->getServiceInvoice($serviceBoockedInfoId)));
        }

        //SMS notification
        SmsNotificationRepository::sendNotification(
            $customer->phone_no,
            MessageType::ServiceConfirm,
            [
                ['key' => '{order_number}', 'value' =>   $serviceBoockedInfoId]
            ]
        );

        return 1;
    }


    public function updateServiceDuePayment($response)
    {
        $serviceBoockedId = Session::get("user_balance_order_info")['refNo'];
        $bookedService = SchServiceBooking::where('id', $serviceBoockedId)->first();

        $bookedService->paid_amount = floatval(Session::get("user_balance_order_info")['serviceAmount']);
        $bookedService->payment_status = ServicePaymentStatus::Paid;
        $bookedService->status = ServiceStatus::Approved;
        $bookedService->update();


        $bookedService->payments()->create(
            [
                'payment_type' => PaymentType::UserBalance,
                'payment_amount' => Session::get("user_balance_order_info")['serviceAmount'],
                'payment_fee' => 0,
                'currency_code' => 'user_balance',
                'payee_email_address' => 'user_balance',
                'payee_crd_no' => 'user_balance',
                'payment_create_time' => now(),
                'payment_details' => '',
                'order_id' => Session::get("user_balance_order_info")['trnId']
            ]
        );

        //email confirm notification
        $serviceDate = new Carbon($bookedService->service_date);
        $customer = CmnCustomer::where('id', $bookedService->cmn_customer_id)->select('phone_no', 'email', 'full_name')->first();
        if (UtilityRepository::isEmailConfigured()) {
            $user = '';
            if (auth()->check()) {
                $user = auth()->user();
            } else {
                $user = User::first();
                $user->email = $customer->email;
                $user->phone_no = $customer->phone_no;
                $user->full_name = $customer->full_name;
            }
           
            $serviceMessage = [
                'user_name' => $user->name,
                'message_subject' => 'Booking confirm notification',
                'message_body' => 'Your booking is confirm',
                'booking_info' => ' Booking No#' .  $bookedService->id . ', Service Date# ' . $serviceDate->format('D, M d, Y') . ' at ' . $bookedService->start_time . ' to ' .  $bookedService->end_time,
                'message_footer' => 'Thanks you for choosing our service.',
                'action_url' => url('/client-dashboard')
            ];
            Notification::send($user, new ServiceBookingNotification($serviceMessage));
        }

        //SMS notification       
        SmsNotificationRepository::sendNotification(
            $customer->phone_no,
            MessageType::ServiceConfirm,
            [
                ['key' => '{booking_number}', 'value' =>    $bookedService->id],
                ['key' => '{service_status}', 'value' =>    "Confirm"],
                ['key' => '{service_date}', 'value' =>   $serviceDate->format('D, M d, Y')],
                ['key' => '{service_start}', 'value' =>    $bookedService->start_time],
                ['key' => '{service_end}', 'value' =>    $bookedService->end_time]
            ]
        );

        return 1;
    }
}
