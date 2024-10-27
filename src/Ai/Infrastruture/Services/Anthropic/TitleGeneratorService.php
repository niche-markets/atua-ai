<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\Anthropic;

use Ai\Domain\Title\GenerateTitleResponse;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Title;
use Ai\Infrastruture\Services\CostCalculator;
use Billing\Domain\ValueObjects\Count;
use Override;

class TitleGeneratorService implements TitleServiceInterface
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
    public function generateTitle(
        Content $content,
        Model $model
    ): GenerateTitleResponse {
        $words = $this->getWords($content);

        if (empty($words)) {
            $title = new Title();
            return new GenerateTitleResponse($title, new Count(0));
        }

        $body = [
            'model' => $model->value,
            'messages' => [
                [
                    'role' => 'user',
                    'content' =>  $words
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Title:'
                ]
            ],
            'system' => 'Your task is to generate a single title for the given content. Identify the language of the content and generate a title that is relevant to the content. The title should be concise and informative. The title should be no more than 64 characters long. Even though the given summary is in list form, the title should not be a list. Generate the title as if it were for a blog post or news article on the topic. Don\'t generate variations of the same title with different tones or styles.',
            'max_tokens' => 64,
        ];

        $resp = $this->client->sendRequest('POST', '/v1/messages', $body);
        $data = json_decode($resp->getBody()->getContents());

        $inputCost = $this->calc->calculate(
            $data->usage->input_tokens ?? 0,
            $model,
            CostCalculator::INPUT
        );

        $outputCost = $this->calc->calculate(
            $data->usage->output_tokens ?? 0,
            $model,
            CostCalculator::OUTPUT
        );

        $cost = new Count($inputCost->value + $outputCost->value);

        $title = $data->content[0]->text ?? 'Untitled';
        $title = explode("\n", trim($title))[0];
        $title = trim($title, ' "');

        return new GenerateTitleResponse(
            new Title($title),
            $cost
        );
    }

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    private function getWords(Content $content, $count = 100)
    {
        preg_match("/(?:\w+(?:\W+|$)){0,$count}/", $content->value, $matches);
        return $matches[0] ?: mb_substr($content->value, 0, $count * 4);
    }
}
