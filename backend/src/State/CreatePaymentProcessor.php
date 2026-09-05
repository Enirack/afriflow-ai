<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\CreatePaymentInput;
use App\Entity\Payment;
use App\Entity\Sale;
use App\Enum\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CreatePaymentProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Payment
    {
        /** @var CreatePaymentInput $data */
        $sale = $this->entityManager->getRepository(Sale::class)->find($data->saleId);
        if (!$sale) {
            throw new NotFoundHttpException('Sale not found.');
        }

        $payment = new Payment($sale, $data->amount, PaymentMethod::from($data->method));
        $sale->addPayment($payment);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}
