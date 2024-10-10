<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PaymentType extends Enum
{
    const LocalPayment =   1;
    const Paypal =   2;
    const Stripe =   3;
    const UserBalance =   4;
}
