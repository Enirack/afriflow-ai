<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSaleInput
{
    public ?int $customerId = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['cash', 'mobile_money', 'bank_transfer', 'other'])]
    public string $paymentMethod;

    #[Assert\PositiveOrZero]
    public string $discount = '0';

    /**
     * @var CreateSaleItemInput[]
     */
    #[Assert\Count(min: 1)]
    #[Assert\Valid]
    public array $items = [];
}
