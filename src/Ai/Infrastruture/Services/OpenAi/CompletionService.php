<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\OpenAi;

use Ai\Domain\Completion\CompletionServiceInterface;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastruture\Services\CostCalculator;
use Billing\Domain\ValueObjects\Count;
use Generator;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;
use Override;
use RuntimeException;

class CompletionService implements CompletionServiceInterface
{
    private array $models = [
        'gpt-4o',
        'gpt-4-turbo',
        'gpt-4-turbo-preview',
        'gpt-4',
        'gpt-3.5-turbo',
        'gpt-3.5-turbo-instruct'
    ];

    public function __construct(
        private Client $client,
        private Gpt3Tokenizer $tokenizer,
        private CostCalculator $calc
    ) {
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    public function generateCompletion(Model $model, array $params = []): Generator
    {
        try {
            if ($model->value == 'gpt-3.5-turbo-instruct') {
                return $this->generateInstructedCompletion($model, $params);
            }
        } catch (ErrorException $th) {
            throw new ApiException($th->getMessage(), previous: $th);
        }

        try {
            return $this->generateChatCompletion($model, $params);
        } catch (ErrorException $th) {
            throw new ApiException($th->getMessage(), previous: $th);
        }
    }

    /**
     * @return Generator<int,Chunk,null,Count>
     * @throws ErrorException
     * @throws RuntimeException
     */
    private function generateInstructedCompletion(
        Model $model,
        array $params = []
    ): Generator {
        $prompt = $params['prompt'] ?? '';

        $resp = $this->client->completions()->createStreamed([
            'model' => $model->value,
            'prompt' => $prompt,
            'temperature' => (int)($params['temperature'] ?? 1),
        ]);

        $inputTokensCount = $this->tokenizer->count($prompt);
        $outputTokensCount = 0;

        foreach ($resp as $item) {
            $content = $item->choices[0]->text;

            if ($content) {
                $outputTokensCount++;
                yield new Chunk($content);
            }
        }

        $inputCost = $this->calc->calculate(
            $inputTokensCount,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $outputTokensCount,
            $model,
            CostCalculator::OUTPUT
        );

        return new Count($inputCost->value + $outputCost->value);
    }

    /**
     * @return Generator<int,Chunk,null,Count>
     * @throws RuntimeException
     * @throws ErrorException
     */
    private function generateChatCompletion(
        Model $model,
        array $params = []
    ): Generator {
        $prompt = $params['prompt'] ?? '';

        $resp = $this->client->chat()->createStreamed([
            'model' => $model->value,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'temperature' => (int)($params['temperature'] ?? 1),
        ]);

        $inputTokensCount = $this->tokenizer->count($prompt);
        $outputTokensCount = 0;

        foreach ($resp as $item) {
            $content = $item->choices[0]->delta->content;

            if ($content) {
                $outputTokensCount++;
                yield new Chunk($content);
            }
        }

        $inputCost = $this->calc->calculate(
            $inputTokensCount,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $outputTokensCount,
            $model,
            CostCalculator::OUTPUT
        );

        return new Count($inputCost->value + $outputCost->value);
    }
}
