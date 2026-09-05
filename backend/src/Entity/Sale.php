<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Dto\CreateSaleInput;
use App\Enum\PaymentMethod;
use App\Enum\SaleStatus;
use App\State\CreateSaleProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(input: CreateSaleInput::class, processor: CreateSaleProcessor::class),
    ],
    security: "is_granted('ROLE_USER')",
    normalizationContext: ['groups' => ['sale:read']],
)]
class Sale implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sale:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['sale:read'])]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sale:read'])]
    private User $seller;

    #[ORM\Column]
    #[Groups(['sale:read'])]
    private \DateTimeImmutable $saleDate;

    #[ORM\Column(length: 20, enumType: PaymentMethod::class)]
    #[Groups(['sale:read'])]
    private PaymentMethod $paymentMethod;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Groups(['sale:read'])]
    private string $discount = '0';

    #[ORM\Column(length: 20, enumType: SaleStatus::class)]
    #[Groups(['sale:read'])]
    private SaleStatus $status = SaleStatus::Unpaid;

    #[ORM\Column]
    #[Groups(['sale:read'])]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, SaleItem> */
    #[ORM\OneToMany(targetEntity: SaleItem::class, mappedBy: 'sale', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['sale:read'])]
    private Collection $items;

    /** @var Collection<int, Payment> */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'sale', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['sale:read'])]
    private Collection $payments;

    public function __construct(Company $company, User $seller, PaymentMethod $paymentMethod, ?Customer $customer = null)
    {
        $this->company = $company;
        $this->seller = $seller;
        $this->paymentMethod = $paymentMethod;
        $this->customer = $customer;
        $this->saleDate = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function getSeller(): User
    {
        return $this->seller;
    }

    public function getSaleDate(): \DateTimeImmutable
    {
        return $this->saleDate;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function getDiscount(): string
    {
        return $this->discount;
    }

    public function setDiscount(string $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getStatus(): SaleStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, SaleItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(SaleItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }

        return $this;
    }

    /** @return Collection<int, Payment> */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
        }

        $this->refreshStatus();

        return $this;
    }

    #[Groups(['sale:read'])]
    public function getTotalAmount(): string
    {
        $total = '0.00';
        foreach ($this->items as $item) {
            $total = bcadd($total, $item->getTotalPrice(), 2);
        }

        $total = bcsub($total, $this->discount, 2);

        // Defense in depth: the discount is validated against the items total
        // at creation time, but never let a negative total reach the API or
        // the stats aggregates that sum it.
        return bccomp($total, '0.00', 2) < 0 ? '0.00' : $total;
    }

    #[Groups(['sale:read'])]
    public function getAmountPaid(): string
    {
        $paid = '0.00';
        foreach ($this->payments as $payment) {
            $paid = bcadd($paid, $payment->getAmount(), 2);
        }

        return $paid;
    }

    #[Groups(['sale:read'])]
    public function getBalanceDue(): string
    {
        return bcsub($this->getTotalAmount(), $this->getAmountPaid(), 2);
    }

    public function refreshStatus(): static
    {
        $balance = $this->getBalanceDue();

        $this->status = match (true) {
            bccomp($balance, '0.00', 2) <= 0 => SaleStatus::Paid,
            bccomp($this->getAmountPaid(), '0.00', 2) > 0 => SaleStatus::PartiallyPaid,
            default => SaleStatus::Unpaid,
        };

        return $this;
    }
}
