<?php

declare(strict_types=1);

namespace App\Http\Controllers\Factory;

use App\Http\DTO\User\CreateUserDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserFactory
{
    public function createUserDTO(Request $request): CreateUserDTO
    {
        return new CreateUserDTO(
            name: $request->get('name'),
            email: $request->get('email'),
            password: Hash::make($request->get('password')),
        );
    }
}
