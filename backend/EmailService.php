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
 * Génère le header HTML pour les emails
 */
function get_email_header(): string
{
    return '
    <tr>
        <td style="background-color: #f8f9fa; padding: 30px 40px; border-bottom: 1px solid #e9ecef;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                <tr>
                    <td align="center" style="padding: 0;">
                        <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                            <tr>
                                <td align="center" style="padding: 0 0 8px 0;">
                                    <span style="color: #212529; font-size: 24px; font-weight: 600; font-family: Arial, sans-serif; line-height: 1.2;">
                                        Service Scolarité
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding: 0;">
                                    <span style="color: #6c757d; font-size: 14px; font-weight: 400; font-family: Arial, sans-serif; line-height: 1.4;">
                                        Plateforme de Gestion des Services Étudiants
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>';
}

/**
 * Génère le footer HTML pour les emails
 */
function get_email_footer(): string
{
    $currentYear = date('Y');
    return '
    <tr>
        <td style="padding: 25px 40px; background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                <tr>
                    <td align="center" style="padding: 0 0 10px 0;">
                        <span style="color: #6c757d; font-size: 13px; font-family: Arial, sans-serif; line-height: 1.6;">
                            Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.
                        </span>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding: 0;">
                        <span style="color: #adb5bd; font-size: 12px; font-family: Arial, sans-serif; line-height: 1.5;">
                            © ' . $currentYear . ' Service Scolarité - Tous droits réservés.
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>';
}

/**
 * Crée une boîte bien visible pour les réponses des administrateurs
 */
function format_admin_response(string $response): string
{
    $response = htmlspecialchars(trim($response), ENT_QUOTES, 'UTF-8');
    $response = nl2br($response);
    
    return '
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 24px 0;">
        <tr>
            <td style="background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 20px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                    <tr>
                        <td style="padding: 0 0 12px 0;">
                            <span style="color: #495057; font-size: 14px; font-weight: 600; font-family: Arial, sans-serif;">
                                Réponse du Service Scolarité :
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0;">
                            <span style="color: #212529; font-size: 15px; line-height: 1.7; font-family: Arial, sans-serif;">
                                ' . $response . '
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';
}

/**
 * Formate un message avec une réponse d'administrateur en HTML
 * Utilisez cette fonction pour créer des emails avec des réponses bien visibles
 */
function format_message_with_response(string $introText, string $responseText, string $closingText = ''): string
{
    $intro = convert_text_to_html($introText);
    $response = format_admin_response($responseText);
    $closing = !empty($closingText) ? convert_text_to_html($closingText) : '';
    
    return $intro . $response . $closing;
}

/**
 * Crée un tableau HTML pour afficher les détails d'une demande
 * Utilise les couleurs de la plateforme (bleu)
 */
function format_request_details_table(array $details): string
{
    // Couleurs de la plateforme (dérivées du bleu)
    $headerBg = '#1a4a6b'; // navy (205 75% 22%)
    $headerText = '#ffffff';
    $rowBg = '#f8f9fa';
    $rowAltBg = '#ffffff';
    $borderColor = '#c5d4e0'; // blue-pale
    $labelColor = '#0d2a3d'; // navy-dark
    $valueColor = '#212529';
    
    $html = '<table width="500" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 20px 0; border: 1px solid ' . $borderColor . ';">';
    
    // En-tête du tableau
    $html .= '<tr>';
    $html .= '<td style="background-color: ' . $headerBg . '; padding: 12px 16px; border-bottom: 2px solid ' . $borderColor . ';">';
    $html .= '<span style="color: ' . $headerText . '; font-size: 15px; font-weight: 600; font-family: Arial, sans-serif;">Détails de votre demande</span>';
    $html .= '</td>';
    $html .= '</tr>';
    
    // Lignes de données
    $isAlt = false;
    foreach ($details as $label => $value) {
        $bgColor = $isAlt ? $rowAltBg : $rowBg;
        $html .= '<tr>';
        $html .= '<td style="background-color: ' . $bgColor . '; padding: 12px 16px; border-bottom: 1px solid ' . $borderColor . ';">';
        $html .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">';
        $html .= '<tr>';
        $html .= '<td width="40%" style="padding: 0 12px 0 0; vertical-align: top; border-right: 1px solid ' . $borderColor . ';">';
        $html .= '<span style="color: ' . $labelColor . '; font-size: 14px; font-weight: 600; font-family: Arial, sans-serif;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
        $html .= '</td>';
        $html .= '<td width="60%" style="padding: 0 0 0 12px; vertical-align: top;">';
        $html .= '<span style="color: ' . $valueColor . '; font-size: 14px; font-family: Arial, sans-serif;">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</span>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</td>';
        $html .= '</tr>';
        $isAlt = !$isAlt;
    }
    
    $html .= '</table>';
    
    return $html;
}

/**
 * Convertit un message texte en HTML avec mise en forme
 */
function convert_text_to_html(string $text): string
{
    // Détecter si le message contient une réponse d'administrateur
    // Patterns courants pour identifier les réponses
    $responsePatterns = [
        '/Notre réponse\s*:\s*\n(.+?)(?=\n\n|$)/s',
        '/Raisons du refus\s*:\s*\n(.+?)(?=\n\n|$)/s',
        '/Réponse\s*:\s*\n(.+?)(?=\n\n|$)/s',
    ];
    
    $hasResponse = false;
    $responseText = '';
    $mainText = $text;
    
    // Chercher une réponse dans le texte
    foreach ($responsePatterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $hasResponse = true;
            $responseText = trim($matches[1]);
            // Retirer la réponse du texte principal (y compris le label)
            $mainText = preg_replace($pattern, '', $text);
            // Nettoyer les sauts de ligne multiples
            $mainText = preg_replace('/\n{3,}/', "\n\n", $mainText);
            break;
        }
    }
    
    // Échapper le HTML pour la sécurité
    $html = htmlspecialchars($mainText, ENT_QUOTES, 'UTF-8');
    
    // Convertir les sauts de ligne doubles en paragraphes
    $paragraphs = preg_split('/\n\s*\n/', $html);
    $formattedParagraphs = [];
    
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if (!empty($para)) {
            // Convertir les sauts de ligne simples en <br>
            $para = nl2br($para);
            // Appliquer les styles de formatage markdown
            $para = preg_replace('/\*\*(.+?)\*\*/', '<strong style="color: #212529; font-weight: 600;">$1</strong>', $para);
            $para = preg_replace('/\*(.+?)\*/', '<em style="color: #495057; font-style: italic;">$1</em>', $para);
            // Mettre le paragraphe dans un tableau (compatible email)
            $formattedParagraphs[] = '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0 0 16px 0;"><tr><td style="padding: 0; color: #212529; line-height: 1.7; font-size: 15px; font-family: Arial, sans-serif;">' . $para . '</td></tr></table>';
        }
    }
    
    // Si aucun paragraphe n'a été créé (texte sans sauts de ligne doubles), traiter comme un seul paragraphe
    if (empty($formattedParagraphs)) {
        $html = nl2br($html);
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong style="color: #212529; font-weight: 600;">$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em style="color: #495057; font-style: italic;">$1</em>', $html);
        $formattedParagraphs[] = '<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0 0 16px 0;"><tr><td style="padding: 0; color: #212529; line-height: 1.7; font-size: 15px; font-family: Arial, sans-serif;">' . $html . '</td></tr></table>';
    }
    
    $result = implode('', $formattedParagraphs);
    
    // Ajouter la boîte de réponse si une réponse a été détectée
    if ($hasResponse && !empty($responseText)) {
        $result .= format_admin_response($responseText);
    }
    
    return $result;
}

/**
 * Génère un template HTML complet pour l'email
 */
function create_email_template(string $content, bool $isHtml = false): string
{
    // Si le contenu est déjà en HTML (contient des balises HTML), l'utiliser tel quel
    // Sinon, le convertir même si isHtml = true (car les messages sont souvent en texte brut)
    $isActuallyHtml = $isHtml && (strip_tags($content) !== $content);
    $bodyContent = $isActuallyHtml ? $content : convert_text_to_html($content);
    
    $header = get_email_header();
    $footer = get_email_footer();
    
    return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Email Service Scolarité</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td { font-family: Arial, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5;">
    <!-- Table principale centrée - 600px -->
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!-- Container principal - largeur fixe 600px -->
                <table width="600" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; background-color: #ffffff; border: 1px solid #dee2e6;">
                    ' . $header . '
                    <!-- Contenu principal -->
                    <tr>
                        <td style="padding: 35px 40px; background-color: #ffffff;">
                            ' . $bodyContent . '
                        </td>
                    </tr>
                    ' . $footer . '
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