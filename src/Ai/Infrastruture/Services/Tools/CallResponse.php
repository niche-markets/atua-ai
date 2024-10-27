<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\Tools;

use Billing\Domain\ValueObjects\Count;

class CallResponse
{
    public function __construct(
        public readonly string $content,
        public readonly Count $cost
    ) {
    }
}
