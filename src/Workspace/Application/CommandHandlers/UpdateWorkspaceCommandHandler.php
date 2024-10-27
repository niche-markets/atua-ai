<?php

declare(strict_types=1);

namespace Workspace\Application\CommandHandlers;

use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Workspace\Application\Commands\UpdateWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Events\WorkspaceUpdatedEvent;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Exceptions\WorkspaceUserNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class UpdateWorkspaceCommandHandler
{
    public function __construct(
        private WorkspaceRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceUserNotFoundException
     * @throws Exception
     */
    public function handle(UpdateWorkspaceCommand $cmd): WorkspaceEntity
    {
        $ws = $this->repo->ofId($cmd->id);

        if ($cmd->name) {
            $ws->setName($cmd->name);
        }

        if ($cmd->ownerId) {
            $ws->setOwner($cmd->ownerId);
        }

        if ($cmd->address) {
            $ws->setAddress($cmd->address);
        }

        // Dispatch the workspace updated event
        $event = new WorkspaceUpdatedEvent($ws);
        $this->dispatcher->dispatch($event);

        return $ws;
    }
}
