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
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Lock\LockFactory;

final class CreatePaymentProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Payment
    {
        /** @var CreatePaymentInput $data */

        // Lock before reading the sale's balance, so two concurrent payments
        // against the same sale can't both read "not yet paid" before either
        // one's payment is persisted (overpayment race).
        $lock = $this->lockFactory->createLock(sprintf('sale-payment-%d', $data->saleId));
        $lock->acquire(true);

        try {
            $sale = $this->entityManager->getRepository(Sale::class)->find($data->saleId);
            if (!$sale) {
                throw new NotFoundHttpException('Sale not found.');
            }

            if (bccomp($data->amount, $sale->getBalanceDue(), 2) > 0) {
                throw new UnprocessableEntityHttpException(sprintf(
                    'Le montant du paiement (%s) dépasse le solde restant dû (%s).',
                    $data->amount,
                    $sale->getBalanceDue(),
                ));
            }

            $payment = new Payment($sale, $data->amount, PaymentMethod::from($data->method));
            $sale->addPayment($payment);

            $this->entityManager->persist($payment);
            $this->entityManager->flush();

            return $payment;
        } finally {
            $lock->release();
        }
    }
}
