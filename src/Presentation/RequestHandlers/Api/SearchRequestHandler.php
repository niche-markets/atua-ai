<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api;

use Ai\Application\Commands\ListLibraryItemsCommand;
use Ai\Domain\Entities\DocumentEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\ValueObjects\ItemType;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Api\DocumentResource;
use Presentation\Resources\Api\PresetResource;
use Presentation\Resources\ListResource;
use Presentation\Response\JsonResponse;
use Preset\Application\Commands\ListPresetsCommand;
use Preset\Domain\Entities\PresetEntity;
use Preset\Domain\Exceptions\PresetNotFoundException;
use Preset\Domain\ValueObjects\Status;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/search', method: RequestMethod::GET)]
class SearchRequestHandler extends Api implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     * @throws PresetNotFoundException
     * @throws LibraryItemNotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $workspace = $request->getAttribute(WorkspaceEntity::class);

        $list = new ListResource();

        $params = (object) $request->getQueryParams();
        $query = $params->query ?? null;
        $limit = $params->limit ?? null;

        $this
            ->searchPresets($list, $query, $limit)
            ->searchDocuments($list, $user, $workspace, $query, $limit);

        return new JsonResponse($list);
    }

    /**
     * @throws NoHandlerFoundException
     * @throws PresetNotFoundException
     */
    private function searchPresets(
        ListResource $list,
        ?string $query,
        ?int $limit
    ): self {
        $cmd = new ListPresetsCommand();
        $cmd->status = Status::from(1);

        if ($query) {
            $cmd->query = $query;
        }

        if ($limit) {
            $cmd->setLimit($limit);
        }

        /** @var Traversable<int,PresetEntity> $presets */
        $presets = $this->dispatcher->dispatch($cmd);

        foreach ($presets as $preset) {
            $list->pushData(new PresetResource($preset));
        }

        return $this;
    }

    /**
     * @throws NoHandlerFoundException
     * @throws LibraryItemNotFoundException
     */
    private function searchDocuments(
        ListResource $list,
        UserEntity $user,
        WorkspaceEntity $workspace,
        ?string $query,
        ?int $limit
    ): self {
        $cmd = new ListLibraryItemsCommand();
        $cmd->user = $user;
        $cmd->workspace = $workspace;
        $cmd->type = ItemType::DOCUMENT;

        if ($query) {
            $cmd->query = $query;
        }

        if ($limit) {
            $cmd->setLimit($limit);
        }

        /** @var Traversable<int,DocumentEntity> */
        $documents = $this->dispatcher->dispatch($cmd);
        foreach ($documents as $doc) {
            $list->pushData(new DocumentResource($doc));
        }

        return $this;
    }
}
