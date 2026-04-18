<?php
// scratch/verify_sender_fix.php

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
$sender = $env['INFOBIP_SENDER'] ?? 'InfoSMS';

if ($apiKey === 'MISSING' || $baseUrl === 'MISSING') {
    echo "❌ Erreur : Identifiants manquants dans le .env\n";
    exit(1);
}

$to = "21695291258"; // Celui qui est déjà whitelisted ou au moins accepté
echo "Test d'envoi vers $to avec l'expediteur '$sender'...\n";

$url = rtrim($baseUrl, '/') . '/sms/3/messages';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$payload = json_encode([
    'messages' => [
        [
            'from' => $sender,
            'destinations' => [['to' => $to]],
            'content' => ['text' => "Test AutiCare Fix - Expéditeur: $sender - " . date('H:i:s')]
        ]
    ]
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP : $httpCode\n";
echo "Reponse : $response\n";
