<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\DTO\User\CreateUserDTO;
use App\Http\DTO\User\UserDTO;
use App\Models\User;
use Illuminate\Support\Collection;

class UserRepository
{
    public function createUser(CreateUserDTO $createUserDTO): UserDTO
    {
        $user = new User([
             'name' => $createUserDTO->name,
             'email' => $createUserDTO->email,
             'password' => $createUserDTO->password,
        ]);

        $user->save();

        return new UserDTO($user->id, $user->name, $user->email);
    }

    public function getUserIdOrNullByEmail(string $email): ?int
    {
        return User::where('email', $email)->first()?->id;
    }

    public function getAllActiveUserIds(): Collection
    {
        // @todo check rules for subscription
        return User::all()->pluck('id');
    }
}
