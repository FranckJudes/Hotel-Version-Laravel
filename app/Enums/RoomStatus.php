<?php

namespace App\Enums;

enum RoomStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case OCCUPIED = 'OCCUPIED';
    case MAINTENANCE = 'MAINTENANCE';
    case CLEANING = 'CLEANING';
}
