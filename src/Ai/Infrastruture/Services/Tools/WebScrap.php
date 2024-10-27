<?php


declare(strict_types=1);

namespace Ai\Infrastruture\Services\Tools;

use Billing\Domain\ValueObjects\Count;
use DOMDocument;
use DOMXPath;
use Easy\Container\Attributes\Inject;
use Override;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class WebScrap implements ToolInterface
{
    public const LOOKUP_KEY = 'web_scrap';

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,

        #[Inject('option.features.tools.web_scrap.is_enabled')]
        private ?bool $isEnabled = null,
    ) {
    }


    #[Override]
    public function isEnabled(): bool
    {
        return (bool) $this->isEnabled;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Retrieves the HTML content of a webpage at the given URL. The 
        tool will return the HTML content of the webpage as a string. It should 
        be used when the user asks for information from a webpage that is not 
        present in the AI model\'s knowledge base. Regardless of the language of 
        the scanned website content, the user\'s prompt must be answered in 
        the original language.';
    }

    #[Override]
    public function getDefinitions(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "url" => [
                    "type" => "string",
                    "description" => "URL of the webpage to browse."
                ],
            ],
            "required" => ["url"]
        ];
    }

    #[Override]
    public function call(array $params = []): CallResponse
    {
        $url = $params['url'];

        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);

        $html = $response->getBody()->getContents();

        return new CallResponse(
            $this->extractContent($html),
            new Count(0)
        );
    }

    private function extractContent(string $html): string
    {
        $max = 25000;

        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        // Extract all text nodes
        $textNodes = $xpath->query("//text()[normalize-space()]");
        $textContent = "";
        $length = 0;
        foreach ($textNodes as $node) {
            $value = trim($node->nodeValue);
            $delta = mb_strlen($value);

            if ($length + $delta > $max) {
                break;
            }

            $textContent .= $value . " ";
            $length += $delta;
        }

        // Extract JSON data from script tags
        $scriptNodes = $xpath->query("//script[@type='application/json']");
        $jsonContent = [];
        foreach ($scriptNodes as $node) {
            $delta = mb_strlen($node->nodeValue);
            if ($length + $delta > $max) {
                break;
            }

            $jsonContent[] = json_decode($node->nodeValue, true);
            $length += $delta;
        }

        // Combine text and JSON data into a meaningful format
        $formattedContent = trim($textContent) . "\n\n";
        if (!empty($jsonContent)) {
            $formattedContent .= json_encode($jsonContent, JSON_PRETTY_PRINT) . "\n";
        }

        return $formattedContent;
    }
}
