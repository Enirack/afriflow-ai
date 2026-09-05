<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreatePaymentInput
{
    #[Assert\NotBlank]
    public int $saleId;

    #[Assert\Positive]
    public string $amount;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['cash', 'mobile_money', 'bank_transfer', 'other'])]
    public string $method;
}
