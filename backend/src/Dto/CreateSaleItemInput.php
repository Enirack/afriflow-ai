<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSaleItemInput
{
    #[Assert\NotBlank]
    public int $productId;

    #[Assert\Positive]
    public int $quantity = 1;

    public ?string $unitPrice = null;
}
