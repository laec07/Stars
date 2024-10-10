<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class ServiceStatus extends Enum
{
    const Pending =   0;
    const Processing =   1;
    const Approved = 2;
    const Cancel = 3;
    const Done = 4;
}
