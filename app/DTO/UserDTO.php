<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;
use App\Models\User;
use Carbon\Carbon;

readonly class UserDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public string $birthday
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            username: $user->username,
            email: $user->email,
            birthday: $user->birthday->format('Y-m-d')
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'birthday' => $this->birthday,
        ];
    }
}