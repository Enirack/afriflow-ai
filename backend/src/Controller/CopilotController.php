<?php

namespace App\Controller;

use App\Ai\CopilotService;
use App\Dto\CopilotAskInput;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/copilot')]
final class CopilotController extends AbstractController
{
    public function __construct(
        private readonly CopilotService $copilot,
    ) {
    }

    #[Route('/ask', name: 'app_copilot_ask', methods: ['POST'])]
    public function ask(#[MapRequestPayload] CopilotAskInput $input): JsonResponse
    {
        return new JsonResponse(['answer' => $this->copilot->ask($input->question)]);
    }

    #[Route('/analyze', name: 'app_copilot_analyze', methods: ['POST'])]
    public function analyze(): JsonResponse
    {
        return new JsonResponse(['report' => $this->copilot->analyzeActivity()]);
    }
}
