<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Services\ShopifyTokenService;

class ShopifyRefreshTokens extends Command
{
    protected $signature = 'shopify:refresh-tokens';

    protected $description = 'Refresh expired or near-expiry Shopify tokens';

    public function handle(ShopifyTokenService $tokenService)
    {
        $this->info('Checking Shopify tokens...');

        $clients = Client::query()
            ->whereNotNull('shopify_store_url')
            ->whereNotNull('shopify_client_id')
            ->whereNotNull('shopify_client_secret')
            ->get();

        if ($clients->isEmpty()) {
            $this->warn('No Shopify clients found.');
            return self::SUCCESS;
        }

        foreach ($clients as $client) {

            try {

                if ($tokenService->isValid($client)) {

                    $this->line(
                        "Client {$client->id}: Token is valid - SKIPPED"
                    );

                    continue;
                }

                $tokenService->generateToken($client);

                $this->info(
                    "Client {$client->id}: Token REFRESHED"
                );
            } catch (\Throwable $e) {

                $this->error(
                    "Client {$client->id}: FAILED"
                );

                $this->error(
                    $e->getMessage()
                );
            }
        }

        $this->info('Shopify token check completed.');

        return self::SUCCESS;
    }
}
