<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return new UserResource($users, 'Success', 'List of Users');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return new UserResource(null, 'Failed', $validator->errors());
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->email_verified_at = now();
        $user->remember_token = Str::random(10);
        $user->save();

        return new UserResource($user, 'Success', 'User created successfully');
    }

    public function show($uuid)
    {
        $user = User::find($uuid);
        if ($user) {
            return new UserResource($user, 'Success', 'User found');
        } else {
            return new UserResource(null, 'Failed', 'User not found');
        }
    }

    public function update(Request $request, $uuid)
    {
        $user = User::find($uuid);
        if ($user) {
            $user->name = $request->name;
            $user->password = bcrypt($request->password);
            $user->save();

            return new UserResource($user, 'Success', 'User updated successfully');
        } else {
            return new UserResource(null, 'Failed', 'User not found');
        }
    }

    public function destroy($uuid)
    {
        $user = User::find($uuid);
        if ($user) {
            $user->delete();

            return new UserResource($user, 'Success', 'User deleted successfully');
        } else {
            return new UserResource(null, 'Failed', 'User not found');
        }
    }
}
