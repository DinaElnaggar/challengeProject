<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UsersController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $users = User::query()->paginate(15);
        return $this->success([
            'users' => $users,
        ]);
    }

    public function show(string $id)
    {
        $user = User::withTrashed()->find((int) $id);
        if (!$user) {
            return $this->notFound('User not found');
        }
        return $this->success(['user' => $user]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($validated);

        return $this->success(['user' => $user], 201);
    }

    public function update(Request $request, string $id)
    {
        $user = User::find((int) $id);
        if (!$user) {
            return $this->notFound('User not found');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        $user->fill($validated);
        $user->save();

        return $this->success(['user' => $user]);
    }

    public function destroy(string $id)
    {
        $user = User::find((int) $id);
        if (!$user) {
            return $this->notFound('User not found');
        }
        $user->delete();
        return $this->success([], Response::HTTP_NO_CONTENT);
    }

    public function restore(string $id)
    {
        $user = User::withTrashed()->find((int) $id);
        if (!$user) {
            return $this->notFound('User not found');
        }
        if (!$user->trashed()) {
            return $this->fail('User is not deleted', [], 400);
        }
        $user->restore();
        return $this->success(['user' => $user]);
    }
}

