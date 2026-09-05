<?php

namespace App\Controller;

use App\Dto\RegisterInput;
use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] RegisterInput $input): JsonResponse
    {
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
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Compte créé avec succès.'], 201);
    }
}
