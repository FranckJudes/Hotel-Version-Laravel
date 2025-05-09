<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CHECKED_IN = 'CHECKED_IN';
    case CHECKED_OUT = 'CHECKED_OUT';
    case CANCELLED = 'CANCELLED';
    case NO_SHOW = 'NO_SHOW';
}
