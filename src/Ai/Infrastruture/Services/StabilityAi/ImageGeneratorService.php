<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\StabilityAi;

use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Exceptions\ModelNotSupportedException;
use Ai\Domain\Image\ImageServiceInterface;
use Ai\Domain\Image\GenerateImageResponse;
use Ai\Domain\ValueObjects\Height;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\Width;
use Ai\Infrastruture\Services\CostCalculator;
use Easy\Container\Attributes\Inject;
use Override;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ImageGeneratorService implements ImageServiceInterface
{
    private const BASE_URL = "https://api.stability.ai";

    private array $models = [
        'stable-diffusion-xl-1024-v1-0',
        'stable-diffusion-v1-6',
    ];

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $factory,
        private StreamFactoryInterface $streamFactory,
        private CostCalculator $calc,

        #[Inject('version')]
        private string $version,

        #[Inject('option.stabilityai.api_key')]
        private ?string $apiKey = null
    ) {
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    #[Override]
    public function generateImage(
        Model $model,
        ?Width $width = null,
        ?Height $height = null,
        ?array $params = null
    ): GenerateImageResponse {
        if (!$this->supportsModel($model)) {
            throw new ModelNotSupportedException(
                self::class,
                $model
            );
        }

        if (!$params || !array_key_exists('prompt', $params)) {
            throw new DomainException('Missing parameter: prompt');
        }

        $data = [
            'text_prompts' => [
                [
                    'text' => $params['prompt'],
                    'weight' => 1
                ]
            ]
        ];

        if ($width) {
            $data['width'] = $width->value;
        }

        if ($height) {
            $data['height'] = $height->value;
        }

        foreach (['sampler', 'clip_guidance_preset', 'style'] as $key) {
            if (array_key_exists($key, $params)) {
                $data[$key] = $params[$key];
            }
        }

        if (array_key_exists('negative_prompt', $params)) {
            $data['text_prompts'][] = [
                'text' => $params['negative_prompt'],
                'weight' => -1
            ];
        }

        $stream = $this->streamFactory->createStream(json_encode($data));

        $request = $this->factory
            ->createRequest('POST', self::BASE_URL . '/v1/generation/' . $model->value . '/text-to-image')
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Stability-Client-ID', 'aikeedo')
            ->withHeader('Stability-Client-Version', trim($this->version))
            ->withBody($stream);

        $resp = $this->client->sendRequest($request);
        $body = json_decode($resp->getBody()->getContents());

        if ($resp->getStatusCode() !== 200) {
            throw new ApiException(
                'Failed to generate image: ' . ($body->message ?? '')
            );
        }

        if (!isset($body->artifacts) || !is_array($body->artifacts) || count($body->artifacts) === 0) {
            throw new DomainException('Failed to generate image');
        }

        $artifact = $body->artifacts[0];
        $content = base64_decode($artifact->base64);

        $cost = $this->calc->calculate(1, $model);
        return new GenerateImageResponse(
            imagecreatefromstring($content),
            $cost,
            RequestParams::fromArray($params)
        );
    }
}
