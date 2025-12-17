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
        <td class="header-gradient" style="background-color: #667eea; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 40px 30px 40px; border-radius: 8px 8px 0 0;">
            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: center;">
                        <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 600; letter-spacing: -0.5px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;">
                            Service Scolarité
                        </h1>
                        <p style="margin: 10px 0 0 0; color: #ffffff; opacity: 0.95; font-size: 14px; font-weight: 400; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;">
                            Plateforme de Gestion des Services Étudiants
                        </p>
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
        <td style="padding: 30px 40px; background-color: #f8f9fa; border-top: 1px solid #e9ecef; border-radius: 0 0 8px 8px;">
            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="text-align: center; padding: 0;">
                        <p style="margin: 0 0 12px 0; color: #6c757d; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; font-size: 13px; line-height: 1.5;">
                            Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.
                        </p>
                        <p style="margin: 0; color: #adb5bd; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; font-size: 12px; line-height: 1.5;">
                            © ' . $currentYear . ' Service Scolarité - Tous droits réservés.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>';
}

/**
 * Détecte et convertit les sections de données structurées en HTML formaté
 */
function format_structured_data(string $text): string
{
    // Détecter les sections avec séparateurs (lignes de tirets ou égal)
    $lines = explode("\n", $text);
    $result = [];
    $inDataSection = false;
    $dataLines = [];
    $sectionTitle = '';
    $pendingTitle = '';
    
    foreach ($lines as $i => $line) {
        $trimmed = trim($line);
        
        // Détecter les séparateurs (lignes avec seulement des tirets, égal, ou caractères répétés)
        if (preg_match('/^[-=]{3,}$/', $trimmed)) {
            // Si on était dans une section de données, la fermer
            if ($inDataSection && !empty($dataLines)) {
                $result[] = format_data_section($sectionTitle, $dataLines);
                $dataLines = [];
                $sectionTitle = '';
            }
            // Le séparateur marque le début d'une nouvelle section
            // Si on avait un titre en attente, l'utiliser
            if (!empty($pendingTitle)) {
                $sectionTitle = $pendingTitle;
                $pendingTitle = '';
            }
            $inDataSection = true;
            continue;
        }
        
        // Si on est dans une section de données
        if ($inDataSection) {
            // Détecter un titre de section (ligne sans ":" ou ligne avec ":" à la fin sans valeur)
            if (empty($dataLines) && !empty($trimmed)) {
                // Si c'est un pattern "Label :" sans valeur, c'est un titre
                if (preg_match('/^([^:]+):\s*$/', $trimmed, $matches)) {
                    $sectionTitle = trim($matches[1]);
                    continue;
                }
                // Si ce n'est pas un pattern label:valeur, c'est peut-être un titre
                if (!preg_match('/^[^:]+:\s*.+$/', $trimmed)) {
                    $sectionTitle = $trimmed;
                    continue;
                }
            }
            
            // Détecter les patterns "Label : valeur"
            if (preg_match('/^([^:]+):\s*(.+)$/', $trimmed, $matches)) {
                $dataLines[] = [
                    'label' => trim($matches[1]),
                    'value' => trim($matches[2])
                ];
            } elseif (!empty($trimmed)) {
                // Si ce n'est pas un pattern label:valeur et qu'on a des données, fermer la section
                if (!empty($dataLines)) {
                    $result[] = format_data_section($sectionTitle, $dataLines);
                    $dataLines = [];
                    $sectionTitle = '';
                }
                $inDataSection = false;
                $result[] = $line;
            }
        } else {
            // Vérifier si la ligne suivante pourrait être un séparateur
            // Si cette ligne ressemble à un titre (se termine par ":"), la garder en attente
            if (preg_match('/^[^:]+:\s*$/', $trimmed)) {
                $pendingTitle = trim(rtrim($trimmed, ':'));
            } else {
                $pendingTitle = '';
            }
            $result[] = $line;
        }
    }
    
    // Fermer la dernière section si elle existe
    if ($inDataSection && !empty($dataLines)) {
        $result[] = format_data_section($sectionTitle, $dataLines);
    }
    
    return implode("\n", $result);
}

/**
 * Formate une section de données en HTML structuré
 */
function format_data_section(string $title, array $dataLines): string
{
    if (empty($dataLines)) {
        return '';
    }
    
    $html = '<div style="margin: 24px 0; background-color: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; overflow: hidden;">';
    
    // Titre de la section
    if (!empty($title)) {
        $html .= '<div style="background-color: #667eea; padding: 16px 20px; border-bottom: 1px solid #e9ecef;">';
        $html .= '<h3 style="margin: 0; color: #ffffff; font-size: 16px; font-weight: 600; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>';
        $html .= '</div>';
    }
    
    // Tableau des données
    $html .= '<table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #ffffff;">';
    
    foreach ($dataLines as $index => $data) {
        $bgColor = ($index % 2 === 0) ? '#ffffff' : '#f8f9fa';
        $html .= '<tr style="background-color: ' . $bgColor . ';">';
        $html .= '<td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef; font-weight: 600; color: #4a5568; width: 40%; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; font-size: 14px;">';
        $html .= htmlspecialchars($data['label'], ENT_QUOTES, 'UTF-8');
        $html .= '</td>';
        $html .= '<td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef; color: #2d3748; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; font-size: 14px;">';
        $html .= htmlspecialchars($data['value'], ENT_QUOTES, 'UTF-8');
        $html .= '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Convertit un message texte en HTML avec mise en forme
 */
function convert_text_to_html(string $text): string
{
    // D'abord, formater les données structurées (cela retourne du HTML)
    $text = format_structured_data($text);
    
    // Séparer le contenu HTML déjà formaté du texte brut
    // Utiliser un marqueur temporaire pour protéger le HTML
    $marker = '___HTML_SECTION___';
    $htmlSections = [];
    $counter = 0;
    
    // Remplacer les sections HTML par des marqueurs
    $text = preg_replace_callback('/(<div[^>]*>.*?<\/div>)/s', function($matches) use (&$htmlSections, &$counter, $marker) {
        $key = $marker . $counter . $marker;
        $htmlSections[$counter] = $matches[1];
        $counter++;
        return $key;
    }, $text);
    
    // Maintenant, convertir le texte restant en HTML
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Restaurer les sections HTML
    foreach ($htmlSections as $index => $htmlSection) {
        $text = str_replace($marker . $index . $marker, $htmlSection, $text);
    }
    
    // Convertir les sauts de ligne doubles en paragraphes (mais pas dans les sections HTML)
    $parts = preg_split('/(<div[^>]*>.*?<\/div>)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $result = [];
    
    foreach ($parts as $part) {
        if (preg_match('/^<div/', $part)) {
            // C'est du HTML déjà formaté, le garder tel quel
            $result[] = $part;
        } else {
            // C'est du texte, le formater
            $part = trim($part);
            if (empty($part)) {
                continue;
            }
            
            // Convertir les sauts de ligne doubles en séparateurs de paragraphes
            $paragraphs = preg_split('/\n\s*\n/', $part);
            $formattedParagraphs = [];
            
            foreach ($paragraphs as $para) {
                $para = trim($para);
                if (!empty($para)) {
                    // Convertir les sauts de ligne simples en <br>
                    $para = nl2br($para);
                    // Appliquer les styles de formatage markdown
                    $para = preg_replace('/\*\*(.+?)\*\*/', '<strong style="color: #2d3748; font-weight: 600;">$1</strong>', $para);
                    $para = preg_replace('/\*(.+?)\*/', '<em style="color: #4a5568; font-style: italic;">$1</em>', $para);
                    // Mettre le paragraphe dans un <p> stylé
                    $formattedParagraphs[] = '<p style="margin: 0 0 16px 0; color: #2d3748; line-height: 1.75;">' . $para . '</p>';
                }
            }
            
            if (!empty($formattedParagraphs)) {
                $result[] = implode('', $formattedParagraphs);
            } elseif (!empty($part)) {
                // Si pas de paragraphes, traiter comme texte simple
                $part = nl2br($part);
                $part = preg_replace('/\*\*(.+?)\*\*/', '<strong style="color: #2d3748; font-weight: 600;">$1</strong>', $part);
                $part = preg_replace('/\*(.+?)\*/', '<em style="color: #4a5568; font-style: italic;">$1</em>', $part);
                $result[] = '<p style="margin: 0 0 16px 0; color: #2d3748; line-height: 1.75;">' . $part . '</p>';
            }
        }
    }
    
    $output = implode('', $result);
    
    // Si aucun contenu n'a été généré, retourner un paragraphe vide
    if (empty(trim(strip_tags($output)))) {
        return '<p style="margin: 0 0 16px 0; color: #2d3748; line-height: 1.75;">&nbsp;</p>';
    }
    
    return $output;
}

/**
 * Génère un template HTML complet pour l'email
 */
function create_email_template(string $content, bool $isHtml = false): string
{
    // Si le contenu est déjà en HTML, l'utiliser tel quel, sinon le convertir
    $bodyContent = $isHtml ? $content : convert_text_to_html($content);
    
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
    <style type="text/css">
        /* Styles pour améliorer la compatibilité email */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        /* Styles pour les liens */
        a {
            color: #667eea;
            text-decoration: none;
        }
        a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        /* Styles pour les listes */
        ul, ol {
            margin: 0 0 16px 0;
            padding-left: 24px;
        }
        li {
            margin: 0 0 8px 0;
            color: #2d3748;
            line-height: 1.75;
        }
        /* Styles pour les tableaux */
        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        /* Styles pour les tableaux dans le contenu */
        .email-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .email-content th, .email-content td {
            padding: 12px;
            border: 1px solid #e9ecef;
            text-align: left;
        }
        .email-content th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2d3748;
        }
        /* Styles pour les citations */
        blockquote {
            margin: 16px 0;
            padding: 12px 16px;
            border-left: 4px solid #667eea;
            background-color: #f7fafc;
            color: #4a5568;
        }
    </style>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, a { font-family: Arial, sans-serif !important; }
        .header-gradient {
            background: #667eea !important;
        }
    </style>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f7fa; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
    <!-- Wrapper -->
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f5f7fa; padding: 20px 0;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <!-- Main Container -->
                <table role="presentation" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); overflow: hidden; border: 1px solid #e9ecef;">
                    ' . $header . '
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 40px 30px 40px; background-color: #ffffff;">
                            <div class="email-content" style="color: #2d3748; font-size: 16px; line-height: 1.75; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;">
                                ' . $bodyContent . '
                            </div>
                        </td>
                    </tr>
                    ' . $footer . '
                </table>
                <!-- Spacer -->
                <table role="presentation" style="width: 100%; max-width: 600px; margin: 20px auto 0;">
                    <tr>
                        <td style="text-align: center; padding: 20px 0;">
                            <p style="margin: 0; color: #a0aec0; font-size: 12px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;">
                                Si vous avez des questions, contactez le service scolarité.
                            </p>
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
    
    $headers = "From: scolarite@uae.ma\r\n";
    $headers .= "Reply-To: scolarite@uae.ac.ma\r\n";
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