<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\ElevenLabs;

use Easy\Container\Attributes\Inject;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class Client
{
    private const BASE_URL = "https://api.elevenlabs.io/v1";

    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,

        #[Inject('option.elevenlabs.api_key')]
        private ?string $apiKey = null
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     */
    public function sendRequest(
        string $method,
        string $path,
        array $data = [],
        array $headers = []
    ): ResponseInterface {
        $req = $this->requestFactory->createRequest(
            $method,
            self::BASE_URL . $path
        );

        $isMultiPart = false;
        foreach ($data as $key => $value) {
            if ($value instanceof StreamInterface) {
                $isMultiPart = true;
                break;
            }
        }

        if ($isMultiPart) {
            $builder = new MultipartStreamBuilder($this->streamFactory);

            foreach ($data as $key => $value) {
                $builder->addResource($key, $value);
            }

            $multipartStream = $builder->build();
            $boundary = $builder->getBoundary();

            $req = $req
                ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary)
                ->withBody($multipartStream);
        } else {
            $req = $req->withHeader('Content-Type', 'application/json')
                ->withHeader('Accept', 'application/json');

            if ($this->apiKey) {
                $req = $req->withHeader('xi-api-key', $this->apiKey);
            }

            if ($data) {
                $stream = $req->getBody();
                $stream->write(json_encode($data));
                $req = $req->withBody($stream);
            }
        }

        foreach ($headers as $key => $value) {
            $req = $req->withHeader($key, $value);
        }

        return $this->client->sendRequest($req);
    }
}
