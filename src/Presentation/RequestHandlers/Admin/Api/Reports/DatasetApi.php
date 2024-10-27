<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Reports;

use DateTime;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\CountryResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Stat\Application\Commands\GetDatasetCommand;
use Stat\Domain\ValueObjects\DatasetCategory;
use Symfony\Component\Intl\Countries;
use Traversable;

#[Route(path: '/dataset/[usage|signup|country:type]', method: RequestMethod::GET)]
class DatasetApi extends ReportsApi implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $type = $request->getAttribute('type');
        $category = DatasetCategory::DATE;

        if ($type == 'country') {
            $type = 'signup';
            $category = DatasetCategory::COUNTRY;
        }

        $cmd = new GetDatasetCommand($type);
        $cmd->month = new DateTime();
        $cmd->category = $category;

        /** @var Traversable<array{category:string,value:int}> */
        $dataset = $this->dispatcher->dispatch($cmd);

        if ($request->getAttribute('type') == 'country') {
            $data = [];

            foreach ($dataset as $stat) {
                $data[] = [
                    'category' => new CountryResource($stat['category']),
                    'value' => $stat['value'],
                ];
            }
        } else {
            $data = iterator_to_array($dataset);
        }

        return new JsonResponse($data);
    }
}
