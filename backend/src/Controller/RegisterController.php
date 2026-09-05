<?php

namespace App\Controller;

use App\Dto\RegisterInput;
use App\Entity\Company;
use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire(service: 'limiter.register')] private readonly RateLimiterFactory $registerLimiter,
    ) {
    }

    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] RegisterInput $input, Request $request): JsonResponse
    {
        $limit = $this->registerLimiter->create($request->getClientIp())->consume();
        if (!$limit->isAccepted()) {
            return new JsonResponse(
                ['error' => 'Trop de tentatives, réessayez plus tard.'],
                429,
                ['Retry-After' => (string) $limit->getRetryAfter()->getTimestamp()],
            );
        }

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $input->email]);
        if ($existing) {
            return new JsonResponse(['error' => 'Un compte existe déjà avec cet email.'], 409);
        }

        $company = new Company($input->companyName);
        $user = new User($input->email, $input->fullName, $company);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $input->password));

        $this->entityManager->persist($company);
        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Two concurrent registrations with the same email raced past the
            // check above; the unique index is the real guard.
            return new JsonResponse(['error' => 'Un compte existe déjà avec cet email.'], 409);
        }

        return new JsonResponse(['message' => 'Compte créé avec succès.'], 201);
    }
}
