<?php
// scratch/list_sms_logs.php

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

if ($apiKey === 'MISSING' || $baseUrl === 'MISSING') {
    echo "❌ Erreur : Identifiants manquants dans le .env\n";
    exit(1);
}

echo "Recuperation des derniers logs SMS...\n";

$ch = curl_init(rtrim($baseUrl, '/') . "/sms/1/logs?limit=5");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP : $httpCode\n";
echo "Reponse : $response\n";
