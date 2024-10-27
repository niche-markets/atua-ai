<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\OpenAi;

use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Transcription\GenerateTranscriptionResponse;
use Ai\Domain\ValueObjects\Transcription;
use Ai\Domain\Transcription\TranscriptionServiceInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastruture\Services\CostCalculator;
use OpenAI\Client;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Responses\Audio\TranscriptionResponseSegment;
use Override;
use Psr\Http\Message\StreamInterface;

class TranscriptionService implements TranscriptionServiceInterface
{
    private array $models = [
        'whisper-1'
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc
    ) {
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    #[Override]
    public function generateTranscription(
        Model $model,
        StreamInterface $file,
        array $params = [],
    ): GenerateTranscriptionResponse {
        $model = $model ?: $this->models[0];

        try {
            $resp = $this->client->audio()->transcribe([
                'file' => $file,
                'model' => $model->value,
                'response_format' => 'verbose_json',
                // 'timestamp_granularities' => ['segment', 'word'],
            ]);
        } catch (ErrorException $th) {
            throw new ApiException($th->getMessage(), previous: $th);
        }

        $cost = $this->calc->calculate($resp->duration ?? 0, $model);

        $segments = array_map(
            fn (TranscriptionResponseSegment $segment): array => [
                'text' => $segment->text,
                'start' => $segment->start,
                'end' => $segment->end,
            ],
            $resp->segments
        );

        $transcription = new Transcription(
            $resp->text,
            $resp->language,
            $resp->duration,
            $segments,
            [], // API Client does not return words
        );

        return new GenerateTranscriptionResponse(
            $cost,
            $transcription,
        );
    }
}
