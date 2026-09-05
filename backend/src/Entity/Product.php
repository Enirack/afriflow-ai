<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\SetCompanyProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: SetCompanyProcessor::class),
        new Patch(),
        new Delete(),
    ],
    security: "is_granted('ROLE_USER')",
    normalizationContext: ['groups' => ['product:read']],
    denormalizationContext: ['groups' => ['product:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'sku' => 'exact'])]
class Product implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['product:read', 'product:write'])]
    private string $name;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['product:read', 'product:write'])]
    private ?string $sku = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Assert\PositiveOrZero]
    #[Groups(['product:read', 'product:write'])]
    private string $unitPrice;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['product:read', 'product:write'])]
    private int $stockQuantity = 0;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $name, string $unitPrice)
    {
        $this->name = $name;
        $this->unitPrice = $unitPrice;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): static
    {
        $this->stockQuantity = $stockQuantity;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
