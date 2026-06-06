<?php

namespace App\DTO;

use JsonSerializable;

readonly class ClientInfoDTO implements JsonSerializable
{
    public function __construct(
        public string $ipAddress,
        public string $userAgent
    ) {}
    
    public function jsonSerialize(): array
    {
        return [
            'ipAddress' => $this->ipAddress,
            'userAgent' => $this->userAgent,
        ];
    }
}