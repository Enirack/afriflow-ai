<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Dto\CreatePaymentInput;
use App\Enum\PaymentMethod;
use App\State\CreatePaymentProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(input: CreatePaymentInput::class, processor: CreatePaymentProcessor::class),
    ],
    security: "is_granted('ROLE_USER')",
    normalizationContext: ['groups' => ['payment:read']],
)]
class Payment implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['payment:read', 'sale:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Sale::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['payment:read'])]
    private Sale $sale;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['payment:read', 'sale:read'])]
    private string $amount;

    #[ORM\Column(length: 20, enumType: PaymentMethod::class)]
    #[Groups(['payment:read', 'sale:read'])]
    private PaymentMethod $method;

    #[ORM\Column]
    #[Groups(['payment:read', 'sale:read'])]
    private \DateTimeImmutable $paidAt;

    public function __construct(Sale $sale, string $amount, PaymentMethod $method)
    {
        $this->company = $sale->getCompany();
        $this->sale = $sale;
        $this->amount = $amount;
        $this->method = $method;
        $this->paidAt = new \DateTimeImmutable();
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

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getMethod(): PaymentMethod
    {
        return $this->method;
    }

    public function getPaidAt(): \DateTimeImmutable
    {
        return $this->paidAt;
    }
}
