<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\Anthropic;

use Ai\Domain\Completion\CodeCompletionServiceInterface;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastruture\Services\CostCalculator;
use Billing\Domain\ValueObjects\Count;
use Generator;
use Override;

class CodeCompletionService implements CodeCompletionServiceInterface
{
    private array $models = [
        'claude-3-5-sonnet-20240620',
        'claude-3-opus-20240229',
        'claude-3-sonnet-20240229',
        'claude-3-haiku-20240307'
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc
    ) {
    }

    #[Override]
    public function generateCodeCompletion(
        Model $model,
        string $prompt,
        string $language,
        array $params = []
    ): Generator {
        $prompt = $params['prompt'] ?? '';

        $body = [
            'model' => $model->value,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'max_tokens' => 4096,
            'stream' => true,
            'system' => "You're $language programming language expert."
        ];

        if (isset($params['temperature'])) {
            $body['temperature'] = (float)$params['temperature'] / 2;
        }

        $resp = $this->client->sendRequest('POST', '/v1/messages', $body);
        $stream = new StreamResponse($resp);

        $inputTokensCount = 0;
        $outputTokensCount = 0;

        foreach ($stream as $data) {
            $type = $data->type ?? null;

            if ($type === 'error') {
                throw new ApiException($data->error->message);
            }

            if ($type == 'message_start') {
                $inputTokensCount += $data->message->usage->input_tokens ?? 0;
                $outputTokensCount += $data->message->usage->output_tokens ?? 0;

                continue;
            }

            if ($type == 'content_block_delta') {
                $content = $data->delta->text ?? null;

                if ($content) {
                    yield new Chunk($content);
                }

                continue;
            }

            if ($type == 'message_delta') {
                $inputTokensCount += $data->usage->input_tokens ?? 0;
                $outputTokensCount += $data->usage->output_tokens ?? 0;

                continue;
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

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }
}
