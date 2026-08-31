<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class ShopifyTokenService
{
    /**
     * Get a valid Shopify token.
     *
     * If current token is valid,
     * return existing token.
     *
     * If expired / expiring,
     * generate a new token.
     */
    public function getToken(Client $client): string
    {
        if ($this->isValid($client)) {
            return $client->shopify_access_token;
        }

        return $this->generateToken($client);
    }


    /**
     * Check token validity.
     *
     * We refresh 10 minutes before actual expiry.
     */
    public function isValid(Client $client): bool
    {
        if (empty($client->shopify_access_token)) {
            return false;
        }

        if (empty($client->token_expires_at)) {
            return false;
        }

        return now()->lt(
            $client->token_expires_at->copy()->subMinutes(10)
        );
    }


    /**
     * Generate new Shopify access token.
     */
    public function generateToken(Client $client): string
    {
        /*
         * Prevent two simultaneous requests
         * from refreshing the same client token.
         */
        $lock = Cache::lock(
            'shopify-token-' . $client->id,
            30
        );

        return $lock->block(
            10,
            function () use ($client) {

                /*
                 * Refresh DB object.
                 */
                $client->refresh();

                /*
                 * Maybe another process already
                 * generated a new token.
                 */
                if ($this->isValid($client)) {
                    return $client->shopify_access_token;
                }

                return $this->requestToken($client);
            }
        );
    }


    /**
     * Call Shopify OAuth endpoint.
     */
    private function requestToken(Client $client): string
    {
        $store = $this->cleanStore(
            $client->shopify_store_url
        );

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(30)
            ->post(
                "https://{$store}/admin/oauth/access_token",
                [
                    'grant_type' =>
                    'client_credentials',

                    'client_id' =>
                    $client->shopify_client_id,

                    'client_secret' =>
                    $client->shopify_client_secret,
                ]
            );

        if (!$response->successful()) {

            $message =
                'Shopify token API failed. HTTP ' .
                $response->status() .
                ' - ' .
                $response->body();

            $client->update([
                'shopify_status' => 'failed',
                'shopify_last_error' => $message,
            ]);

            throw new \Exception($message);
        }

        $data = $response->json();

        if (empty($data['access_token'])) {

            throw new \Exception(
                'Shopify access token missing.'
            );
        }

        $expiresIn =
            (int) ($data['expires_in'] ?? 86399);

        $client->update([

            'shopify_access_token' =>
            $data['access_token'],

            'token_expires_at' =>
            now()->addSeconds($expiresIn),

            'token_updated_at' =>
            now(),

            'shopify_status' =>
            'connected',

            'shopify_last_error' =>
            null,
        ]);

        return $data['access_token'];
    }


    /**
     * Clean Shopify store URL.
     */
    private function cleanStore(?string $url): string
    {
        $url = trim($url);

        $url = preg_replace(
            '#^https?://#i',
            '',
            $url
        );

        return rtrim($url, '/');
    }
}
