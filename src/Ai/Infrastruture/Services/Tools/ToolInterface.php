<?php

declare(strict_types=1);

namespace Ai\Infrastruture\Services\Tools;

interface ToolInterface
{
    public function isEnabled(): bool;
    public function getDescription(): string;
    public function getDefinitions(): array;
    public function call(array $params = []): CallResponse;
}
