<?php

namespace App\Enums;

enum RoomType: string
{
    case STANDARD = 'standard';
    case DELUXE = 'deluxe';
    case SUITE = 'suite';
    case FAMILY = 'family';
    case PRESIDENTIAL = 'presidential';
}
