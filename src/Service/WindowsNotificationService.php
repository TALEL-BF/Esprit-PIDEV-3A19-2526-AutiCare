<?php

namespace App\Service;

use App\Entity\Seance;
use Psr\Log\LoggerInterface;

class WindowsNotificationService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Envoie une notification système Windows via PowerShell
     * 
     * @param string $titre Titre de la notification
     * @param string $message Contenu du message
     * @param string $type Type d'alerte (info, success, warning, error)
     * @return bool True si succès, false sinon
     */
    public function sendWindowsNotification(string $titre, string $message, string $type = 'info'): bool
    {
        // Vérifier que nous sommes sur Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $this->logger->warning('Tentative de notification Windows sur un système non-Windows');
            return false;
        }

        try {
            $sanitizedTitre = $this->sanitizeForPowerShell($titre);
            $sanitizedMessage = $this->sanitizeForPowerShell($message);
            
            $iconType = match($type) {
                'success' => 'Information',
                'warning' => 'Warning',
                'error' => 'Error',
                default => 'Information',
            };

            // Script PowerShell pour afficher une notification Toast
            $powershellScript = $this->buildPowerShellScript(
                $sanitizedTitre,
                $sanitizedMessage,
                $iconType
            );

            // Exécuter le script PowerShell
            $this->executePowerShellScript($powershellScript);

            $this->logger->info('Notification Windows envoyée', [
                'titre' => $titre,
                'type' => $type,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la notification Windows', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envoie une notification d'alerte system pour une nouvelle séance
     */
    public function notifyAdminNewSeance(Seance $seance): bool
    {
        $titre = '🔔 Nouvelle Séance Ajoutée';
        $message = sprintf(
            "Séance: %s\n📅 Date: %s\n⏰ Heure: %s\n⏱️ Durée: %d min",
            $seance->getTitreSeance(),
            $seance->getDateSeance() ? $seance->getDateSeance()->format('d/m/Y') : 'N/A',
            $seance->getDateSeance() ? $seance->getDateSeance()->format('H:i') : 'N/A',
            $seance->getDuree() ?? 0
        );

        return $this->sendWindowsNotification($titre, $message, 'success');
    }

    /**
     * Envoie une notification d'alerte system pour une modification de séance
     */
    public function notifyAdminSeanceUpdate(Seance $seance): bool
    {
        $titre = '✏️ Séance Modifiée';
        $message = sprintf(
            "Séance: %s\n📅 Date: %s\n⏰ Heure: %s\n⏱️ Durée: %d min",
            $seance->getTitreSeance(),
            $seance->getDateSeance() ? $seance->getDateSeance()->format('d/m/Y') : 'N/A',
            $seance->getDateSeance() ? $seance->getDateSeance()->format('H:i') : 'N/A',
            $seance->getDuree() ?? 0
        );

        return $this->sendWindowsNotification($titre, $message, 'warning');
    }

    /**
     * Construit le script PowerShell pour afficher la notification
     */
    private function buildPowerShellScript(string $titre, string $message, string $iconType): string
    {
        return <<<PS
[Windows.UI.Notifications.ToastNotificationManager, Windows.UI.Notifications] > \$null

\$appId = 'AutiCare.Admin'
\$xml = @"
<toast>
    <visual>
        <binding template="ToastText02">
            <text id="1">$titre</text>
            <text id="2">$message</text>
        </binding>
    </visual>
</toast>
"@

\$toast = New-Object Windows.UI.Notifications.ToastNotification \$xml
\$notifier = [Windows.UI.Notifications.ToastNotificationManager]::CreateToastNotifier(\$appId)
\$notifier.Show(\$toast)
PS;
    }

    /**
     * Exécute un script PowerShell de manière sécurisée
     */
    private function executePowerShellScript(string $script): void
    {
        // Créer un fichier temporaire pour stocker le script
        $tempFile = tempnam(sys_get_temp_dir(), 'auticare_notif_');
        
        try {
            file_put_contents($tempFile, $script);
            
            // Exécuter le script PowerShell en arrière-plan
            $command = sprintf(
                'powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%s"',
                escapeshellarg($tempFile)
            );

            // Exécuter sans attendre la réponse (non-bloquant)
            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
            }
        } finally {
            // Nettoyer le fichier temporaire
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Nettoie une chaîne pour PowerShell
     */
    private function sanitizeForPowerShell(string $input): string
    {
        // Remplacer les caractères spéciaux problématiques
        $input = str_replace('"', '\"', $input);
        $input = str_replace('$', '\$', $input);
        $input = str_replace('`', '``', $input);
        
        return $input;
    }
}
