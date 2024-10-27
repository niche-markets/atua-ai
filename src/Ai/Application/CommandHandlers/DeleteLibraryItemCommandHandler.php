<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\DeleteLibraryItemCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;

class DeleteLibraryItemCommandHandler
{
    public function __construct(
        private LibraryItemRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws LibraryItemNotFoundException
     */
    public function handle(DeleteLibraryItemCommand $cmd): void
    {
        $item = $cmd->item instanceof AbstractLibraryItemEntity
            ? $cmd->item
            : $this->repo->ofId($cmd->item);

        $this->repo->remove($item);
    }
}
