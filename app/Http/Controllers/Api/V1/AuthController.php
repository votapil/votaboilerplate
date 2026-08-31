<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\DeleteAccountAction;
use App\Actions\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordForgotRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Token authentication for the SPA and any mobile shell.
 *
 * There is no refresh endpoint by design: Sanctum personal access tokens are
 * opaque and long-lived (bound the lifetime with SANCTUM_TOKEN_EXPIRATION if
 * you need one), so a refresh flow would add moving parts without adding
 * security.
 *
 * Rate limits live on the routes, not here — see routes/api.php.
 */
final class AuthController extends Controller
{
    /**
     * Everything UserResource reads. Loaded explicitly because
     * Model::shouldBeStrict() turns a lazy load into an exception outside
     * production — which is exactly what you want: getAllPermissions() touches
     * two relations, and on a list endpoint that is two queries per row.
     *
     * @var list<string>
     */
    private const USER_RELATIONS = ['settings', 'roles', 'roles.permissions', 'permissions'];

    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $user = $action->handle($request->validated());

        return $this->tokenResponse($user, Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::findByEmail($request->validated('email'));

        // One message for "no such account" and for "wrong password": telling
        // them apart hands an attacker a list of valid addresses.
        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return $this->tokenResponse($user, Response::HTTP_OK);
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user()->loadMissing(self::USER_RELATIONS));
    }

    public function logout(Request $request): JsonResponse
    {
        // Only the token that made this call. Killing every token would sign the
        // user out of their other devices, which is a different feature.
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('auth.logged_out')]);
    }

    /**
     * Self-service account deletion — required by both mobile stores and by the
     * GDPR, and far cheaper to have as a skeleton than to invent the week before
     * a store review.
     *
     * A bearer token is the only credential asked for. Re-asking for the
     * password here is a defensible hardening once the product has real data to
     * lose; it also means the client must send a body on a DELETE, which not
     * every HTTP stack does gracefully.
     */
    public function deleteAccount(Request $request, DeleteAccountAction $action): JsonResponse
    {
        $action->handle($request->user());

        return response()->json(['message' => __('auth.account_deleted')]);
    }

    public function passwordForgot(PasswordForgotRequest $request): JsonResponse
    {
        // The broker's result is discarded on purpose: an "unknown email" reply
        // would turn this endpoint into an account-enumeration oracle.
        Password::sendResetLink($request->validated());

        return response()->json(['message' => __('auth.reset_link_sent')]);
    }

    public function passwordReset(PasswordResetRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();

                // A password reset is what someone does after losing control of
                // the account, so every issued token has to go with it.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => __($status)]);
    }

    private function tokenResponse(User $user, int $status): JsonResponse
    {
        return UserResource::make($user->loadMissing(self::USER_RELATIONS))
            ->additional(['token' => $user->createToken('api')->plainTextToken])
            ->response()
            ->setStatusCode($status);
    }
}
