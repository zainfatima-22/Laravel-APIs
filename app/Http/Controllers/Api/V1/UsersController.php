<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UsersController extends ApisController
{
    use AuthorizesRequests, ApiResponses;
    /**
     * Display a paginated list of users.
     */
    public function index()
    {
        $query = User::query();
        if ($this->include('tickets')) {
            $query->with('tickets');
        }
        return UserResource::collection($query->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());
        return new UserResource($user);
    }

    /**
     * Display a single user resource.
     */
    public function show(User $user)
    {
        if ($this->include('tickets')) {
            $user->load('tickets'); // Lazy load conditionally
        }

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());
        return new UserResource($user);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return $this->ok('User deleted successfully');
    }
}
