<?php

declare(strict_types=1);

namespace App\Http\DTO\User;

class CreateUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
