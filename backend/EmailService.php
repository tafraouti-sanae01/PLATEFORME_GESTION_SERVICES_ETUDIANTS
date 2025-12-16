<?php
/**
 * Service d'envoi d'email avec PHPMailer
 */

// Vérifier si PHPMailer est installé
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Génère le footer HTML pour les emails
 */
function get_email_footer(): string
{
    $currentYear = date('Y');
    return '
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e0e0e0; text-align: center; color: #666666; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6;">
        <p style="margin: 10px 0; color: #666666;">
            Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.
        </p>
        <p style="margin: 10px 0; color: #666666;">
            © ' . $currentYear . ' Service Pro. Tous droits réservés.
        </p>
    </div>';
}

/**
 * Convertit un message texte en HTML avec mise en forme
 */
function convert_text_to_html(string $text): string
{
    // Convertir les sauts de ligne en <br>
    $html = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    
    // Améliorer la mise en forme
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
    
    return $html;
}

/**
 * Génère un template HTML complet pour l'email
 */
function create_email_template(string $content, bool $isHtml = false): string
{
    // Si le contenu est déjà en HTML, l'utiliser tel quel, sinon le convertir
    $bodyContent = $isHtml ? $content : convert_text_to_html($content);
    
    $footer = get_email_footer();
    
    return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Service Scolarité</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f5f5f5;">
        <tr>
            <td style="padding: 20px 0;">
                <table role="presentation" style="width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 40px 40px 30px 40px;">
                            <div style="color: #333333; font-size: 16px; line-height: 1.6;">
                                ' . $bodyContent . '
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 40px 40px 40px;">
                            ' . $footer . '
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function send_email_to_student(string $to, string $subject, string $message, bool $isHtml = false, ?array $attachment = null): array
{
    // Charger la configuration email si elle existe
    $configFile = __DIR__ . '/email_config.php';
    $config = [];
    if (file_exists($configFile)) {
        $config = require $configFile;
    }
    
    // Vérifier si on est en mode développement
    // Si dev_mode est explicitement défini à false, on ignore la détection automatique
    if (isset($config['dev_mode'])) {
        $isDevelopment = $config['dev_mode'] === true;
    } else {
        // Si non défini, détecter automatiquement selon l'hôte
        $isDevelopment = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:8000', '127.0.0.1:8000']);
    }
    
    // Si PHPMailer est disponible, l'utiliser
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer(true);
        
        try {
            if ($isDevelopment) {
                // En développement, simuler l'envoi (pas de configuration SMTP requise)
                $htmlBody = create_email_template($message, $isHtml);
                error_log("=== EMAIL SIMULÉ (PHPMailer disponible) ===");
                error_log("À: $to");
                error_log("Sujet: $subject");
                error_log("Message HTML:\n$htmlBody");
                error_log("===================");
                
                return [
                    'sent' => false,
                    'message' => "Email simulé en développement. Créez email_config.php et configurez SMTP pour l'envoi réel.",
                ];
            }
            
            // Configuration SMTP depuis le fichier de config ou variables d'environnement
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'] ?? getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'] ?? getenv('SMTP_USER') ?: 'votre-email@gmail.com';
            $mail->Password = $config['smtp_pass'] ?? getenv('SMTP_PASS') ?: 'votre-mot-de-passe-app';
            
            // Déterminer le type de chiffrement selon le port
            $port = (int)($config['smtp_port'] ?? getenv('SMTP_PORT') ?: 587);
            $mail->Port = $port;
            
            // Port 465 = SSL, Port 587 = TLS
            if ($port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            }
            
            // Expéditeur et destinataire
            $fromEmail = $config['smtp_from'] ?? getenv('SMTP_FROM') ?: 'scolarite@univ.ma';
            $fromName = $config['smtp_from_name'] ?? 'Service Scolarité';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            
            // Créer le template HTML avec le footer
            $htmlBody = create_email_template($message, $isHtml);
            
            // Créer une version texte pour les clients email qui ne supportent pas HTML
            $textBody = strip_tags($htmlBody);
            $textBody = html_entity_decode($textBody, ENT_QUOTES, 'UTF-8');
            // Nettoyer les espaces multiples et sauts de ligne
            $textBody = preg_replace('/\s+/', ' ', $textBody);
            $textBody = trim($textBody);
            
            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // Ajouter la pièce jointe si fournie
            if ($attachment && isset($attachment['content']) && isset($attachment['filename'])) {
                try {
                    $mail->addStringAttachment($attachment['content'], $attachment['filename'], 'base64', 'application/pdf');
                } catch (Exception $e) {
                    error_log("Erreur lors de l'ajout de la pièce jointe: " . $e->getMessage());
                }
            }
            
            $mail->send();
            
            return [
                'sent' => true,
                'message' => 'Email envoyé avec succès via PHPMailer',
            ];
        } catch (Exception $e) {
            error_log("Erreur PHPMailer: {$mail->ErrorInfo}");
            return [
                'sent' => false,
                'message' => "Erreur PHPMailer: {$mail->ErrorInfo}",
            ];
        }
    }
    
    // Fallback : utiliser la fonction mail() de PHP
    if ($isDevelopment) {
        $htmlBody = create_email_template($message, $isHtml);
        error_log("=== EMAIL SIMULÉ ===");
        error_log("À: $to");
        error_log("Sujet: $subject");
        error_log("Message HTML:\n$htmlBody");
        error_log("===================");
        
        return [
            'sent' => false,
            'message' => "Email simulé en développement. Vérifiez les logs PHP pour voir le contenu.",
        ];
    }
    
    // Créer le template HTML avec le footer pour le fallback mail()
    $htmlBody = create_email_template($message, $isHtml);
    
    // Note: mail() natif ne supporte pas facilement les pièces jointes
    // Si une pièce jointe est fournie, logger un avertissement
    if ($attachment) {
        error_log("ATTENTION: Une pièce jointe PDF était prévue mais ne peut pas être envoyée avec mail() natif. Utilisez PHPMailer pour les pièces jointes.");
    }
    
    $headers = "From: scolarite@univ.ma\r\n";
    $headers .= "Reply-To: scolarite@univ.ma\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $mailSent = @mail($to, $subject, $htmlBody, $headers);
    
    if (!$mailSent) {
        error_log("Email could not be sent to: $to");
    }
    
    return [
        'sent' => $mailSent,
        'message' => $mailSent ? 'Email envoyé avec succès' : 'Erreur lors de l\'envoi (vérifiez la configuration SMTP)',
    ];
}