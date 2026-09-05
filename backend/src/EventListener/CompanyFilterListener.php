<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::CONTROLLER, priority: 8)]
final class CompanyFilterListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    public function __invoke(ControllerEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $filter = $this->entityManager->getFilters()->enable('company_filter');
        $filter->setParameter('company_id', $user->getCompany()->getId());
    }
}
