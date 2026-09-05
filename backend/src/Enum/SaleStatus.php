<?php

namespace App\Enum;

enum SaleStatus: string
{
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Unpaid = 'unpaid';
}
