<?php

declare(strict_types=1);

namespace Assistant\Application\Commands;

use Assistant\Application\CommandHandlers\CountAssistantsCommandHandler;
use Assistant\Domain\ValueObjects\Status;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CountAssistantsCommandHandler::class)]
class CountAssistantsCommand
{
    public ?Status $status = null;

    /** Search terms/query */
    public ?string $query = null;

    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);
        return $this;
    }
}
