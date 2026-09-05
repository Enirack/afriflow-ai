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
        $sale->setDiscount($data->discount);

        foreach ($data->items as $itemInput) {
            $product = $this->entityManager->getRepository(Product::class)->find($itemInput->productId);
            if (!$product) {
                throw new NotFoundHttpException(sprintf('Product #%d not found.', $itemInput->productId));
            }

            $unitPrice = $itemInput->unitPrice ?? $product->getUnitPrice();
            $sale->addItem(new SaleItem($sale, $product, $itemInput->quantity, $unitPrice));
            $product->setStockQuantity($product->getStockQuantity() - $itemInput->quantity);
        }

        $this->entityManager->persist($sale);
        $this->entityManager->flush();

        return $sale;
    }
}
