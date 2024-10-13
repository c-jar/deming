<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Database\QueryException;
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
            $config_name = 'services.socialite_controller.'.$provider;
            $additional_scopes = config($config_name.'.additional_scopes');
            return Socialite::with($provider)->scopes($additional_scopes)->redirect();
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
            $allow_create_user = config($config_name.'.allow_create_user', $allow_create_user);
        }
        Log::debug('CONFIG: allow_create_user='.($allow_create_user ? 'true' : 'false'));
        if($allow_create_user){
            $role_claim = config($config_name.'.role_claim', '');
            Log::debug('CONFIG: role_claim='.$role_claim);
            $default_role = config($config_name.'.default_role', '');
            Log::debug('CONFIG: default_role='.$default_role);
        }

        try {
            $socialite_user = Socialite::with($provider)->user();
            $user = null;

            // Search user by email
            if($socialite_user->email){
                $user = User::query()->whereEmail($socialite_user->email)->first();
            } else {
                Log::warning("User has no attribute email");
            }
     
            // If not exist and allow to create user then create it
            if (!$user && $allow_create_user) { 
                $user = $this->create_user($socialite_user, $provider,  $role_claim, $default_role);
            }

            // If no user redirect to login with error message
            if (!$user) {
                Log::warning("User [$socialite_user->id, $socialite_user->email] not found in deming database");
                return redirect('login')->withErrors(['socialite' => trans('cruds.login.error.user_not_exist') ]);
            } 

            Log::info("User '$user->login' login with $provider provider");
     
            Auth::guard('web')->login($user);   
     
            return redirect(route('home'));
        } catch (Exception $exception) {
            return redirect('login');
        }
    }

    protected function create_user($socialite_user, string $provider, string $role_claim, string $default_role) {
        $user = new User();

        // set login with preferred_username, otherwise use id
        $login = null;
        if($socialite_user->offsetExists('preferred_username')){
            $login = $socialite_user->offsetGet("preferred_username");
        }
        if(!$login){
            $login = $socialite_user->id;
        }
        $user->login = $login;

        $user->name = $socialite_user->name;
        $user->email = $socialite_user->email;
        $user->title = "User provide by $provider";

        $role_name = "";
        if(!empty($role_claim)){
            if($socialite_user->offsetExists($role_claim)){
                $role_name = $socialite_user->offsetGet($role_claim);
            }
        }
        if(!array_key_exists($role_name, self::ROLES_MAP)){
            if(!empty($default_role)){
                $role_name = $default_role;
            } else {
                Log::error("No default role set! A valid role must be provided. role='$role_name'");
                return null;
            }
        }
        $user->role = self::ROLES_MAP[$role_name];

        $language = self::LOCALES[0];
        if ($socialite_user->offsetExists('locale')){
            $locale = explode('-', $socialite_user->offsetGet('locale'))[0];
            if (in_array($locale, self::LOCALES)) $language = $locale;
        }
        $user->language = $language;

        // TODO allow null password
        $user->password = bin2hex(random_bytes(32));

        Log::info("Create new user '$user->login' with role '$user->role' from $provider provider");
        try {
            $user->save();
        } catch(QueryException $exception){
            Log::debug($exception->getMessage());
            Log::error("Unable to create user");
            return null;
        }

        return $user;
    }
}
