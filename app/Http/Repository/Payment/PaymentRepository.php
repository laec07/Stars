<?php

namespace App\Http\Repository\Payment;

use App\Enums\PaymentType;
use App\Models\Payment\CmnPaymentType;

class PaymentRepository
{

    public function makePayment($paymentType, $amount, $paymentFor, $refNo)
    {
        if ($paymentType == PaymentType::Paypal) {
            //paypal payment
            $paypal = new PaypalRepository();
            $paymentGateway = new PaymentGatewayRepository($paypal);
            return $paymentGateway->checkout($amount, $paymentFor, $refNo);
        } else if ($paymentType == PaymentType::Stripe) {
            //stripe payment
            $stripe = new StripeRepository();
            $paymentGateway = new PaymentGatewayRepository($stripe);
            return $paymentGateway->checkout($amount, $paymentFor, $refNo);
        } else if ($paymentType == PaymentType::UserBalance) {
            //user balance 
            $paymentGateway = new PaymentGatewayRepository(new UserBalanceRepository());
            return $paymentGateway->checkout($amount, $paymentFor, $refNo);
        } else {
            //local payment
            return "localPayment";
        }
    }

    public function getPaymentType()
    {
        return CmnPaymentType::where('status', 1)->select('id', 'name')->get();
    }

    public function getPaymentTypeForBookingCalenderDropdown()
    {
        return CmnPaymentType::where('status', 1)->where('type','!=',PaymentType::UserBalance)->select('id', 'name')->get();
    }

    public function getPaymentMethod()
    {
        return CmnPaymentType::where('status', 1)->select(
            'id',
            'name',
            'type'
        )->get();
    }
}
