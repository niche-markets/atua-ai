<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

use Ai\Domain\ValueObjects\Model;
use Billing\Domain\ValueObjects\PlanConfig\ChatConfig;
use Billing\Domain\ValueObjects\PlanConfig\ClassifierConfig;
use Billing\Domain\ValueObjects\PlanConfig\CoderConfig;
use Billing\Domain\ValueObjects\PlanConfig\ImagineConfig;
use Billing\Domain\ValueObjects\PlanConfig\TitlerConfig;
use Billing\Domain\ValueObjects\PlanConfig\TranscriberConfig;
use Billing\Domain\ValueObjects\PlanConfig\VoiceIsolatorConfig;
use Billing\Domain\ValueObjects\PlanConfig\VoiceOverConfig;
use Billing\Domain\ValueObjects\PlanConfig\WriterConfig;
use JsonSerializable;
use Override;

class PlanConfig implements JsonSerializable
{
    public readonly WriterConfig $writer;
    public readonly CoderConfig $coder;
    public readonly ImagineConfig $imagine;
    public readonly TranscriberConfig $transcriber;
    public readonly VoiceOverConfig $voiceover;
    public readonly TitlerConfig $titler;
    public readonly ChatConfig $chat;
    public readonly VoiceIsolatorConfig $voiceIsolator;
    public readonly ClassifierConfig $classifier;

    /** @var array<string,bool> */
    public readonly array $models;

    /** @var array<string,bool> */
    public readonly array $tools;

    public function __construct(?array $data = null)
    {
        $data = $data ?? [];

        $this->writer = new WriterConfig(
            $data['writer']['is_enabled'] ?? false,
            new Model($data['writer']['model'] ?? 'gpt-4')
        );

        $this->coder = new CoderConfig(
            $data['coder']['is_enabled'] ?? false,
            new Model($data['coder']['model'] ?? 'gpt-4')
        );

        $this->imagine = new ImagineConfig(
            $data['imagine']['is_enabled'] ?? false
        );

        $this->transcriber = new TranscriberConfig(
            $data['transcriber']['is_enabled'] ?? false
        );

        $this->voiceover = new VoiceOverConfig(
            $data['voiceover']['is_enabled'] ?? false
        );

        $this->titler = new TitlerConfig(
            new Model($data['titler']['model'] ?? 'gpt-4')
        );

        $this->chat = new ChatConfig(
            $data['chat']['is_enabled'] ?? false
        );

        $this->voiceIsolator = new VoiceIsolatorConfig(
            $data['voice_isolator']['is_enabled'] ?? false
        );

        $this->classifier = new ClassifierConfig(
            $data['classifier']['is_enabled'] ?? false
        );

        $models = [
            'dall-e-3' => false,
            'dall-e-2' => false,
            'stable-diffusion-xl-1024-v1-0' => false,
            'stable-diffusion-v1-6' => false,
            'clipdrop' => false,
            'tts-1' => false,
            'eleven_multilingual_v2' => false,
            'eleven_multilingual_v1' => false,
            'eleven_monolingual_v1' => false,
            'google-tts-standard' => false,
            'google-tts-premium' => false,
            'google-tts-studio' => false,
            'azure-tts' => false,
        ];

        foreach ($models as $model => $enabled) {
            $models[$model] = (bool) ($data['models'][$model] ?? false);
        }

        $this->models = $models;

        $tools = [
            'google_search' => false,
            'cohere_web_search' => false,
            'web_scrap' => false,
        ];

        foreach ($tools as $tool => $enabled) {
            $tools[$tool] = (bool) ($data['tools'][$tool] ?? false);
        }

        $this->tools = $tools;
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'writer' => $this->writer,
            'coder' => $this->coder,
            'imagine' => $this->imagine,
            'transcriber' => $this->transcriber,
            'voiceover' => $this->voiceover,
            'chat' => $this->chat,
            'voice_isolator' => $this->voiceIsolator,
            'classifier' => $this->classifier,
            'titler' => $this->titler,
            'models' => $this->models,
            'tools' => $this->tools,
        ];
    }

    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }
}
