<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->where('role', User::ROLE_USER)
            ->orderBy('name')
            ->get();

        return ApiResponse::success('Assignable users fetched successfully.', [
            'users' => UserResource::collection($users)->resolve(),
        ]);
    }
}
