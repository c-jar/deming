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

    public const ROLES_MAP = [
        //'admin' => '1',
        'user' => '2',
        'auditee' => '5',
        'auditor' => '3',
        //'api' => '4',
    ];

    public const LOCALES = ['en', 'fr'];

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

        // Get additionnal config for current provider
        $config_name = 'services.socialite_controller.'.$provider;
        $allow_create_user = false;
        if(config($config_name)){
            if(config($config_name.'.allow_create_user')){
                $allow_create_user = config($config_name.'.allow_create_user');
            }
        }
        Log::debug('Config allow_create_user='.$allow_create_user);


        try {
            $socialite_user = Socialite::with($provider)->user();
            $user = null;

            if($socialite_user->email){
                $user = User::query()->whereEmail($socialite_user->email)->first();
            } else {
                Log::warning("User has no attribute email");
            }
     
            if (!$user) { 
                if ($allow_create_user){
                    $user = new User();
<<<<<<< HEAD

                    // set login with preferred_username, otherwise use id
                    $login = null;
                    if($socialite_user->offsetExists('preferred_username')){
                        $login = $socialite_user->offsetGet("preferred_username");
                    }
                    if(!$login){
                        $login = $socialite_user->id;
                    }
                    $user->login = $login;

=======
                    $user->login = $socialite_user->id;
>>>>>>> d13523ef3b04fd0d154f5c8fe6e6f4c9fa3e595e
                    $user->name = $socialite_user->name;
                    $user->email = $socialite_user->email;
                    $user->title = "";
                    $user->role = self::ROLES_MAP['auditee'];

                    $language = self::LOCALES[0];
                    if ($socialite_user->offsetExists('locale')){
                        $locale = explode('-', $socialite_user->offsetGet('locale'));
                        $_language = $locale[0];
                        if (in_array($_language, self::LOCALES)) $language = $_language;
                    }
                    $user->language = $language;

                    // TODO allow null password
                    $user->password = bin2hex(random_bytes(32));

                    Log::info("Create new user '$user->login' with role '$user->role' from $provider provider");
                    $user->save();


                } else {
                    Log::warning("User [$socialite_user->id, $socialite_user->email] not found in deming database");
                    return redirect('login')->withErrors(['socialite' => trans('cruds.login.error.user_not_exist') ]);
                } 
            }

            Log::info("User '$user->login' login with $provider provider");
     
            Auth::guard('web')->login($user);   
     
            return redirect(route('home'));
        } catch (Exception $exception) {
            return redirect('login');
        }
    }
}
