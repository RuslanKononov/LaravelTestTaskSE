<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Factory\UserFactory;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserFactory $userFactory,
    ) {
    }

    public function userRegister(Request $request): JsonResponse
    {
        // Data validation
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => $validator->errors(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $userDTO = $this->userRepository->createUser($this->userFactory->createUserDTO($request));

        return response()->json(
            [
                'success' => true,
                'message' => 'You have successfully registered!',
                'data' => $userDTO,
            ],
            Response::HTTP_CREATED
        );
    }

    public function userLogin(Request $request): JsonResponse
    {
        // Validate data for login
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        // Generate JWT token
        $token = JWTAuth::attempt($request->only('email', 'password'));
        if (!$token) {
            return response()->json(
                ['message' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return response()->json(['token' => $token]);
    }

    public function userInfo(Request $request): JsonResponse
    {
        $user = $request->get('authenticatedUser');

        return response()->json(
            [
                'success' => true,
                'userName' => $user?->name,
                'userEmail' => $user?->email,
            ],
            Response::HTTP_OK,
        );
    }
}
