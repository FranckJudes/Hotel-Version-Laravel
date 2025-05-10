<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case ORANGE_MONEY = 'orange_money';
    case MTN_MOBILE_MONEY = 'mtn_mobile_money';
    case CREDIT_CARD = 'credit_card';
    case CASH = 'cash';
}
