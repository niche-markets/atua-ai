<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\OpenAi;

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
use OpenAI\Client;
use Override;
use Throwable;

class ImageService implements ImageServiceInterface
{
    private array $models = [
        'dall-e-3',
        'dall-e-2'
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
            'prompt' => $params['prompt'],
            'model' => $model->value,
            'response_format' => 'b64_json',
        ];

        if ($width && $height) {
            $data['size'] = $width->value . 'x' . $height->value;
        }

        if (array_key_exists('quality', $params)) {
            $data['quality'] = $params['quality'];
        }

        if (array_key_exists('style', $params)) {
            $data['style'] = $params['style'];
        }

        try {
            $resp = $this->client->images()->create($data);
        } catch (Throwable $th) {
            throw new ApiException(
                $th->getMessage(),
                $th->getCode(),
                $th
            );
        }

        if (!isset($resp->data) || !is_array($resp->data) || count($resp->data) === 0) {
            throw new DomainException('Failed to generate image');
        }

        $content = base64_decode($resp->data[0]->b64_json);

        $flags = isset($data['quality']) && $data['quality'] == 'hd'
            ? CostCalculator::QUALITY_HD
            : CostCalculator::QUALITY_SD;

        if (isset($data['size'])) {
            match ($data['size']) {
                '256x256' => $flags |= CostCalculator::SIZE_256x256,
                '512x512' => $flags |= CostCalculator::SIZE_512x512,
                '1024x1024' => $flags |= CostCalculator::SIZE_1024x1024,
                '1024x1792' => $flags |= CostCalculator::SIZE_1024x1792,
                '1792x1024' => $flags |= CostCalculator::SIZE_1792x1024,
                default => null
            };
        }

        $cost = $this->calc->calculate(
            1,
            $model,
            $flags
        );

        return new GenerateImageResponse(
            imagecreatefromstring($content),
            $cost,
            RequestParams::fromArray($params)
        );
    }
}
