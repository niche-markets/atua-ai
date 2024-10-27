<?php

declare(strict_types=1);

namespace Stat\Application\CommandHandlers;

use Stat\Application\Commands\ReadStatCommand;
use Stat\Domain\Repositories\StatRepositoryInterface;

class ReadStatCommandHandler
{
    public function __construct(
        private StatRepositoryInterface $repo
    ) {
    }

    public function handle(ReadStatCommand $cmd): int
    {
        $stats = $this->repo->filterByType($cmd->type);

        if ($cmd->year) {
            $stats = $stats->filterByYear($cmd->year);
        } else if ($cmd->month) {
            $stats = $stats->filterByMonth($cmd->month);
        } else if ($cmd->day) {
            $stats = $stats->filterByDay($cmd->day);
        }

        return $stats->stat();
    }
}
