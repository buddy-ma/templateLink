<?php

namespace App\Socialite;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class ZohoProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array<int, string>
     */
    protected $scopes = ['aaaserver.profile.READ', 'openid', 'email', 'profile'];

    /**
     * Get the base domain for Zoho API calls.
     * Reads from config at runtime to avoid cached stale values.
     */
    protected function getBaseDomain(): string
    {
        $domain = config('services.zoho.domain')
            ?? $this->config['domain']
            ?? 'accounts.zoho.com';

        $domain = str_replace(['https://', 'http://'], '', $domain);

        return 'https://'.$domain;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseDomain().'/oauth/v2/auth', $state);
    }

    /**
     * Get the GET parameters for the code request.
     *
     * @param  string|null  $state
     * @return array<string, mixed>
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        $fields['response_type'] = 'code';

        if (empty($fields['client_id']) && ! empty($this->clientId)) {
            $fields['client_id'] = $this->clientId;
        }

        if (empty($fields['redirect_uri']) && ! empty($this->redirectUrl)) {
            $fields['redirect_uri'] = $this->redirectUrl;
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getBaseDomain().'/oauth/v2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        try {
            $response = $this->getHttpClient()->get($this->getBaseDomain().'/oauth/user/info', [
                'headers' => [
                    'Authorization' => 'Zoho-oauthtoken '.$token,
                    'Accept' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if ($response && $response->getStatusCode() === 401) {
                $body = json_decode($response->getBody()->getContents(), true);
                if (isset($body['cause']) && $body['cause'] === 'INVALID_OAUTHSCOPE') {
                    Log::error('Zoho OAuth: INVALID_OAUTHSCOPE error', [
                        'token_preview' => substr($token, 0, 20).'...',
                        'domain' => $this->getBaseDomain(),
                        'scopes_requested' => $this->scopes,
                    ]);
                }
            }
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $this->firstPresent($user, ['ZUID', 'sub', 'id']),
            'name' => $this->firstPresent($user, ['Display_Name', 'name', 'given_name']) ?? $this->composedName($user),
            'email' => $this->firstPresent($user, ['Email', 'email']) ?? '',
            'avatar' => $this->firstPresent($user, ['picture', 'photo']),
        ]);
    }

    /**
     * Zoho names the same attributes differently depending on the endpoint.
     *
     * @param  array<int, string>  $keys
     */
    private function firstPresent(array $user, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (isset($user[$key])) {
                return $user[$key];
            }
        }

        return null;
    }

    /**
     * Fallback when Zoho does not provide a display name.
     */
    private function composedName(array $user): string
    {
        return trim(($user['First_Name'] ?? '').' '.($user['Last_Name'] ?? ''));
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
