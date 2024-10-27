<?php

declare(strict_types=1);

namespace Stat\Application\Commands;

use DateTime;
use DateTimeInterface;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Stat\Application\CommandHandlers\ReadStatCommandHandler;
use Stat\Domain\ValueObjects\StatType;

#[Handler(ReadStatCommandHandler::class)]
class ReadStatCommand
{
    public StatType $type;
    public ?DateTimeInterface $year = null;
    public ?DateTimeInterface $month = null;
    public ?DateTimeInterface $day = null;

    public function __construct(string|StatType $type)
    {
        $this->type = $type instanceof StatType ? $type : StatType::from($type);
        $this->day = new DateTime();
    }
}
