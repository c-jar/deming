<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use Log;

class SocialiteController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['redirect', 'callback']]);
    }

    public function redirect(string $provider) {
        $providers = config('services.socialite_avialable', []);

        if (in_array($provider, $providers)) {
            return Socialite::driver($provider)->redirect();
        }

        abort(404);
    }

    public function callback(string $provider) {
        $providers = config('services.socialite_avialable', []);

        if (! in_array($provider, $providers)) {
            abort(404);
        }

        try {
            $socialiteUser = Socialite::driver($provider)->user();
            $user = User::query()->whereEmail($socialiteUser->email)->first();
     
            if (!$user) {
                return redirect('login');
            }
     
            Auth::guard('web')->login($user);   
     
            return redirect(route('home'));
        } catch (Exception $exception) {
            return redirect('login');
        }
    }
}
