<?php

namespace App\Providers\Socialite;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use GuzzleHttp\Exception\GuzzleException;
use Laravel\Socialite\Two\User;
use Log;

 
class OIDCServiceProvider extends AbstractProvider
{

    /**
     * @return string
    */
    public function getOIDCUrl()
    {
        return config('services.oidc.host');
    }
 
    /**
     * @param string $state
     *
     * @return string
     */
    protected function getAuthUrl($state)
    {
        Log::info("get auth url");
        Log::info($this->getOIDCUrl());
        return $this->buildAuthUrlFromBase($this->getOIDCUrl() . '/authorize', $state);
    }
 
    /**
     * @return string
     */
    protected function getTokenUrl()
    {
        return $this->getOIDCUrl() . '/token';
    }
 
    /**
     * @param string $token
     *
     * @throws GuzzleException
     *
     * @return array|mixed
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post($this->getOIDCUrl() . '/userInfo', [
            'headers' => [
                'cache-control' => 'no-cache',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
    
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['sub'],
            'email' => $user['email'],
            'username' => $user['username'],
            'email_verified' => $user['email_verified'],
            'family_name' => $user['family_name'],
        ]);
    }

}