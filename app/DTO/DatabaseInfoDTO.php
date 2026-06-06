<?php

namespace App\DTO;

use JsonSerializable;

readonly class DatabaseInfoDTO implements JsonSerializable
{
    public function __construct(
        public string $driver,
        public string $serverVersion,
        public string $databaseName
    ) {}
    
    public function jsonSerialize(): array
    {
        return [
            'driver' => $this->driver,
            'serverVersion' => $this->serverVersion,
            'databaseName' => $this->databaseName,
        ];
    }
}