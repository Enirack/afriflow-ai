<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\ExpenseCategory;
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
    normalizationContext: ['groups' => ['expense:read']],
    denormalizationContext: ['groups' => ['expense:write']],
)]
#[ApiFilter(DateFilter::class, properties: ['expenseDate'])]
class Expense implements CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['expense:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    #[ORM\Column(length: 20, enumType: ExpenseCategory::class)]
    #[Groups(['expense:read', 'expense:write'])]
    private ExpenseCategory $category;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    #[Assert\Positive]
    #[Groups(['expense:read', 'expense:write'])]
    private string $amount;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['expense:read', 'expense:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['expense:read', 'expense:write'])]
    private \DateTimeImmutable $expenseDate;

    #[ORM\Column]
    #[Groups(['expense:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct(ExpenseCategory $category, string $amount, \DateTimeImmutable $expenseDate)
    {
        $this->category = $category;
        $this->amount = $amount;
        $this->expenseDate = $expenseDate;
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

    public function getCategory(): ExpenseCategory
    {
        return $this->category;
    }

    public function setCategory(ExpenseCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getExpenseDate(): \DateTimeImmutable
    {
        return $this->expenseDate;
    }

    public function setExpenseDate(\DateTimeImmutable $expenseDate): static
    {
        $this->expenseDate = $expenseDate;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
