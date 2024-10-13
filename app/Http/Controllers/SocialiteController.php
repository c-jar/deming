<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Log;

/**
 * Socialite Controller for OpenID Connect Autentication
 */
class SocialiteController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['redirect', 'callback']]);
    }

    /**
     * Redirect action use to redirect user to OIDC provider.
     */
    public function redirect(string $provider) {
        $providers = config('services.socialite_controller.providers', []);

        if (in_array($provider, $providers)) {
            Log::debug("Redirect with '$provider' provider");
            return Socialite::with($provider)->redirect();
        }

        Log::warning("Redirect: Provider '$provider' not found.");
        abort(404);
    }

    /**
     * Callback action use when OIDC provider redirect user to app.
     */
    public function callback(Request $request, string $provider) {
        $providers = config('services.socialite_controller.providers', []);

        if (! in_array($provider, $providers)) {
            Log::warning("Callback: Provider '$provider' not found.");
            abort(404);
        }

        Log::debug("Callback provider : '$provider'");

        try {
            $socialiteUser = Socialite::with($provider)->user();
            $user = null;

            if($socialiteUser->email){
                $user = User::query()->whereEmail($socialiteUser->email)->first();
            } else {
                Log::warning("User has no attribute email");
            }
     
            if (!$user) {
                Log::warning("User [$socialiteUser->id, $socialiteUser->email] not found in deming database");
                return redirect('login')->withErrors(['socialite' => trans('cruds.login.error.user_not_exist') ]);
            }

            Log::info("User '$user->login' login with $provider provider");
     
            Auth::guard('web')->login($user);   
     
            return redirect(route('home'));
        } catch (Exception $exception) {
            return redirect('login');
        }
    }
}
