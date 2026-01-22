<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PriceCrawlerService
{
    private const PAGINATION_LIMIT = 100;

    private const API_CONFIG = [
        'uniqlo' => [
            'api_url' => 'https://www.uniqlo.com/jp/api/commerce/v5/ja/products',
            'store_id' => '126608',
        ],
        'gu' => [
            'api_url' => 'https://www.gu-global.com/jp/api/commerce/v5/ja/products',
            'store_id' => '126608',
        ],
    ];

    /**
     * Execute the crawling task.
     */
    public function crawl(?string $brand = null): array
    {
        $results = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        $brands = $brand ? [$brand] : ['uniqlo', 'gu'];

        foreach ($brands as $brandName) {
            Log::info("Starting to crawl {$brandName} products...");

            try {
                $items = $this->fetchAllProducts($brandName);
                $brandResults = $this->processProducts($items, $brandName);

                $results['total'] += $brandResults['total'];
                $results['created'] += $brandResults['created'];
                $results['updated'] += $brandResults['updated'];

                Log::info("{$brandName} crawl completed", $brandResults);
            } catch (\Exception $e) {
                $results['errors'][] = "{$brandName}: " . $e->getMessage();
                Log::error("{$brandName} crawl failed: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Fetch all products with pagination.
     */
    private function fetchAllProducts(string $brand): array
    {
        $config = self::API_CONFIG[$brand];
        $items = [];
        $offset = 0;

        do {
            $response = $this->httpRequest($config['api_url'], [
                'storeId' => $config['store_id'],
                'limit' => self::PAGINATION_LIMIT,
                'offset' => $offset,
            ]);

            if (!$response || !isset($response['result']['items'])) {
                break;
            }

            $pageItems = $response['result']['items'];
            $items = array_merge($items, $pageItems);
            $total = $response['result']['pagination']['total'] ?? 0;

            Log::debug("Fetched {$brand} products: " . count($items) . "/{$total}");

            $offset += self::PAGINATION_LIMIT;
        } while ($offset < $total);

        return $items;
    }

    /**
     * Send HTTP request with retry mechanism.
     */
    private function httpRequest(string $url, array $params = []): ?array
    {
        $maxRetries = 4;
        $retryDelay = 1;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Accept' => 'application/json',
                    ])
                    ->get($url, $params);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning("HTTP request failed (attempt {$attempt}/{$maxRetries}): {$response->status()}");
            } catch (\Exception $e) {
                Log::warning("HTTP request exception (attempt {$attempt}/{$maxRetries}): " . $e->getMessage());
            }

            if ($attempt < $maxRetries) {
                sleep($retryDelay * $attempt);
            }
        }

        return null;
    }

    /**
     * Process and save product data.
     */
    private function processProducts(array $items, string $brand): array
    {
        $created = 0;
        $updated = 0;

        foreach ($items as $item) {
            $productId = $item['productId'] ?? null;
            $priceGroup = $item['priceGroup'] ?? null;
            $price = $item['prices']['base']['value'] ?? null;

            if (!$productId || !$priceGroup || $price === null) {
                continue;
            }

            // Find or create product
            $product = Product::firstOrNew([
                'product_id' => $productId,
                'price_group' => $priceGroup,
            ]);

            $isNew = !$product->exists;

            // Update product info
            $product->name = $item['name'] ?? '';
            $product->brand = $brand;
            $product->gender = $item['genderCategory'] ?? null;
            $product->image_url = $this->extractImageUrl($item['images'] ?? []);
            $product->current_price = $price;

            // Update lowest/highest price
            if ($isNew || $price < $product->lowest_price) {
                $product->lowest_price = $price;
            }
            if ($isNew || $price > $product->highest_price) {
                $product->highest_price = $price;
            }

            $product->save();

            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }

            // Record price history only if price changed
            $lastHistory = PriceHistory::where('product_id', $product->id)
                ->latest()
                ->first();

            if (!$lastHistory || $lastHistory->price != $price) {
                PriceHistory::create([
                    'product_id' => $product->id,
                    'price' => $price,
                ]);
            }
        }

        return [
            'total' => count($items),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Extract the main image URL from the images array.
     */
    private function extractImageUrl(array $images): ?string
    {
        if (!isset($images['main'])) {
            return null;
        }

        // Handle simple string format (for testing)
        if (is_string($images['main'])) {
            return $images['main'];
        }

        // Handle nested object format: { "colorCode": { "image": "url", "model": [] } }
        if (is_array($images['main'])) {
            foreach ($images['main'] as $colorData) {
                if (isset($colorData['image'])) {
                    return $colorData['image'];
                }
            }
        }

        return null;
    }
}
