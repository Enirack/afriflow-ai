<?php

namespace App\Ai;

use App\Ai\Tool\BusinessToolInterface;
use App\Entity\Company;
use App\Entity\User;
use App\Service\BusinessStatsService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class CopilotService
{
    private const MAX_TOOL_ITERATIONS = 5;

    /** @var BusinessToolInterface[] */
    private readonly array $tools;

    /**
     * @param iterable<BusinessToolInterface> $tools
     */
    public function __construct(
        private readonly ClaudeClientInterface $claudeClient,
        private readonly BusinessStatsService $stats,
        #[AutowireIterator('app.ai_tool')] iterable $tools,
        private readonly Security $security,
    ) {
        $this->tools = iterator_to_array($tools, false);
    }

    public function ask(string $question): string
    {
        $company = $this->currentCompany();

        $toolDefinitions = array_map(
            static fn (BusinessToolInterface $tool) => $tool->getDefinition(),
            $this->tools,
        );

        $messages = [ClaudeMessage::user($question)];

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; ++$i) {
            $response = $this->claudeClient->send(
                $this->systemPrompt($company),
                $messages,
                $toolDefinitions,
            );

            if ('tool_use' !== $response->stopReason) {
                return $response->text() ?: "Je n'ai pas pu trouver de réponse à votre question.";
            }

            $messages[] = new ClaudeMessage('assistant', $response->content);

            $toolResults = [];
            foreach ($response->toolUses() as $toolUse) {
                $tool = $this->findTool($toolUse['name']);
                $result = $tool
                    ? $tool->execute($toolUse['input'], $company)
                    : ['error' => sprintf('Outil "%s" inconnu.', $toolUse['name'])];

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $toolUse['id'],
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }

            $messages[] = new ClaudeMessage('user', $toolResults);
        }

        return "Je n'ai pas réussi à répondre à votre question, pouvez-vous la reformuler ?";
    }

    public function analyzeActivity(): string
    {
        $company = $this->currentCompany();
        $to = new \DateTimeImmutable('now');
        $from = new \DateTimeImmutable('first day of this month 00:00:00');

        $data = json_encode([
            'periode' => ['debut' => $from->format('Y-m-d'), 'fin' => $to->format('Y-m-d')],
            'resume' => $this->stats->summary($from, $to),
            'produits_les_plus_vendus' => $this->stats->topProducts($from, $to, 3),
            'meilleurs_clients' => $this->stats->topCustomers($from, $to, 3),
            'depenses_par_categorie' => $this->stats->expensesByCategory($from, $to),
            'clients_avec_impaye' => $this->stats->unpaidCustomers(5),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
            Voici les données réelles de l'entreprise "{$company->getName()}" pour la période en
            cours, au format JSON :

            {$data}

            Rédige un rapport d'analyse d'activité court et actionnable, en français, structuré
            exactement avec ces 5 sections (une ligne de titre avec l'emoji, puis 2-3 phrases) :

            📈 Performance
            🏆 Produit phare
            ⚠️ Point d'attention
            💡 Recommandation
            🎯 Objectif suggéré pour le mois prochain

            N'invente aucun chiffre absent des données ci-dessus. Si une section n'est pas
            pertinente faute de données, dis-le en une phrase plutôt que de l'omettre.
            PROMPT;

        $response = $this->claudeClient->send(
            $this->systemPrompt($company),
            [ClaudeMessage::user($prompt)],
        );

        return $response->text() ?: "Impossible de générer le rapport pour le moment.";
    }

    private function findTool(string $name): ?BusinessToolInterface
    {
        foreach ($this->tools as $tool) {
            if ($tool->getName() === $name) {
                return $tool;
            }
        }

        return null;
    }

    private function currentCompany(): Company
    {
        $user = $this->security->getUser();
        \assert($user instanceof User);

        return $user->getCompany();
    }

    private function systemPrompt(Company $company): string
    {
        return <<<PROMPT
            Tu es AfriFlow, l'assistant business de "{$company->getName()}", une PME qui utilise
            l'application AfriFlow AI pour gérer ses ventes, ses clients et ses dépenses.

            Réponds toujours en français, de façon concise et actionnable, en FCFA.
            Utilise les outils à ta disposition pour interroger les données réelles de
            l'entreprise avant de répondre — ne devine jamais un chiffre. Si une question
            porte sur une période sans préciser laquelle, utilise le mois en cours par défaut.
            Si les données ne permettent pas de répondre, dis-le clairement.
            PROMPT;
    }
}
