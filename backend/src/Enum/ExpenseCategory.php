<?php

namespace App\Enum;

enum ExpenseCategory: string
{
    case Transport = 'transport';
    case Salary = 'salary';
    case Stock = 'stock';
    case Rent = 'rent';
    case Marketing = 'marketing';
    case Suppliers = 'suppliers';
    case Other = 'other';
}
