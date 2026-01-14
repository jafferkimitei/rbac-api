<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'roles' => $u->getRoleNames()->values(),
                    'permissions' => $u->getAllPermissions()->pluck('name')->values(),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'data' => $users,
        ]);
    }
}