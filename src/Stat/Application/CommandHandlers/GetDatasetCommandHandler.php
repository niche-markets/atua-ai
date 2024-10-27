<?php

declare(strict_types=1);

namespace Stat\Application\CommandHandlers;

use Stat\Application\Commands\GetDatasetCommand;
use Stat\Domain\Repositories\StatRepositoryInterface;
use Traversable;

class GetDatasetCommandHandler
{
    public function __construct(
        private StatRepositoryInterface $repo
    ) {
    }

    /**
     * @return Traversable<array{category:string,value:int}>
     */
    public function handle(GetDatasetCommand $cmd): Traversable
    {
        $stats = $this->repo->filterByType($cmd->type);

        if ($cmd->year) {
            $stats = $stats->filterByYear($cmd->year);
        } else if ($cmd->month) {
            $stats = $stats->filterByMonth($cmd->month);
        } else if ($cmd->day) {
            $stats = $stats->filterByDay($cmd->day);
        }

        return $stats->getDataset(
            $cmd->category,
        );
    }
}
