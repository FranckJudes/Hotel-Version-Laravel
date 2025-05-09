<?php

namespace App\Enums;

enum RoomType: string
{
    case SINGLE = 'SINGLE';
    case DOUBLE = 'DOUBLE';
    case TWIN = 'TWIN';
    case DELUXE = 'DELUXE';
    case SUITE = 'SUITE';
    case PRESIDENTIAL = 'PRESIDENTIAL';
}
