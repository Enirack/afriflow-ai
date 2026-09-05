<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
class SaleItem implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sale:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Sale::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Sale $sale;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sale:read'])]
    private Product $product;

    #[ORM\Column]
    #[Groups(['sale:read'])]
    private int $quantity;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['sale:read'])]
    private string $unitPrice;

    public function __construct(Sale $sale, Product $product, int $quantity, string $unitPrice)
    {
        $this->company = $sale->getCompany();
        $this->sale = $sale;
        $this->product = $product;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getSale(): Sale
    {
        return $this->sale;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    #[Groups(['sale:read'])]
    public function getTotalPrice(): string
    {
        return bcmul((string) $this->quantity, $this->unitPrice, 2);
    }
}
