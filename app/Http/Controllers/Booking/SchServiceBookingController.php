<?php

namespace App\Http\Controllers\Booking;

use App\Enums\MessageType;
use Exception;
use App\Enums\ServiceStatus;
use Illuminate\Http\Request;
use App\Enums\ServicePaymentStatus;
use App\Http\Controllers\Controller;
use App\Enums\ServiceCancelPaymentStatus;
use App\Models\Booking\SchServiceBooking;
use App\Models\Employee\SchEmployeeService;
use App\Http\Repository\Booking\BookingRepository;
use App\Http\Repository\Coupon\CouponRepository;
use App\Http\Repository\SmsNotification\SmsNotificationRepository;
use App\Http\Repository\UtilityRepository;
use App\Models\Booking\SchServiceBookingInfo;
use App\Models\Customer\CmnCustomer;
use App\Models\Employee\SchEmployee;
use App\Models\Settings\CmnCompany;
use App\Models\User;
use App\Notifications\ServiceBookingNotification;
use App\Notifications\ServiceOrderNotification;
use Carbon\Carbon;
use ErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SchServiceBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function bookingCalendar()
    {
        return view('booking.booking-calendar');
    }

    /**
     * Summary
     * get employee service schedule for schedule table/calendar
     * @sch_employee_id,@cmn_branch_id,@date,@sch_service_booking_id
     * Author: Kaysar
     * Date: 06-dec-2021
     */
    public function getEmployeeSchedule(Request $request)
    {
        try {
            $bookingRepo = new BookingRepository();
            $data = $bookingRepo->getEmployeeBookingSchedule($request->cmn_branch_id, $request->sch_employee_id, $request->cmn_customer_id, $request->date, $request->serviceBookingId);
            return $this->apiResponse($data, 200);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '403', 'data' => $ex], 400);
        }
    }

    /**
     * Summary
     * get booking info by booking id
     * Author: Kaysar
     * Date: 06-dec-2021
     */
    public function getBookingInfoByServiceId(Request $request)
    {
        try {
            $bookingService = SchServiceBooking::join('cmn_customers', 'sch_service_bookings.cmn_customer_id', '=', 'cmn_customers.id')
                ->join('sch_services', 'sch_service_bookings.sch_service_id', '=', 'sch_services.id')
                ->join('cmn_branches', 'sch_service_bookings.cmn_branch_id', '=', 'cmn_branches.id')
                ->join('sch_employees', 'sch_service_bookings.sch_employee_id', '=', 'sch_employees.id')
                ->where('sch_service_bookings.id', $request->sch_service_booking_id)
                ->select(
                    'sch_service_bookings.id',
                    'cmn_branches.name as branch',
                    'sch_service_bookings.cmn_branch_id',
                    'sch_employees.full_name as employee',
                    'sch_service_bookings.sch_employee_id',
                    'cmn_customers.full_name as customer',
                    'sch_service_bookings.cmn_customer_id',
                    'cmn_customers.phone_no',
                    'cmn_customers.email',
                    'sch_service_bookings.date',
                    'sch_service_bookings.sch_service_id',
                    'sch_services.title as service',
                    'sch_services.sch_service_category_id',
                    'sch_service_bookings.start_time',
                    'sch_service_bookings.end_time',
                    'sch_service_bookings.paid_amount',
                    'sch_service_bookings.status',
                    'sch_service_bookings.remarks',
                    'sch_service_bookings.created_at',
                    'sch_service_bookings.cmn_payment_type_id',
                    'sch_service_bookings.remarks',
                    'sch_employees.specialist',
                    'sch_employees.image_url'
                )->first();
            return $this->apiResponse(['status' => '1', 'data' => $bookingService], 200);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '403', 'data' => $ex], 400);
        }
    }



    /**
     * Summary
     * save booking service from admin panel
     * Author: Kaysar
     * Date: 06-dec-2021
     */
    public function saveBooking(Request $request)
    {
        DB::beginTransaction();
        try {
            $request = (object)$request->bookingData;
            $bookingRepo = new BookingRepository();
            $customerInfo = CmnCustomer::where('id',  $request->cmn_customer_id)->select('phone_no', 'user_id')->first();

            //insert service booking
            $serviceList = array();
            $serviceTotalAmount = 0;
            foreach ($request->items as $key => $item) {
                $item = (object)$item;

                //get employee wise service charge
                $serviceCharge = SchEmployeeService::where('sch_employee_id', $item->sch_employee_id)
                    ->where('sch_service_id', $item->sch_service_id)->select('fees')->first();
                if ($serviceCharge == null)
                    throw new ErrorException("This service is not avaiable please try another one.");

                $serviceTime = explode('-', $item->service_time);
                $serviceStartTime = $serviceTime[0];
                $serviceEndTime = $serviceTime[1];

                //check service is booked or not
                if ($bookingRepo->serviceIsAvaiable($item->sch_service_id, $item->sch_employee_id, $item->service_date, $serviceStartTime, $serviceEndTime) > 0 && $request->isForceBooking == 0)
                    return $this->apiResponse(['status' => '-1', 'data' => "The selected service is bocked. Do you want to add another one this time?"], 200);

                //check servicce limitation
                $serviceLimitation = $bookingRepo->IsServiceLimitation($item->service_date,$serviceStartTime, $request->cmn_customer_id, $item->sch_service_id, 1, 1);
                if ($serviceLimitation['allow'] < 1 && $request->isForceBooking == 0)
                    return $this->apiResponse(['status' => '-1', 'data' => $serviceLimitation['message'] . " Do you want to add forchly?"], 200);

                $paymentStatus = ServicePaymentStatus::Unpaid;
                if ($request->paid_amount >= $serviceCharge->fees) {
                    $paymentStatus = ServicePaymentStatus::Paid;
                } else if ($request->paid_amount > 0) {
                    $paymentStatus = ServicePaymentStatus::PartialPaid;
                }
                //total service charge
                $serviceTotalAmount = $serviceTotalAmount + $serviceCharge->fees;

                $serviceList[] = [
                    'id' => null,
                    'cmn_branch_id' => $item->cmn_branch_id,
                    'cmn_customer_id' => $request->cmn_customer_id,
                    'sch_employee_id' => $item->sch_employee_id,
                    'date' => $item->service_date,
                    'start_time' => $serviceStartTime,
                    'end_time' => $serviceEndTime,
                    'sch_service_id' => $item->sch_service_id,
                    'status' => $request->status,
                    'service_amount' => $serviceCharge->fees,
                    'paid_amount' => 0,
                    'payment_status' => $paymentStatus,
                    'cmn_payment_type_id' => $request->cmn_payment_type_id,
                    'canceled_paid_amount' => 0,
                    'cancel_paid_status' => ServiceCancelPaymentStatus::Unpaid,
                    'remarks' => $request->remarks,
                    'created_by' => auth()->id()
                ];
            }

            $payableAmount = $serviceTotalAmount;
            $couponDiscount = 0;
            //get voucher discount
            if (UtilityRepository::emptyOrNullToZero($request->coupon_code) != 0) {
                $couponRepo = new CouponRepository();
                $couponDiscount = $couponRepo->validateAndGetCouponValue($customerInfo->user_id, $request->coupon_code, $serviceTotalAmount);
            }
            if ($couponDiscount > 0) {
                $payableAmount = $payableAmount - $couponDiscount;
            } else {
                $couponDiscount = 0;
            }

            //update service paid status
            $totalServiceAmount = $serviceTotalAmount;
            $dueAmount = $payableAmount - $request->paid_amount ?? 0;
            foreach ($serviceList as $service) {
                $service = (object)$service;
                $paidAmount = $service->service_amount * ($request->paid_amount ?? 0 / $totalServiceAmount);
                $service->paid_amount = $paidAmount;
                if ($dueAmount > 1) {
                    $service->payment_status = ServicePaymentStatus::PartialPaid;
                } else if ($request->paid_amount <= 0) {
                    $service->payment_status = ServicePaymentStatus::Unpaid;
                } else {
                    $service->payment_status = ServicePaymentStatus::Paid;
                }
                $service->status = ServiceStatus::Approved;
            }

            $serviceBookingInfo = SchServiceBookingInfo::create([
                'booking_date' => Carbon::now(),
                'cmn_customer_id' => $request->cmn_customer_id,
                'total_amount' => $serviceTotalAmount,
                'payable_amount' => $payableAmount,
                'paid_amount' => $request->paid_amount,
                'due_amount' => $dueAmount,
                'is_due_paid' => $dueAmount > 0 ? 0 : 1,
                'coupon_code' => $request->coupon_code,
                'coupon_discount' => $couponDiscount,
                'remarks' => $request->remarks,
                'created_by' => auth()->id()
            ]);
            $serviceBookingInfo->serviceBookings()->attach($serviceList);
            DB::commit();

            $bookingData = $bookingRepo->getEmployeeBookingSchedule($request->cmn_branch_id, 0, 0, null, 0, $serviceBookingInfo->id);


            //send notification to user
            if (UtilityRepository::isEmailConfigured()) {
                $user = '';
                if (auth()->check()) {
                    $user = auth()->user();
                } else {
                    $user = User::first();
                    $user->email = $request->email;
                    $user->phone_no = $request->phone_no;
                    $user->full_name = $request->full_name;
                }
                $bookingRepo = new BookingRepository();
                Notification::send($user, new ServiceOrderNotification($bookingRepo->getServiceInvoice($serviceBookingInfo->id)));
            }

            //SMS notification
            SmsNotificationRepository::sendNotification(
                $customerInfo->phone_no,
                MessageType::ServiceStatus,
                [
                    ['key' => '{order_number}', 'value' =>   $serviceBookingInfo->id]
                ]
            );

            return $this->apiResponse(['status' => '1', 'data' => $bookingData,'booking_info_id'=>$serviceBookingInfo->id], 200);
        } catch (ErrorException $ex) {
            DB::rollBack();
            return $this->apiResponse(['status' => '-501', 'data' => $ex->getMessage()], 400);
        } catch (Exception $qx) {
            DB::rollBack();
            return $this->apiResponse(['status' => '501', 'data' => $qx], 400);
        }
    }

    /**
     * Summary
     * update booking service from admin panel
     * Author: Kaysar
     * Date: 11-dec-2021
     */
    public function updateBooking(Request $request)
    {
        try {
            $bookingRepo = new BookingRepository();

            //get employee wise service charge
            $serviceCharge = SchEmployeeService::where('sch_employee_id', $request->sch_employee_id)
                ->where('sch_service_id', $request->sch_service_id)->select('fees')->first();
            if ($serviceCharge == null)
                return $this->apiResponse(['status' => '-1', 'data' => "This service is not avaiable please try another one."], 400);

            $serviceTime = explode('-', $request->service_time);
            $serviceStartTime = $serviceTime[0];
            $serviceEndTime = $serviceTime[1];

            //check service is booked or not
            if ($bookingRepo->serviceIsAvaiable($request->sch_service_id, $request->sch_employee_id, $request->service_date, $serviceStartTime, $serviceEndTime) > 0 && $request->isForceBooking == 0)
                return $this->apiResponse(['status' => '-1', 'data' => "The selected service is bocked. Do you want to add another one this time?"], 200);

            //check servicce limitation
            $serviceLimitation = $bookingRepo->IsServiceLimitation($request->service_date,$serviceStartTime, $request->cmn_customer_id, $request->sch_service_id, 1, 1);
            if ($serviceLimitation['allow'] < 1 && $request->isForceBooking == 0)
                return $this->apiResponse(['status' => '-1', 'data' => $serviceLimitation['message'] . " Do you want to add forchly?"], 200);

            $paymentStatus = ServicePaymentStatus::Unpaid;
            if ($request->paid_amount >= $serviceCharge->fees) {
                $paymentStatus = ServicePaymentStatus::Paid;
            } else if ($request->paid_amount > 0) {
                $paymentStatus = ServicePaymentStatus::PartialPaid;
            }

            $updateData = SchServiceBooking::where('id', $request->id)->update(
                [
                    'cmn_branch_id' => $request->cmn_branch_id,
                    'cmn_customer_id' => $request->cmn_customer_id,
                    'sch_employee_id' => $request->sch_employee_id,
                    'date' => $request->service_date,
                    'start_time' => $serviceStartTime,
                    'end_time' => $serviceEndTime,
                    'sch_service_id' => $request->sch_service_id,
                    'status' => $request->status,
                    'service_amount' => $serviceCharge->fees,
                    'paid_amount' => $request->paid_amount,
                    'payment_status' => $paymentStatus,
                    'cmn_payment_type_id' => $request->cmn_payment_type_id,
                    'canceled_paid_amount' => 0,
                    'cancel_paid_status' => ServiceCancelPaymentStatus::Unpaid,
                    'remarks' => $request->remarks,
                    'updated_by' => auth()->id()
                ]
            );

            $bookingData = $bookingRepo->getEmployeeBookingSchedule($request->cmn_branch_id, $request->sch_employee_id, 0, null, $request->id);

            //send notification to user
            $serviceDate = new Carbon($request->service_date);
            if ($request->email_notify != null && UtilityRepository::isEmailConfigured()) {
                $customer = CmnCustomer::where('id', $request->cmn_customer_id)->select('email', 'user_id')->first();
                if ($customer != null && $customer->user_id != null) {
                    $user = User::where('id', $customer->user_id)->first();
                    $serviceMessage = [
                        'user_name' => $user->name,
                        'message_subject' => 'Booking ' . UtilityRepository::serviceStatus($request->status) . ' Notification',
                        'message_body' => 'Your service request is ' . UtilityRepository::serviceStatus($request->status) . '.',
                        'booking_info' => ' Booking No#' . $request->id . ', Service Date# ' . $serviceDate->format('D, M d, Y') . ' at ' . $serviceStartTime . ' to ' . $serviceEndTime,
                        'message_footer' => 'Thanks you for choosing our service.',
                        'action_url' => url('/client-dashboard')
                    ];
                    Notification::send($user, new ServiceBookingNotification($serviceMessage));
                }
            }

            //SMS notification
            $customerContactInfo = CmnCustomer::where('id', $request->cmn_customer_id)->select('phone_no')->first();
            SmsNotificationRepository::sendNotification(
                $customerContactInfo->phone_no,
                MessageType::ServiceStatus,
                [
                    ['key' => '{booking_number}', 'value' => $request->id],
                    ['key' => '{service_status}', 'value' => UtilityRepository::serviceStatus($request->status)],
                    ['key' => '{service_date}', 'value' => $serviceDate->format('D, M d, Y')],
                    ['key' => '{service_start}', 'value' => $serviceStartTime],
                    ['key' => '{service_end}', 'value' => $serviceEndTime]
                ]
            );

            return $this->apiResponse(['status' => '1', 'data' => $bookingData], 200);
        } catch (ErrorException $ex) {
            return $this->apiResponse(['status' => '-501', 'data' => $ex->getMessage()], 400);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '501', 'data' => $qx], 400);
        }
    }

    /**
     * Summary
     * cancel booking service from admin panel
     * Author: Kaysar
     * Date: 11-dec-2021
     */
    public function cancelBooking(Request $request)
    {
        try {
            $bookingRepo = new BookingRepository();
            return $this->apiResponse(['status' => '1', 'data' => $bookingRepo->ChangeBookingStatusAndReturnBookingData($request->id, ServiceStatus::Cancel, $request->email_notify)], 200);
        } catch (ErrorException $ex) {
            return $this->apiResponse(['status' => '-501', 'data' => $ex->getMessage()], 400);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '501', 'data' => $qx], 400);
        }
    }

    public function doneBooking(Request $request)
    {
        try {
            $bookingRepo = new BookingRepository();
            return $this->apiResponse(['status' => '1', 'data' => $bookingRepo->ChangeBookingStatusAndReturnBookingData($request->id, ServiceStatus::Done, $request->email_notify)], 200);
        } catch (ErrorException $ex) {
            return $this->apiResponse(['status' => '-501', 'data' => $ex->getMessage()], 400);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '501', 'data' => $qx], 400);
        }
    }


    /**
     * Summary
     * delete booking service from admin panel
     * Author: Kaysar
     * Date: 11-dec-2021
     */
    public function deleteBooking(Request $request)
    {
        try {
            SchServiceBooking::where('id', $request->id)->delete();
            return $this->apiResponse(['status' => '1', 'data' => ''], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '501', 'data' => $qx], 400);
        }
    }

    public function getEmployeeByService(Request $request)
    {
        try {

            $bookingRepo = new BookingRepository();
            $rtr = $bookingRepo->getEmployeeByService($request->sch_service_id, $request->cmn_branch_id, [1, 2]);
            return $this->apiResponse(['status' => '1', 'data' => $rtr], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

    public function getCouponAmount(Request $request)
    {
        try {
            $couponRepo = new CouponRepository();
            $customer = CmnCustomer::where('id', $request->cmn_customer_id)->select('user_id')->first();
            if ($customer == null)
                throw new ErrorException(translate("You need to signup as customer to before apply this coupon"));
            $data = $couponRepo->validateAndGetCouponValue($customer->user_id, $request->couponCode, $request->orderAmount);
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (ErrorException $ex) {
            return $this->apiResponse(['status' => '-501', 'data' => $ex->getMessage()], 400);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '501', 'data' => $qx], 400);
        }
    }

    public function DownloadServiceOrder(Request $request)
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-P',
            'default_font' => 'dejavusans',
            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
        ]);
        $mpdf->AddPageByArray([
            'margin-left' => 10,
            'margin-right' => 10,
            'margin-top' => 10,
            'margin-bottom' => 10,
        ]);

        $bookingRepo = new BookingRepository();

        $mpdf->SetTitle('Service Order Invoice');
        $mpdf->WriteHTML(view('reports.service-order-invoice', ['order' => $bookingRepo->getServiceInvoice($request->serviceBookingInfoId),'company_info' => CmnCompany::first()]));
        $mpdf->Output('service_order_invoice_' . now()->format('YmdHis') . '.pdf', 'I');
    }
}
