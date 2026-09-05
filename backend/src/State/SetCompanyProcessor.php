<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\CompanyOwnedInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Assigns the authenticated user's company to a newly created resource before persisting it.
 *
 * @template T of CompanyOwnedInterface
 */
final class SetCompanyProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    /**
     * @param T $data
     *
     * @return T
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);
        \assert(method_exists($data, 'setCompany'));

        $data->setCompany($user->getCompany());

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
