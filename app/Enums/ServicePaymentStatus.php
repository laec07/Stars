<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ServicePaymentStatus extends Enum
{
    const Paid =   1;
    const Unpaid =   2;
    const PartialPaid=3;
}
