<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Services\ShopifyTokenService;

class ShopifyGenerateTokens extends Command
{
    protected $signature = 'shopify:generate-tokens';

    protected $description =
    'Generate Shopify access tokens for all configured clients';

    public function handle(
        ShopifyTokenService $tokenService
    ) {
        $clients = Client::query()
            ->whereNotNull('shopify_store_url')
            ->whereNotNull('shopify_client_id')
            ->whereNotNull('shopify_client_secret')
            ->get();

        if ($clients->isEmpty()) {

            $this->warn(
                'No Shopify clients found with credentials.'
            );

            return self::SUCCESS;
        }

        foreach ($clients as $client) {

            $this->info(
                "Processing CRM Client ID: {$client->id}"
            );

            try {

                $tokenService->generateToken($client);

                $this->info(
                    "SUCCESS: Client {$client->id}"
                );
            } catch (\Throwable $e) {

                $this->error(
                    "FAILED: Client {$client->id}"
                );

                $this->error(
                    $e->getMessage()
                );
            }
        }

        return self::SUCCESS;
    }
}
