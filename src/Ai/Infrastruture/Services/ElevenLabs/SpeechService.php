<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\ElevenLabs;

use Voice\Domain\Entities\VoiceEntity;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Speech\GenerateSpeechResponse;
use Ai\Domain\Speech\SpeechServiceInterface;
use Voice\Domain\ValueObjects\ExternalId;
use Ai\Domain\ValueObjects\Model;
use Voice\Domain\ValueObjects\SampleUrl;
use Voice\Domain\ValueObjects\UseCase;
use Voice\Domain\ValueObjects\Name;
use Voice\Domain\ValueObjects\Tone;
use Ai\Infrastruture\Services\CostCalculator;
use Easy\Container\Attributes\Inject;
use Override;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Traversable;
use Voice\Domain\ValueObjects\Accent;
use Voice\Domain\ValueObjects\Age;
use Voice\Domain\ValueObjects\Gender;
use Voice\Domain\ValueObjects\LanguageCode;
use Voice\Domain\ValueObjects\Provider;

class SpeechService implements SpeechServiceInterface
{
    private const BASE_URL = "https://api.elevenlabs.io/v1";

    private array $models = [
        'eleven_multilingual_v2',
        'eleven_multilingual_v1',
        'eleven_monolingual_v1'
    ];

    private $languages = [
        'eleven_multilingual_v2' => [
            'en-US',
            'en-UK',
            'en-AU',
            'en-CA',
            'ja-JP',
            'zh-CN',
            'de-DE',
            'hi-IN',
            'fr-FR',
            'fr-CA',
            'ko-KR',
            'pt-BR',
            'pt-PT',
            'it-IT',
            'es-ES',
            'es-MX',
            'id-ID',
            'nl-NL',
            'tr-TR',
            'fil-PH',
            'pl-PL',
            'sv-SE',
            'bg-BG',
            'ro-RO',
            'ar-SA',
            'ar-AE',
            'cs-CZ',
            'el-GR',
            'fi-FI',
            'hr-HR',
            'ms-MY',
            'sk-SK',
            'da-DK',
            'ta-IN',
            'uk-UA'
        ],
        'eleven_multilingual_v1' => [
            'en-US',
            'en-UK',
            'en-AU',
            'en-CA',
            'de-DE',
            'pl-PL',
            'es-ES',
            'es-MX',
            'it-IT',
            'fr-FR',
            'fr-CA',
            'pt-BR',
            'pt-PT',
            'hi-IN',
        ],
        'eleven_monolingual_v1' => [
            'en_US'
        ]
    ];

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private CostCalculator $calc,

        #[Inject('option.elevenlabs.api_key')]
        private ?string $apiKey = null
    ) {
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    #[Override]
    public function getVoiceList(): Traversable
    {
        if (!$this->apiKey) {
            yield from [];
            return;
        }

        $resp = $this->sendRequest('GET', '/voices');
        $content = json_decode($resp->getBody()->getContents());

        foreach ($content->voices as $voice) {
            if (!isset($voice->preview_url) || !$voice->preview_url) {
                continue;
            }

            $model = new Model($voice->high_quality_base_model_ids[0] ?? $this->models[0]);

            if (!in_array($model->value, $this->models)) {
                continue;
            }

            $entity = new VoiceEntity(
                new Provider('elevenlabs'),
                $model,
                new Name($voice->name),
                new ExternalId($voice->voice_id),
                new SampleUrl($voice->preview_url)
            );

            if (isset($voice->labels->gender)) {
                $entity->setGender(Gender::tryFrom($voice->labels->gender));
            }

            if (isset($voice->labels->accent)) {
                $entity->setAccent(Accent::tryFrom($voice->labels->accent));
            }

            if (isset($voice->labels->age)) {
                match ($voice->labels->age) {
                    'young' => $entity->setAge(Age::YOUNG),
                    'middle aged' => $entity->setAge(Age::MIDDLE_AGED),
                    'old' => $entity->setAge(Age::OLD),
                    default => null
                };
            }

            $desc = $voice->labels->description ?? $voice->labels->descriptive ?? null;
            if ($desc) {
                $tone = Tone::create($desc);

                if ($tone) {
                    $entity->setTones($tone);
                }
            }

            $useCase = $voice->labels->{"use case"} ?? $voice->labels->use_case ?? $voice->labels->usecase  ?? null;
            if ($useCase) {
                $useCase = UseCase::create($useCase);

                if ($useCase) {
                    $entity->setUseCases($useCase);
                }
            }

            if (isset($this->languages[$model->value])) {
                $langs = [];

                foreach ($this->languages[$model->value] as $code) {
                    $lang = LanguageCode::create($code);

                    if ($lang) {
                        $langs[] = $lang;
                    }
                }

                $entity->setSupportedLanguages(...$langs);
            }

            yield $entity;
        }
    }

    #[Override]
    public function generateSpeech(
        VoiceEntity $voice,
        array $params = []
    ): GenerateSpeechResponse {
        if (!$params || !array_key_exists('prompt', $params)) {
            throw new DomainException('Missing parameter: prompt');
        }

        $resp = $this->sendRequest(
            'POST',
            '/text-to-speech/' . $voice->getExternalId()->value,
            [
                'text' => $params['prompt'],
                'model_id' => $voice->getModel()->value
            ]
        );

        $chars = mb_strlen($params['prompt']);
        $cost = $this->calc->calculate($chars, $voice->getModel());

        return new GenerateSpeechResponse(
            $resp->getBody(),
            $cost
        );
    }

    private function sendRequest(
        string $method,
        string $path,
        array $data = [],
        array $headers = []
    ): ResponseInterface {
        $req = $this->requestFactory->createRequest(
            $method,
            self::BASE_URL . $path
        )
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        if ($this->apiKey) {
            $req = $req->withHeader('xi-api-key', $this->apiKey);
        }

        if ($data) {
            $stream = $req->getBody();
            $stream->write(json_encode($data));
            $req = $req->withBody($stream);
        }

        foreach ($headers as $key => $value) {
            $req = $req->withHeader($key, $value);
        }

        return $this->client->sendRequest($req);
    }
}
