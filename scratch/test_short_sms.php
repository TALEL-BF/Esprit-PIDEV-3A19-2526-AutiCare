<?php
// scratch/test_short_sms.php

$typeLabel = 'Consult. Famille'; // Le plus long
$childName = 'Test Enfant';
$action = 'déplacé';
$psyName = 'Test Psy';
$newDateStr = date('d/m/Y à H:i');

$message = sprintf(
    "AutiCare: RDV (%s) %s %s avec %s le %s. Lien dans votre espace.",
    $typeLabel,
    $childName ?: 'enfant',
    $action,
    $psyName ?: 'Psy',
    $newDateStr
);

echo "Message : $message\n";
echo "Longueur : " . mb_strlen($message) . " caractères\n";

if (mb_strlen($message) <= 160) {
    echo "✅ OK : Le message tient dans 1 seul SMS.\n";
} else {
    echo "❌ TROP LONG : Le message dépasse 160 caractères.\n";
}
