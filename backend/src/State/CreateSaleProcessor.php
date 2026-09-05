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
use Symfony\Component\Lock\LockFactory;

final class CreateSaleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly LockFactory $lockFactory,
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

        // Lock every distinct product involved before reading its stock, so two
        // concurrent sales for the same product can't both pass the stock check
        // before either decrements it (oversell race).
        $productIds = array_unique(array_map(static fn ($item) => $item->productId, $data->items));
        $locks = array_map(
            fn (int $productId) => $this->lockFactory->createLock(sprintf('product-stock-%d', $productId)),
            $productIds,
        );

        foreach ($locks as $lock) {
            $lock->acquire(true);
        }

        try {
            $sale = new Sale($company, $user, PaymentMethod::from($data->paymentMethod), $customer);

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
            }

            // Discount is still '0' here, so the sale's own total-amount logic
            // (rather than a second, hand-rolled summation) is the items subtotal.
            if (bccomp($data->discount, $sale->getTotalAmount(), 2) > 0) {
                throw new UnprocessableEntityHttpException('La remise ne peut pas dépasser le total des articles.');
            }

            $sale->setDiscount($data->discount);

            $this->entityManager->persist($sale);
            $this->entityManager->flush();

            return $sale;
        } finally {
            foreach ($locks as $lock) {
                $lock->release();
            }
        }
    }
}
