<?php
// Quick API key test script
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$apiKey = config('scoutalumni.google.api_key');
$cx = config('scoutalumni.google.cx');

echo "API Key: " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -5) . " (length: " . strlen($apiKey) . ")\n";
echo "CX: $cx\n\n";

$response = Illuminate\Support\Facades\Http::timeout(10)->get('https://www.googleapis.com/customsearch/v1', [
    'key' => $apiKey,
    'cx'  => $cx,
    'q'   => 'Naoval Ramadian Octaviansyah linkedin',
    'num' => 1,
]);

echo "HTTP Status: " . $response->status() . "\n";

if ($response->failed()) {
    $err = $response->json();
    echo "Error: " . ($err['error']['message'] ?? 'unknown') . "\n";
    echo "Reason: " . ($err['error']['errors'][0]['reason'] ?? 'unknown') . "\n";
} else {
    $data = $response->json();
    echo "Total Results: " . ($data['searchInformation']['totalResults'] ?? 0) . "\n";
    echo "Items found: " . count($data['items'] ?? []) . "\n";
    if (!empty($data['items'])) {
        echo "First result: " . $data['items'][0]['title'] . " - " . $data['items'][0]['link'] . "\n";
    }
}
