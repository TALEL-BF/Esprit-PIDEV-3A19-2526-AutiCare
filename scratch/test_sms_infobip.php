<?php
// scratch/test_sms_infobip.php

// Charger le .env manuellement
$envFile = __DIR__.'/../.env';
$env = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $env[trim($name)] = trim($value);
    }
}

$apiKey = $env['INFOBIP_API_KEY'] ?? 'MISSING';
$baseUrl = $env['INFOBIP_BASE_URL'] ?? 'MISSING';
if ($baseUrl !== 'MISSING' && !str_starts_with($baseUrl, 'http')) {
    $baseUrl = 'https://' . $baseUrl;
}
$testNumber = '+21695527173'; // Avec le + pour plus de compatibilité
$testMessage = "Test Infobip (E.164) : " . date('H:i:s');

// 1. Vérifier le solde avant l'envoi
$chBal = curl_init(rtrim($baseUrl, '/') . "/account/1/balance");
curl_setopt($chBal, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chBal, CURLOPT_HTTPHEADER, ['Authorization: App ' . $apiKey, 'Accept: application/json']);
$balRes = json_decode(curl_exec($chBal), true);
$balance = $balRes['balance'] ?? 0;
curl_close($chBal);

echo "--- Diagnostic du compte ---\n";
echo "Solde actuel : $balance " . ($balRes['currency'] ?? 'USD') . "\n";
if ($balance <= 0) {
    echo "⚠️ ATTENTION : Votre solde est épuisé ($balance). Le SMS sera accepté par l'API mais ne sera probablement pas délivré.\n";
}
echo "---------------------------\n\n";

echo "Test d'envoi vers $testNumber...\n";

$ch = curl_init(rtrim($baseUrl, '/') . '/sms/3/messages');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$data = [
    'messages' => [
        [
            'sender' => 'ServiceSMS', 
            'destinations' => [['to' => $testNumber]],
            'content' => ['text' => $testMessage]
        ]
    ]
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections (308)
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "Erreur cURL : " . curl_error($ch) . "\n";
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "URL effective : $effectiveUrl\n";
echo "Code HTTP : $httpCode\n";
echo "Reponse detaillee : $response\n";

if ($httpCode >= 200 && $httpCode < 300) {
    $resData = json_decode($response, true);
    $status = $resData['messages'][0]['status']['name'] ?? 'UNKNOWN';
    echo "✅ Success! Status: $status\n";
    echo "ID Message: " . ($resData['messages'][0]['messageId'] ?? 'N/A') . "\n";
} else {
    echo "❌ Failed.\n";
}
