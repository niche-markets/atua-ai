<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\OpenAi;

use Ai\Domain\Title\GenerateTitleResponse;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Title;
use Ai\Infrastruture\Services\CostCalculator;
use Billing\Domain\ValueObjects\Count;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;
use OpenAI\Client;
use Override;

class TitleGeneratorService implements TitleServiceInterface
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

    #[Override]
    public function generateTitle(Content $content, Model $model): GenerateTitleResponse
    {
        $words = $this->getWords($content);

        if (empty($words)) {
            $title = new Title();
            return new GenerateTitleResponse($title, new Count(0));
        }

        if ($model->value == 'gpt-3.5-turbo-instruct') {
            return $this->generateInstructedCompletion($model, $words);
        }

        return $this->generateChatCompletion($model, $words);
    }

    private function generateInstructedCompletion(
        Model $model,
        string $words
    ): GenerateTitleResponse {
        $resp = $this->client->completions()->create([
            'model' => $model->value,
            'prompt' => 'Your task is to generate a single title for the given content delimited by triple quotes. Identify the language of the content and generate a title that is relevant to the content. The title should be concise and informative. The title should be no more than 64 characters long. Even though the given summary is in list form, the title should not be a list. Generate the title as if it were for a blog post or news article on the topic. Don\'t generate variations of the same title with different tones or styles. """' . $words . '"""',
        ]);

        $inputCost = $this->calc->calculate(
            $resp->usage->promptTokens ?? 0,
            $model,
            CostCalculator::INPUT
        );

        $outpuitCost = $this->calc->calculate(
            $resp->usage->completionTokens ?? 0,
            $model,
            CostCalculator::OUTPUT
        );

        $cost = new Count($inputCost->value + $outpuitCost->value);

        $title = $resp->choices[0]->text ?? 'Untitled';
        $title = explode("\n", trim($title))[0];
        $title = trim($title, ' "');

        return new GenerateTitleResponse(
            new Title($title),
            $cost
        );
    }

    private function generateChatCompletion(
        Model $model,
        string $words
    ): GenerateTitleResponse {
        $resp = $this->client->chat()->create([
            'model' => $model->value,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Your task is to generate a single title for the given content. Identify the language of the content and generate a title that is relevant to the content. The title should be concise and informative. The title should be no more than 64 characters long. Even though the given summary is in list form, the title should not be a list. Generate the title as if it were for a blog post or news article on the topic. Don\'t generate variations of the same title with different tones or styles.',
                ],
                [
                    'role' => 'user',
                    'content' => 'Summarize the text delimited by triple quotes in one sentence by using same language. """' . $words . '"""',
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Title:'
                ]
            ]
        ]);

        $inputCost = $this->calc->calculate(
            $resp->usage->promptTokens ?? 0,
            $model,
            CostCalculator::INPUT
        );

        $outpuitCost = $this->calc->calculate(
            $resp->usage->completionTokens ?? 0,
            $model,
            CostCalculator::OUTPUT
        );

        $cost = new Count($inputCost->value + $outpuitCost->value);

        $title = $resp->choices[0]->message->content ?? 'Untitled';
        $title = explode("\n", trim($title))[0];
        $title = trim($title, ' "');

        return new GenerateTitleResponse(
            new Title($title),
            $cost
        );
    }

    private function getWords(Content $content, $count = 100)
    {
        preg_match("/(?:\w+(?:\W+|$)){0,$count}/", $content->value, $matches);
        return $matches[0];
    }
}
