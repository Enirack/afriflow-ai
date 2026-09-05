<?php

namespace App\Command;

use App\Entity\Company;
use App\Entity\Customer;
use App\Entity\Expense;
use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\Sale;
use App\Entity\SaleItem;
use App\Entity\User;
use App\Enum\ExpenseCategory;
use App\Enum\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-demo',
    description: 'Seeds a demo company (Boutique Awa) with products, customers, sales and expenses.',
)]
final class SeedDemoDataCommand extends Command
{
    private const DEMO_EMAIL = 'demo@afriflow.ai';
    private const DEMO_PASSWORD = 'demo1234';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::DEMO_EMAIL]);
        if ($existing) {
            $io->error(sprintf(
                'A demo account already exists for "%s". Drop and re-migrate the database first if you want a fresh seed.',
                self::DEMO_EMAIL,
            ));

            return Command::FAILURE;
        }

        $company = new Company('Boutique Awa');
        $company->setCurrency('XOF');

        $user = new User(self::DEMO_EMAIL, 'Awa Diallo', $company);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD));

        $this->entityManager->persist($company);
        $this->entityManager->persist($user);

        $products = $this->seedProducts($company);
        $customers = $this->seedCustomers($company);
        $this->seedExpenses($company);
        $this->seedSales($company, $user, $products, $customers);

        $this->entityManager->flush();

        $io->success('Demo data seeded.');
        $io->table(['Email', 'Password', 'Company'], [[self::DEMO_EMAIL, self::DEMO_PASSWORD, $company->getName()]]);

        return Command::SUCCESS;
    }

    /**
     * @return Product[]
     */
    private function seedProducts(Company $company): array
    {
        $definitions = [
            ['T-shirt', 'TS-001', '7500', 50],
            ['Chaussures homme', 'CH-014', '18000', 20],
            ['Sac à main', 'SA-007', '25000', 12],
            ['Casquette', 'CA-002', '4000', 40],
            ['Ceinture cuir', 'CE-003', '9000', 25],
        ];

        $products = [];
        foreach ($definitions as [$name, $sku, $price, $stock]) {
            $product = new Product($name, $price);
            $product->setCompany($company);
            $product->setSku($sku);
            $product->setStockQuantity($stock);
            $this->entityManager->persist($product);
            $products[] = $product;
        }

        return $products;
    }

    /**
     * @return Customer[]
     */
    private function seedCustomers(Company $company): array
    {
        $definitions = [
            ['Mamadou Ba', '771234567'],
            ['Fatou Sy', '781234567'],
            ['Ibrahima Ndiaye', '761234567'],
            ['Aminata Diop', '701234567'],
        ];

        $customers = [];
        foreach ($definitions as [$name, $phone]) {
            $customer = new Customer($name);
            $customer->setCompany($company);
            $customer->setPhone($phone);
            $this->entityManager->persist($customer);
            $customers[] = $customer;
        }

        return $customers;
    }

    private function seedExpenses(Company $company): void
    {
        $now = new \DateTimeImmutable('now');
        $definitions = [
            [ExpenseCategory::Transport, '5000', 3, 'Livraison stock'],
            [ExpenseCategory::Rent, '80000', 1, 'Loyer boutique'],
            [ExpenseCategory::Marketing, '15000', 5, 'Publicité Facebook'],
            [ExpenseCategory::Stock, '120000', 10, 'Réapprovisionnement'],
            [ExpenseCategory::Salary, '60000', 2, 'Salaire employé'],
            [ExpenseCategory::Suppliers, '30000', 7, 'Fournisseur textile'],
        ];

        foreach ($definitions as [$category, $amount, $daysAgo, $description]) {
            $expense = new Expense($category, $amount, $now->modify(sprintf('-%d days', $daysAgo)));
            $expense->setCompany($company);
            $expense->setDescription($description);
            $this->entityManager->persist($expense);
        }
    }

    /**
     * @param Product[]  $products
     * @param Customer[] $customers
     */
    private function seedSales(Company $company, User $seller, array $products, array $customers): void
    {
        $methods = [PaymentMethod::Cash, PaymentMethod::MobileMoney, PaymentMethod::BankTransfer];

        // [daysAgo, customerIndex|null, [[productIndex, quantity], ...], paidRatio]
        $definitions = [
            [0, 0, [[0, 2]], 1.0],
            [0, null, [[3, 1]], 1.0],
            [1, 1, [[1, 1], [3, 2]], 0.5],
            [2, 2, [[2, 1]], 0.0],
            [3, 0, [[0, 1], [4, 1]], 1.0],
            [4, null, [[3, 3]], 1.0],
            [5, 3, [[1, 1]], 1.0],
            [6, 1, [[0, 3]], 0.6],
            [8, null, [[2, 1], [3, 1]], 1.0],
            [10, 2, [[4, 2]], 0.0],
            [12, 0, [[1, 1]], 1.0],
            [15, null, [[0, 2], [3, 1]], 1.0],
            [18, 1, [[2, 1]], 1.0],
            [20, 3, [[0, 1]], 0.4],
            [25, null, [[1, 1], [4, 1]], 1.0],
        ];

        foreach ($definitions as $i => [$daysAgo, $customerIndex, $items, $paidRatio]) {
            $customer = null !== $customerIndex ? $customers[$customerIndex] : null;
            $method = $methods[$i % \count($methods)];

            $sale = new Sale($company, $seller, $method, $customer);

            foreach ($items as [$productIndex, $quantity]) {
                $product = $products[$productIndex];
                $sale->addItem(new SaleItem($sale, $product, $quantity, $product->getUnitPrice()));
            }

            $this->backdateSale($sale, $daysAgo);
            $this->entityManager->persist($sale);

            $total = $sale->getTotalAmount();
            if ($paidRatio > 0) {
                $amount = 1.0 === $paidRatio ? $total : bcmul($total, (string) $paidRatio, 2);
                $payment = new Payment($sale, $amount, $method);
                $this->backdatePayment($payment, $daysAgo);
                $sale->addPayment($payment);
                $this->entityManager->persist($payment);
            }
        }
    }

    /**
     * Sale::saleDate has no public setter (a real sale is always "now"); the
     * seed data backdates it via reflection purely for a realistic-looking
     * demo dashboard/chart, without adding a setter the real domain has no use for.
     */
    private function backdateSale(Sale $sale, int $daysAgo): void
    {
        $date = (new \DateTimeImmutable())->modify(sprintf('-%d days', $daysAgo));
        $this->setPrivateDate($sale, 'saleDate', $date);
        $this->setPrivateDate($sale, 'createdAt', $date);
    }

    private function backdatePayment(Payment $payment, int $daysAgo): void
    {
        $date = (new \DateTimeImmutable())->modify(sprintf('-%d days', $daysAgo));
        $this->setPrivateDate($payment, 'paidAt', $date);
    }

    private function setPrivateDate(object $object, string $property, \DateTimeImmutable $value): void
    {
        $ref = new \ReflectionProperty($object, $property);
        $ref->setValue($object, $value);
    }
}
