<?php
// scratch/test_sms_textlink.php

$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $env[trim($name)] = trim($value, '"');
    }
}

$apiKey = $env['TEXTLINK_API_KEY'] ?? '';
$baseUrl = $env['TEXTLINK_BASE_URL'] ?? 'https://textlinksms.com';
$defaultCountryCode = $env['TEXTLINK_DEFAULT_COUNTRY_CODE'] ?? '216';
$testNumber = '21695527173';
$testMessage = 'Test TextLink: ' . date('H:i:s');

$phone = trim($testNumber);
$phone = preg_replace('/[\s\-\.\(\)]/', '', $phone) ?? $phone;
if (str_starts_with($phone, '00')) {
    $phone = '+' . substr($phone, 2);
} elseif (!str_starts_with($phone, '+')) {
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    $countryCode = preg_replace('/\D+/', '', $defaultCountryCode) ?? '';
    if ($digits !== '') {
        if ($countryCode !== '' && !str_starts_with($digits, $countryCode)) {
            if (str_starts_with($digits, '0')) {
                $digits = ltrim($digits, '0');
            }
            $phone = '+' . $countryCode . $digits;
        } else {
            $phone = '+' . $digits;
        }
    }
}

if ($apiKey === '') {
    echo "Missing TEXTLINK_API_KEY\n";
    exit(1);
}

if (!str_starts_with($baseUrl, 'http')) {
    $baseUrl = 'https://' . $baseUrl;
}

$url = rtrim($baseUrl, '/') . '/api/send-sms';

echo "Sending to: $phone\n";
echo "URL: $url\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'phone_number' => $phone,
    'text' => $testMessage,
]));
$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error !== '') {
    echo "cURL error: $error\n";
    exit(1);
}

echo "HTTP code: $httpCode\n";
echo "Response: $response\n";
