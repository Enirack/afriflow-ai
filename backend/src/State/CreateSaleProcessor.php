<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreateSaleInput;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\Sale;
use App\Entity\SaleItem;
use App\Entity\User;
use App\Enum\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class CreateSaleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Sale
    {
        /** @var CreateSaleInput $data */
        $user = $this->security->getUser();
        \assert($user instanceof User);

        $company = $user->getCompany();

        $customer = null;
        if (null !== $data->customerId) {
            $customer = $this->entityManager->getRepository(Customer::class)->find($data->customerId);
            if (!$customer) {
                throw new NotFoundHttpException('Customer not found.');
            }
        }

        $sale = new Sale($company, $user, PaymentMethod::from($data->paymentMethod), $customer);

        $itemsTotal = '0.00';
        foreach ($data->items as $itemInput) {
            $product = $this->entityManager->getRepository(Product::class)->find($itemInput->productId);
            if (!$product) {
                throw new NotFoundHttpException(sprintf('Product #%d not found.', $itemInput->productId));
            }

            if ($product->getStockQuantity() < $itemInput->quantity) {
                throw new UnprocessableEntityHttpException(sprintf(
                    'Stock insuffisant pour "%s" (disponible : %d, demandé : %d).',
                    $product->getName(),
                    $product->getStockQuantity(),
                    $itemInput->quantity,
                ));
            }

            $unitPrice = $itemInput->unitPrice ?? $product->getUnitPrice();
            $sale->addItem(new SaleItem($sale, $product, $itemInput->quantity, $unitPrice));
            $product->setStockQuantity($product->getStockQuantity() - $itemInput->quantity);
            $itemsTotal = bcadd($itemsTotal, bcmul((string) $itemInput->quantity, $unitPrice, 2), 2);
        }

        if (bccomp($data->discount, $itemsTotal, 2) > 0) {
            throw new UnprocessableEntityHttpException('La remise ne peut pas dépasser le total des articles.');
        }

        $sale->setDiscount($data->discount);

        $this->entityManager->persist($sale);
        $this->entityManager->flush();

        return $sale;
    }
}
