<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;

readonly class ServerInfoDTO implements JsonSerializable
{
    public function __construct(
        public string $phpVersion,
        public string $phpSapi,
        public int $maxExecutionTime,
        public string $memoryLimit
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'phpVersion' => $this->phpVersion,
            'phpSapi' => $this->phpSapi,
            'maxExecutionTime' => $this->maxExecutionTime,
            'memoryLimit' => $this->memoryLimit,
        ];
    }
}