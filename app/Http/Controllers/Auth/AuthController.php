<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @param AuthRegisterRequest $request
     * @return Response
     */
    public function register(AuthRegisterRequest $request)
    {
        $password = bcrypt($request->password);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password
        ]);

        $token = $user->createToken('vue_survey')->plainTextToken;

        return response([
           'user' => new UserResource($user),
           'token' => $token
        ]);
    }

    /**
     * @param AuthLoginRequest $request
     * @return Response
     */
    public function login(AuthLoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return response(['message' => 'invalid credentials'], 404);
        }

        $token = $user->createToken('vue_survey')->plainTextToken;

        return response([
            'user' => new UserResource($user),
            'token' => $token
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response([
            'user' => new UserResource($user)
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Revolke all tokens client
        $user->tokens()->delete();

        return response([], 204);
    }
}
