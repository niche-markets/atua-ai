<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

class Chunk implements JsonSerializable
{
    public readonly Token|Call $data;

    public function __construct(
        string|Token|Call $value = ''
    ) {
        $this->data = is_string($value) ? new Token($value) : $value;
    }

    #[Override]
    public function jsonSerialize(): mixed
    {
        return $this->data;
    }
}
