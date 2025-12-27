<?php

declare(strict_types=1);

require __DIR__ . '/Database.php';
require __DIR__ . '/helpers.php';

// Charger les dépendances Composer si disponibles
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Récupérer le chemin de la requête
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';

// Normaliser le chemin pour gérer différents formats d'URL
// Exemples: /Service-scolarite/backend/api/health ou /api/health
$apiPos = strpos($path, '/api');
if ($apiPos !== false) {
    // Normaliser l'URI pour que le routeur commence toujours par /api
    $path = substr($path, $apiPos);
} elseif (!str_starts_with($path, '/api')) {
    // Si le chemin ne commence pas par /api, essayer de le trouver autrement
    // Cela peut arriver si .htaccess redirige déjà vers index.php
    // Dans ce cas, vérifier si c'est une requête API basée sur le script name
    if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], 'index.php') !== false) {
        // Le chemin peut être dans PATH_INFO ou REQUEST_URI
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        if ($pathInfo && str_starts_with($pathInfo, '/api')) {
            $path = $pathInfo;
        } elseif ($path && str_starts_with($path, '/api')) {
            // Le chemin est déjà correct
        } else {
            // Essayer de construire le chemin depuis REQUEST_URI
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $queryPos = strpos($requestUri, '?');
            if ($queryPos !== false) {
                $requestUri = substr($requestUri, 0, $queryPos);
            }
            $apiPos = strpos($requestUri, '/api');
            if ($apiPos !== false) {
                $path = substr($requestUri, $apiPos);
            }
        }
    }
}

// S'assurer que le chemin commence par /api
if (!str_starts_with($path, '/api')) {
    // Si ce n'est pas une route API, retourner 404
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Route not found']);
    exit;
}

// Basic CORS for local dev (Vite on 5173)
// Ne pas envoyer les headers CORS pour les téléchargements de PDF
$isPdfDownload = preg_match('#^/api/requests/([^/]+)/download$#', $path);
if (!$isPdfDownload) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (\Throwable $e) {
    error_log('Database connection error: ' . $e->getMessage());
    send_error('Erreur de connexion à la base de données. Vérifiez backend/config.php ou vos variables d\'environnement.', 500);
}
$method = $_SERVER['REQUEST_METHOD'];

// Small router
switch (true) {
    case $path === '/api/health':
        send_json(['status' => 'ok', 'timestamp' => time()]);

    case $path === '/api/requests' && $method === 'GET':
        handle_get_requests($pdo);
        break;

    case $path === '/api/requests' && $method === 'POST':
        handle_create_request($pdo);
        break;

    case preg_match('#^/api/requests/([^/]+)/status$#', $path, $matches) && in_array($method, ['POST', 'PATCH'], true):
        handle_update_request_status($pdo, $matches[1]);
        break;

    case preg_match('#^/api/requests/([^/]+)/send-email$#', $path, $matches) && $method === 'POST':
        handle_send_email($pdo, $matches[1]);
        break;

    case preg_match('#^/api/requests/([^/]+)/download$#', $path, $matches) && $method === 'GET':
        handle_download_document($pdo, $matches[1]);
        break;

    case $path === '/api/complaints' && $method === 'GET':
        handle_get_complaints($pdo);
        break;

    case $path === '/api/complaints' && $method === 'POST':
        handle_create_complaint($pdo);
        break;

    case preg_match('#^/api/complaints/([^/]+)/response$#', $path, $matches) && $method === 'POST':
        handle_respond_to_complaint($pdo, $matches[1]);
        break;

    case $path === '/api/students/validate' && $method === 'POST':
        handle_validate_student($pdo);
        break;

    case $path === '/api/login' && $method === 'POST':
        handle_login($pdo);
        break;

    case $path === '/api/academic-years' && $method === 'GET':
        handle_get_academic_years($pdo);
        break;

    case $path === '/api/semesters' && $method === 'GET':
        handle_get_semesters($pdo);
        break;

    case $path === '/api/supervisors' && $method === 'GET':
        handle_get_supervisors($pdo);
        break;

    case $path === '/api/students/demands' && $method === 'POST':
        handle_get_student_demands($pdo);
        break;

    case preg_match('#^/api/complaints/([^/]+)$#', $path, $matches) && $method === 'GET':
        handle_get_complaint_details($pdo, $matches[1]);
        break;

    case $path === '/api/students/history' && $method === 'POST':
        handle_get_student_history($pdo);
        break;

    default:
        send_error('Route not found', 404);
}

function handle_get_requests(PDO $pdo): void
{
    try {
        $sql = <<<SQL
SELECT 
  d.id_demande,
  d.numero_reference,
  d.type_document,
  d.statut,
  d.date_demande,
  d.id_etudiant,
  e.email AS etu_email,
  e.numero_apogee AS etu_apogee,
  e.cin AS etu_cin,
  e.nom AS etu_nom,
  e.prenom AS etu_prenom,
  ar.annee_universitaire AS ar_annee,
  rn.annee_universitaire AS rn_annee,
  rn.semestre AS rn_semestre,
  cs.nom_entreprise,
  cs.adresse_entreprise,
  cs.sujet_stage,
  cs.date_debut_stage,
  cs.date_fin_stage,
  cs.email_responsable_entreprise,
  cs.nom_responsable_entreprise,
  cs.telephone_responsable_entreprise,
  p.nom AS prof_nom,
  p.prenom AS prof_prenom
FROM demandes d
JOIN etudiants e ON e.id_etudiant = d.id_etudiant
LEFT JOIN attestations_reussite ar ON ar.id_demande = d.id_demande
LEFT JOIN releves_notes rn ON rn.id_demande = d.id_demande
LEFT JOIN conventions_stage cs ON cs.id_demande = d.id_demande
LEFT JOIN professeur p ON p.id_prof = cs.id_prof_encadrant
ORDER BY d.date_demande DESC
SQL;

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log('Erreur lors de la récupération des demandes: ' . $e->getMessage());
        send_error('Erreur lors de la récupération des demandes', 500);
    }

    $payload = array_map(function ($row) {
        $documentType = map_document_type($row['type_document']);
        // Si la demande est traitée, utiliser la date de demande comme date de traitement
        // (ou une date calculée si disponible)
        $processedAt = null;
        if ($row['statut'] === 'traite') {
            // Utiliser la date de demande comme date de traitement approximative
            // En production, vous pourriez avoir une colonne date_traitement
            $processedAt = $row['date_demande'];
        }
        // Format dates properly - ensure ISO 8601 format with time
        $createdAt = $row['date_demande'];
        if ($createdAt && !str_contains($createdAt, 'T') && !str_contains($createdAt, ' ')) {
            // If date is in YYYY-MM-DD format, add time
            $createdAt = $createdAt . ' 00:00:00';
        }
        $processedAtFormatted = $processedAt;
        if ($processedAtFormatted && !str_contains($processedAtFormatted, 'T') && !str_contains($processedAtFormatted, ' ')) {
            $processedAtFormatted = $processedAtFormatted . ' 00:00:00';
        }
        
        return [
            'id' => $row['id_demande'],
            'referenceNumber' => $row['numero_reference'],
            'studentId' => $row['id_etudiant'],
            'documentType' => $documentType,
            'status' => map_status($row['statut']),
            'createdAt' => $createdAt,
            'processedAt' => $processedAtFormatted,
            'academicYear' => $row['ar_annee'] ?? $row['rn_annee'],
            'semester' => $row['rn_semestre'],
            'companyName' => $row['nom_entreprise'],
            'companyAddress' => $row['adresse_entreprise'],
            'supervisorName' => $row['nom_responsable_entreprise'],
            'supervisorEmail' => $row['email_responsable_entreprise'],
            'supervisorPhone' => $row['telephone_responsable_entreprise'],
            'stageStartDate' => $row['date_debut_stage'],
            'stageEndDate' => $row['date_fin_stage'],
            'stageSubject' => $row['sujet_stage'],
            'academicSupervisor' => trim(($row['prof_prenom'] ?? '') . ' ' . ($row['prof_nom'] ?? '')) ?: null,
            'student' => [
                'id' => $row['id_etudiant'],
                'email' => $row['etu_email'],
                'apogee' => $row['etu_apogee'],
                'cin' => $row['etu_cin'],
                'firstName' => $row['etu_prenom'],
                'lastName' => $row['etu_nom'],
            ],
        ];
    }, $rows);

    send_json($payload);
}

function handle_create_request(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error('Invalid JSON in request body', 400);
    }
    
    // Validation des champs obligatoires
    if (!isset($input['studentId']) || !isset($input['documentType'])) {
        send_error('Missing required fields: studentId, documentType', 400);
    }

    // Vérifier que l'étudiant existe
    $stmt = $pdo->prepare('SELECT id_etudiant FROM etudiants WHERE id_etudiant = :id');
    $stmt->execute([':id' => $input['studentId']]);
    if (!$stmt->fetch()) {
        send_error('Student not found', 404);
    }

    // Générer un numéro de référence si non fourni
    $referenceNumber = $input['referenceNumber'] ?? null;
    if (!$referenceNumber) {
        $year = date('Y');
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM demandes WHERE numero_reference LIKE :pattern");
        $stmt->execute([':pattern' => "REQ-$year-%"]);
        $count = $stmt->fetch()['count'] ?? 0;
        $referenceNumber = 'REQ-' . $year . '-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
    }

    // Générer un ID unique pour la demande (format VARCHAR(10) comme dans la DB)
    // Format: D + timestamp court + random 3 chiffres, mais limité à 10 caractères max
    $timestamp = substr((string)time(), -6); // Prendre les 6 derniers chiffres du timestamp
    $random = rand(100, 999);
    $idDemande = 'D' . $timestamp . substr((string)$random, -2); // Total: 1 + 6 + 2 = 9 caractères max
    // S'assurer que l'ID ne dépasse pas 10 caractères
    $idDemande = substr($idDemande, 0, 10);
    
    // Mapper le type de document du frontend vers la base de données
    $documentTypeMap = [
        'attestation_scolarite' => 'attestations_scolarite',
        'attestation_reussite' => 'attestations_reussite',
        'releve_notes' => 'releves_notes',
        'convention_stage' => 'conventions_stage',
    ];
    
    $dbDocumentType = $documentTypeMap[$input['documentType']] ?? null;
    if (!$dbDocumentType) {
        send_error('Invalid document type', 400);
    }

    // Insérer la demande principale
    try {
        $stmt = $pdo->prepare('
            INSERT INTO demandes (id_demande, numero_reference, type_document, statut, date_demande, id_etudiant)
            VALUES (:id, :ref, :type, :statut, CURDATE(), :studentId)
        ');
        $stmt->execute([
            ':id' => $idDemande,
            ':ref' => $referenceNumber,
            ':type' => $dbDocumentType,
            ':statut' => 'en attente',
            ':studentId' => $input['studentId'],
        ]);
    } catch (\PDOException $e) {
        error_log('Erreur lors de l\'insertion de la demande: ' . $e->getMessage());
        // Si l'erreur est due à un ID ou référence déjà existant, générer un nouveau
        if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
            $timestamp = substr((string)time(), -6);
            $random = rand(100, 999);
            $idDemande = 'D' . $timestamp . substr((string)$random, -2);
            $idDemande = substr($idDemande, 0, 10);
            
            // Régénérer aussi le numéro de référence si nécessaire
            $year = date('Y');
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM demandes WHERE numero_reference LIKE :pattern");
            $stmt->execute([':pattern' => "REQ-$year-%"]);
            $count = $stmt->fetch()['count'] ?? 0;
            $referenceNumber = 'REQ-' . $year . '-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
            
            // Réessayer l'insertion
            $stmt = $pdo->prepare('
                INSERT INTO demandes (id_demande, numero_reference, type_document, statut, date_demande, id_etudiant)
                VALUES (:id, :ref, :type, :statut, CURDATE(), :studentId)
            ');
            $stmt->execute([
                ':id' => $idDemande,
                ':ref' => $referenceNumber,
                ':type' => $dbDocumentType,
                ':statut' => 'en attente',
                ':studentId' => $input['studentId'],
            ]);
        } else {
            throw $e;
        }
    }

    // Insérer les données spécifiques selon le type de document
    // Note: attestation_scolarite n'a pas de table dédiée, les données sont dans demandes uniquement
    
    if ($input['documentType'] === 'attestation_reussite') {
        // Générer un ID unique pour l'attestation (format VARCHAR(10))
        $timestamp = substr((string)time(), -6);
        $random = rand(100, 999);
        $idAttestation = 'AR' . $timestamp . substr((string)$random, -1);
        $idAttestation = substr($idAttestation, 0, 10);
        
        // S'assurer que l'année universitaire est au bon format (YYYY-YYYY)
        $academicYear = null;
        if (isset($input['academicYear']) && !empty(trim($input['academicYear']))) {
            $academicYear = trim($input['academicYear']);
            // Si le format est déjà YYYY-YYYY, l'utiliser tel quel
            // Sinon, essayer de le convertir
            if (!preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
                // Si c'est juste YYYY, créer YYYY-YYYY+1
                if (preg_match('/^\d{4}$/', $academicYear)) {
                    $academicYear = $academicYear . '-' . ((int)$academicYear + 1);
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare('
                INSERT INTO attestations_reussite (id_attestation, annee_universitaire, id_demande)
                VALUES (:id, :annee, :demande)
            ');
            $stmt->execute([
                ':id' => $idAttestation,
                ':annee' => $academicYear,
                ':demande' => $idDemande,
            ]);
        } catch (\PDOException $e) {
            error_log('Erreur lors de l\'insertion de l\'attestation de réussite: ' . $e->getMessage());
            // Ne pas échouer complètement, continuer sans l'attestation
        }
    }

    if ($input['documentType'] === 'releve_notes') {
        // Générer un ID unique pour le relevé (format VARCHAR(10))
        $timestamp = substr((string)time(), -6);
        $random = rand(100, 999);
        $idReleve = 'RN' . $timestamp . substr((string)$random, -1);
        $idReleve = substr($idReleve, 0, 10);
        
        // Formater l'année universitaire si fournie
        $academicYear = null;
        if (isset($input['academicYear']) && !empty(trim($input['academicYear']))) {
            $academicYear = trim($input['academicYear']);
            // S'assurer que le format est YYYY-YYYY
            if (!preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
                if (preg_match('/^\d{4}$/', $academicYear)) {
                    $academicYear = $academicYear . '-' . ((int)$academicYear + 1);
                }
            }
        }
        
        // Formater le semestre si fourni
        $semester = null;
        if (isset($input['semester']) && !empty(trim($input['semester']))) {
            $semester = trim($input['semester']);
        }
        
        try {
            $stmt = $pdo->prepare('
                INSERT INTO releves_notes (id_releve, annee_universitaire, semestre, id_demande)
                VALUES (:id, :annee, :semestre, :demande)
            ');
            $stmt->execute([
                ':id' => $idReleve,
                ':annee' => $academicYear,
                ':semestre' => $semester,
                ':demande' => $idDemande,
            ]);
        } catch (\PDOException $e) {
            error_log('Erreur lors de l\'insertion du relevé de notes: ' . $e->getMessage());
            // Ne pas échouer complètement, continuer sans le relevé
        }
    }

    if ($input['documentType'] === 'convention_stage') {
        // Récupérer l'ID du professeur encadrant si fourni
        $idProf = null;
        if (isset($input['academicSupervisor'])) {
            $supervisorName = trim($input['academicSupervisor']);
            if ($supervisorName) {
                // Enlever les préfixes comme "Dr.", "Dr", "Pr.", "Pr", "Prof.", "Prof"
                $supervisorName = preg_replace('/^(Dr\.?|Pr\.?|Prof\.?)\s+/i', '', $supervisorName);
                
                // Séparer le prénom et le nom
                $parts = explode(' ', $supervisorName, 2);
                $prenom = trim($parts[0] ?? '');
                $nom = trim($parts[1] ?? '');
                
                if ($prenom && $nom) {
                    // Chercher d'abord avec prénom et nom exacts
                    $stmt = $pdo->prepare('SELECT id_prof FROM professeur WHERE prenom = :prenom AND nom = :nom AND est_encadrant = 1 LIMIT 1');
                    $stmt->execute([':prenom' => $prenom, ':nom' => $nom]);
                    $prof = $stmt->fetch();
                    $idProf = $prof['id_prof'] ?? null;
                    
                    // Si pas trouvé, chercher seulement par nom (au cas où le prénom serait incomplet)
                    if (!$idProf && $nom) {
                        $stmt = $pdo->prepare('SELECT id_prof FROM professeur WHERE nom = :nom AND est_encadrant = 1 LIMIT 1');
                        $stmt->execute([':nom' => $nom]);
                        $prof = $stmt->fetch();
                        $idProf = $prof['id_prof'] ?? null;
                    }
                }
            }
        }

        // Si aucun prof trouvé, prendre le premier encadrant disponible
        if (!$idProf) {
            $stmt = $pdo->query('SELECT id_prof FROM professeur WHERE est_encadrant = 1 LIMIT 1');
            $prof = $stmt->fetch();
            $idProf = $prof['id_prof'] ?? null;
            
            // Si toujours pas de prof, utiliser un ID par défaut (mais cela devrait rarement arriver)
            if (!$idProf) {
                error_log('Aucun professeur encadrant trouvé dans la base de données');
                $idProf = 'P001'; // Utiliser un ID qui existe dans les données de test
            }
        }

        // Générer un ID unique pour la convention (format VARCHAR(10))
        $timestamp = substr((string)time(), -6);
        $random = rand(100, 999);
        $idConvention = 'CS' . $timestamp . substr((string)$random, -1);
        $idConvention = substr($idConvention, 0, 10);
        
        try {
            $stmt = $pdo->prepare('
                INSERT INTO conventions_stage (
                    id_convention, sujet_stage, date_debut_stage, date_fin_stage,
                    nom_entreprise, adresse_entreprise, email_responsable_entreprise,
                    nom_responsable_entreprise, telephone_responsable_entreprise,
                    id_demande, id_prof_encadrant
                ) VALUES (
                    :id, :sujet, :debut, :fin, :nom_ent, :adresse_ent,
                    :email_resp, :nom_resp, :tel_resp, :demande, :prof
                )
            ');
            $stmt->execute([
                ':id' => $idConvention,
                ':sujet' => $input['stageSubject'] ?? null,
                ':debut' => $input['stageStartDate'] ?? null,
                ':fin' => $input['stageEndDate'] ?? null,
                ':nom_ent' => $input['companyName'] ?? null,
                ':adresse_ent' => $input['companyAddress'] ?? null,
                ':email_resp' => $input['supervisorEmail'] ?? null,
                ':nom_resp' => $input['supervisorName'] ?? null,
                ':tel_resp' => $input['supervisorPhone'] ?? null,
                ':demande' => $idDemande,
                ':prof' => $idProf,
            ]);
        } catch (\PDOException $e) {
            error_log('Erreur lors de l\'insertion de la convention de stage: ' . $e->getMessage());
            // Si l'erreur est due à une contrainte de clé étrangère, essayer avec un prof par défaut
            if (strpos($e->getMessage(), 'FOREIGN KEY') !== false && $idProf !== 'P001') {
                try {
                    $stmt->execute([
                        ':id' => $idConvention,
                        ':sujet' => $input['stageSubject'] ?? null,
                        ':debut' => $input['stageStartDate'] ?? null,
                        ':fin' => $input['stageEndDate'] ?? null,
                        ':nom_ent' => $input['companyName'] ?? null,
                        ':adresse_ent' => $input['companyAddress'] ?? null,
                        ':email_resp' => $input['supervisorEmail'] ?? null,
                        ':nom_resp' => $input['supervisorName'] ?? null,
                        ':tel_resp' => $input['supervisorPhone'] ?? null,
                        ':demande' => $idDemande,
                        ':prof' => 'P001', // Prof par défaut
                    ]);
                } catch (\PDOException $e2) {
                    error_log('Erreur lors de l\'insertion avec prof par défaut: ' . $e2->getMessage());
                    throw $e2;
                }
            } else {
                throw $e;
            }
        }
    }

    // Envoyer un email de confirmation avec les détails de la demande
    try {
        // Récupérer les informations de l'étudiant
        $stmt = $pdo->prepare('
            SELECT email, prenom, nom, numero_apogee 
            FROM etudiants 
            WHERE id_etudiant = :id
        ');
        $stmt->execute([':id' => $input['studentId']]);
        $student = $stmt->fetch();
        
        if ($student) {
            $documentTypeLabels = [
                'attestations_scolarite' => 'Attestation de scolarité',
                'attestations_reussite' => 'Attestation de réussite',
                'releves_notes' => 'Relevé de notes',
                'conventions_stage' => 'Convention de stage',
            ];
            $docLabel = $documentTypeLabels[$dbDocumentType] ?? 'Document';
            
            // Construire le sujet et le message de l'email
            $subject = "Confirmation de votre demande de document - " . $referenceNumber;
            
            // Préparer les détails pour le tableau
            $details = [
                'Numéro de référence' => $referenceNumber,
                'Type de document' => $docLabel,
                'Date de la demande' => date('d/m/Y'),
            ];
            
            // Ajouter les détails spécifiques selon le type de document
            if ($input['documentType'] === 'attestation_reussite' && isset($input['academicYear'])) {
                $details['Année universitaire'] = $input['academicYear'];
            }
            if ($input['documentType'] === 'releve_notes') {
                if (isset($input['academicYear'])) {
                    $details['Année universitaire'] = $input['academicYear'];
                }
                if (isset($input['semester'])) {
                    $details['Semestre'] = $input['semester'];
                }
            }
            if ($input['documentType'] === 'convention_stage') {
                if (isset($input['companyName'])) {
                    $details['Entreprise'] = $input['companyName'];
                }
                if (isset($input['stageSubject'])) {
                    $details['Sujet du stage'] = $input['stageSubject'];
                }
                if (isset($input['stageStartDate']) && isset($input['stageEndDate'])) {
                    $details['Période'] = date('d/m/Y', strtotime($input['stageStartDate'])) . ' - ' . date('d/m/Y', strtotime($input['stageEndDate']));
                }
            }
            
            // Construire le message avec le tableau HTML
            $message = "Bonjour " . $student['prenom'] . " " . $student['nom'] . ",\n\n";
            $message .= "Nous avons bien reçu votre demande de " . strtolower($docLabel) . ".\n\n";
            
            // Charger le service d'email pour utiliser la fonction de formatage
            $emailServiceFile = __DIR__ . '/EmailService.php';
            if (file_exists($emailServiceFile)) {
                require_once $emailServiceFile;
                // Créer le message avec le tableau HTML
                $introText = "Bonjour " . $student['prenom'] . " " . $student['nom'] . ",\n\n";
                $introText .= "Nous avons bien reçu votre demande de " . strtolower($docLabel) . ".\n\n";
                $detailsTable = format_request_details_table($details);
                $closingText = "Votre demande est en cours de traitement. Nous vous tiendrons informé dès que votre document sera prêt.\n\n";
                $closingText .= "Vous pouvez utiliser le numéro de référence ci-dessus pour suivre l'état de votre demande.\n\n";
                $closingText .= "Cordialement,\nLe Service de la Scolarité";
                
                // Combiner le tout en HTML
                $htmlMessage = convert_text_to_html($introText) . $detailsTable . convert_text_to_html($closingText);
                
                // Envoyer l'email avec le message HTML
                send_email_to_student($student['email'], $subject, $htmlMessage, true);
            } else {
                // Fallback : message texte simple si EmailService n'est pas disponible
                $message .= "Détails de votre demande :\n";
                $message .= "----------------------------------------\n";
                foreach ($details as $label => $value) {
                    $message .= $label . " : " . $value . "\n";
                }
                $message .= "----------------------------------------\n\n";
                $message .= "Votre demande est en cours de traitement. Nous vous tiendrons informé dès que votre document sera prêt.\n\n";
                $message .= "Vous pouvez utiliser le numéro de référence ci-dessus pour suivre l'état de votre demande.\n\n";
                $message .= "Cordialement,\nLe Service de la Scolarité";
                
                // En développement, logger l'email
                error_log("=== EMAIL CONFIRMATION DEMANDE ===");
                error_log("A: " . $student['email']);
                error_log("Sujet: " . $subject);
                error_log("Message:\n" . $message);
                error_log("===================");
            }
        }
    } catch (\Exception $e) {
        // Ne pas faire échouer la création de la demande si l'email échoue
        error_log('Erreur lors de l\'envoi de l\'email de confirmation: ' . $e->getMessage());
    }

    send_json(['ok' => true, 'id' => $idDemande, 'referenceNumber' => $referenceNumber], 201);
}

function handle_update_request_status(PDO $pdo, string $id): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error('Invalid JSON in request body', 400);
    }
    
    if (!isset($input['status']) || !in_array($input['status'], ['accepted', 'rejected', 'pending'], true)) {
        send_error('Invalid status', 400);
    }

    // Utiliser la fonction helper pour mapper le statut
    $dbStatus = map_status_to_db($input['status']);

    // Vérifier si le statut 'refuse' est supporté dans la base de données
    // Si non, utiliser 'en attente' pour 'rejected' (temporaire jusqu'à migration)
    try {
        $stmt = $pdo->prepare('UPDATE demandes SET statut = :statut, id_administrateur = :admin WHERE id_demande = :id');
        $stmt->execute([
            ':statut' => $dbStatus,
            ':id' => $id,
            ':admin' => $input['adminId'] ?? null,
        ]);
    } catch (\PDOException $e) {
        // Si le statut 'refuse' n'existe pas dans l'enum, utiliser 'en attente' pour 'rejected'
        if ($dbStatus === 'refuse' && strpos($e->getMessage(), 'enum') !== false) {
            $stmt = $pdo->prepare('UPDATE demandes SET statut = :statut, id_administrateur = :admin WHERE id_demande = :id');
            $stmt->execute([
                ':statut' => 'en attente', // Fallback si 'refuse' n'existe pas
                ':id' => $id,
                ':admin' => $input['adminId'] ?? null,
            ]);
        } else {
            throw $e;
        }
    }

    if ($stmt->rowCount() === 0) {
        send_error('Request not found', 404);
    }

    // Si la demande est acceptée (traite), envoyer automatiquement l'email avec le PDF
    if ($input['status'] === 'accepted' && $dbStatus === 'traite') {
        try {
            // Récupérer les informations de la demande et de l'étudiant
            $sql = <<<SQL
SELECT 
  d.id_demande,
  d.numero_reference,
  d.type_document,
  d.statut,
  e.email AS etu_email,
  e.nom AS etu_nom,
  e.prenom AS etu_prenom,
  e.numero_apogee AS etu_apogee
FROM demandes d
JOIN etudiants e ON e.id_etudiant = d.id_etudiant
WHERE d.id_demande = :id
SQL;

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $request = $stmt->fetch();

            if ($request) {
                // Construire le message d'email
                $documentTypeLabels = [
                    'attestations_scolarite' => 'Attestation de scolarité',
                    'attestations_reussite' => 'Attestation de réussite',
                    'releves_notes' => 'Relevé de notes',
                    'conventions_stage' => 'Convention de stage',
                ];
                $docLabel = $documentTypeLabels[$request['type_document']] ?? 'Document';
                
                $subject = "Votre demande de document - " . $request['numero_reference'];
                $message = "Bonjour " . $request['etu_prenom'] . " " . $request['etu_nom'] . ",\n\n";
                $message .= "Nous vous informons que votre demande de " . strtolower($docLabel) . " (Référence: " . $request['numero_reference'] . ") ";
                $message .= "a été traitée avec succès.\n\n";
                $message .= "Veuillez trouver ci-joint votre document en format PDF.\n\n";
                $message .= "Cordialement,\nLe Service de la Scolarité";

                // Générer le PDF en pièce jointe
                $pdfAttachment = generate_pdf_attachment($pdo, $id);
                
                // Charger le service d'email et envoyer
                $emailServiceFile = __DIR__ . '/EmailService.php';
                if (file_exists($emailServiceFile)) {
                    require_once $emailServiceFile;
                    $result = send_email_to_student($request['etu_email'], $subject, $message, true, $pdfAttachment);
                    // Logger le résultat (mais ne pas faire échouer la requête si l'email échoue)
                    if (!$result['sent']) {
                        error_log("Erreur lors de l'envoi automatique de l'email pour la demande $id: " . $result['message']);
                    }
                } else {
                    error_log("EmailService.php non trouvé - email non envoyé automatiquement pour la demande $id");
                }
            }
        } catch (\Exception $e) {
            // Ne pas faire échouer la mise à jour du statut si l'email échoue
            error_log('Erreur lors de l\'envoi automatique de l\'email: ' . $e->getMessage());
        }
    }

    // Si la demande est refusée, envoyer automatiquement l'email (sans PDF)
    if ($input['status'] === 'rejected' && ($dbStatus === 'refuse' || $dbStatus === 'en attente')) {
        try {
            // Récupérer les informations de la demande et de l'étudiant
            $sql = <<<SQL
SELECT 
  d.id_demande,
  d.numero_reference,
  d.type_document,
  d.statut,
  e.email AS etu_email,
  e.nom AS etu_nom,
  e.prenom AS etu_prenom,
  e.numero_apogee AS etu_apogee
FROM demandes d
JOIN etudiants e ON e.id_etudiant = d.id_etudiant
WHERE d.id_demande = :id
SQL;

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $request = $stmt->fetch();

            if ($request) {
                // Construire le message d'email
                $documentTypeLabels = [
                    'attestations_scolarite' => 'Attestation de scolarité',
                    'attestations_reussite' => 'Attestation de réussite',
                    'releves_notes' => 'Relevé de notes',
                    'conventions_stage' => 'Convention de stage',
                ];
                $docLabel = $documentTypeLabels[$request['type_document']] ?? 'Document';
                
                $subject = "Votre demande de document - " . $request['numero_reference'];
                $message = "Bonjour " . $request['etu_prenom'] . " " . $request['etu_nom'] . ",\n\n";
                $message .= "Nous vous informons que votre demande de " . strtolower($docLabel) . " (Référence: " . $request['numero_reference'] . ") ";
                $message .= "a été refusée.\n\n";
                
                // Ajouter les raisons du refus si fournies - format pour être détecté comme réponse
                if (!empty($input['rejectionReason']) && trim($input['rejectionReason']) !== '') {
                    $message .= "Raisons du refus :\n";
                    $message .= trim($input['rejectionReason']) . "\n\n";
                }
                
                $message .= "Pour plus d'informations, merci de contacter le service de la scolarité.\n\n";
                $message .= "Cordialement,\nLe Service de la Scolarité";

                // Pas de PDF en pièce jointe pour les demandes refusées
                $pdfAttachment = null;
                
                // Charger le service d'email et envoyer
                $emailServiceFile = __DIR__ . '/EmailService.php';
                if (file_exists($emailServiceFile)) {
                    require_once $emailServiceFile;
                    $result = send_email_to_student($request['etu_email'], $subject, $message, true, $pdfAttachment);
                    // Logger le résultat (mais ne pas faire échouer la requête si l'email échoue)
                    if (!$result['sent']) {
                        error_log("Erreur lors de l'envoi automatique de l'email pour la demande $id: " . $result['message']);
                    }
                } else {
                    error_log("EmailService.php non trouvé - email non envoyé automatiquement pour la demande $id");
                }
            }
        } catch (\Exception $e) {
            // Ne pas faire échouer la mise à jour du statut si l'email échoue
            error_log('Erreur lors de l\'envoi automatique de l\'email: ' . $e->getMessage());
        }
    }

    send_json(['ok' => true]);
}

function handle_get_student_history(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error('Invalid JSON in request body', 400);
    }
    
    if (!isset($input['studentId'])) {
        send_error('Missing required field: studentId', 400);
    }

    try {
        // Query to find years and semesters from registered modules
        // We join inscrit_module with module_filiere (via module ID) to get the semester
        // We get the year from inscription_etudiant via the filiere that contains the module
        $sql = <<<SQL
SELECT DISTINCT 
    CONCAT(au.annee_debut, '-', au.annee_fin) as annee_universitaire,
    mf.semestre
FROM inscrit_module im
JOIN module_filiere mf ON mf.id_module = im.id_module
JOIN inscription_etudiant ie ON ie.id_etudiant = im.id_etudiant AND ie.id_filiere = mf.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
WHERE im.id_etudiant = :studentId
ORDER BY au.annee_debut DESC, mf.semestre ASC
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':studentId' => $input['studentId']]);
        $rows = $stmt->fetchAll();
        
        $history = [];
        
        // Group by academic year
        foreach ($rows as $row) {
            $year = $row['annee_universitaire'];
            $semester = 'S' . $row['semestre'];
            
            if (!isset($history[$year])) {
                $history[$year] = [
                    'year' => $year,
                    'semesters' => []
                ];
            }
            
            if (!in_array($semester, $history[$year]['semesters'])) {
                $history[$year]['semesters'][] = $semester;
            }
        }
        
        // Re-index array to get a clean list
        $result = array_values($history);
        
        // Sort semesters naturally (S1, S2, S3...)
        foreach ($result as &$item) {
            sort($item['semesters'], SORT_NATURAL);
        }
        
        send_json($result);
        
    } catch (\PDOException $e) {
        error_log('Erreur lors de la récupération de l\'historique étudiant: ' . $e->getMessage());
        send_error('Erreur lors de la récupération de l\'historique', 500);
    }
}

function handle_send_email(PDO $pdo, string $id): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error('Invalid JSON in request body', 400);
    }
    
    $subject = $input['subject'] ?? null;
    $message = $input['message'] ?? null;

    // Récupérer les informations de la demande et de l'étudiant
    $sql = <<<SQL
SELECT 
  d.id_demande,
  d.numero_reference,
  d.type_document,
  d.statut,
  e.email AS etu_email,
  e.nom AS etu_nom,
  e.prenom AS etu_prenom,
  e.numero_apogee AS etu_apogee
FROM demandes d
JOIN etudiants e ON e.id_etudiant = d.id_etudiant
WHERE d.id_demande = :id
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch();

    if (!$request) {
        send_error('Request not found', 404);
    }

    // Si aucun sujet/message fourni, utiliser un template par défaut
    if (!$subject) {
        $documentTypeLabels = [
            'attestations_scolarite' => 'Attestation de scolarité',
            'attestations_reussite' => 'Attestation de réussite',
            'releves_notes' => 'Relevé de notes',
            'conventions_stage' => 'Convention de stage',
        ];
        $docType = map_document_type($request['type_document']);
        $docLabel = $documentTypeLabels[$request['type_document']] ?? 'Document';
        
        $subject = "Votre demande de document - " . $request['numero_reference'];
        $message = "Bonjour " . $request['etu_prenom'] . " " . $request['etu_nom'] . ",\n\n";
        $message .= "Nous vous informons que votre demande de " . strtolower($docLabel) . " (Référence: " . $request['numero_reference'] . ") ";

        if ($request['statut'] === 'traite') {
            $message .= "a été traitée avec succès.\n\n";
        } elseif ($request['statut'] === 'refuse' || $request['statut'] === 'rejetee' || $request['statut'] === 'rejeté' || $request['statut'] === 'rejetée') {
            $message .= "a été refusée.\n\n";
            $message .= "Pour plus d'informations, merci de contacter le service de la scolarité.\n\n";
        } else {
            $message .= "est en cours de traitement.\n\n";
            $message .= "Nous vous tiendrons informé dès que votre document sera prêt.\n\n";
        }
        $message .= "Cordialement,\nLe Service de la Scolarité";
    }

    // Envoyer l'email en utilisant le service d'email
    $to = $request['etu_email'];
    
    // Générer le PDF en pièce jointe si la demande est traitée
    $pdfAttachment = null;
    if ($request['statut'] === 'traite') {
        $pdfAttachment = generate_pdf_attachment($pdo, $id);
        if ($pdfAttachment) {
            // Mettre à jour le message pour mentionner la pièce jointe
            $message .= "\n\nVeuillez trouver ci-joint votre document en format PDF.";
        }
    }
    
    // Charger le service d'email s'il existe
    $emailServiceFile = __DIR__ . '/EmailService.php';
    if (file_exists($emailServiceFile)) {
        require_once $emailServiceFile;
        $result = send_email_to_student($to, $subject, $message, true, $pdfAttachment);
    } else {
        // Fallback : utiliser la fonction mail() de base
        $isDevelopment = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:8000', '127.0.0.1:8000']);
        
        if ($isDevelopment) {
            error_log("=== EMAIL SIMULE ===");
            error_log("A: $to");
            error_log("Sujet: $subject");
            error_log("Message:\n$message");
            error_log("===================");
            $result = [
                'sent' => false,
                'message' => "Email simulé en développement. Créez EmailService.php pour une meilleure gestion.",
            ];
        } else {
            $headers = "From: scolarite@univ.ma\r\n";
            $headers .= "Reply-To: scolarite@univ.ma\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            $mailSent = @mail($to, $subject, $message, $headers);
            $result = [
                'sent' => $mailSent,
                'message' => $mailSent ? 'Email envoyé avec succès' : 'Erreur lors de l\'envoi',
            ];
        }
    }
    
    send_json([
        'ok' => true,
        'email' => $to,
        'sent' => $result['sent'],
        'message' => $result['message'],
    ]);
}

function handle_download_document(PDO $pdo, string $id): void
{
    // Démarrer le buffer de sortie et s'assurer qu'aucun header n'a été envoyé
    if (ob_get_level() === 0) {
        ob_start();
    }
    
    // Vérifier qu'aucun header n'a été envoyé
    if (headers_sent($file, $line)) {
        error_log("Headers already sent in $file at line $line");
        ob_end_clean();
        send_error('Headers already sent', 500);
    }
    
    // Récupérer toutes les informations de la demande
    // Récupérer toutes les informations de la demande (sans filière, on la récupérera après selon le type de document)
    $sql = <<<SQL
SELECT 
  d.id_demande,
  d.numero_reference,
  d.type_document,
  d.statut,
  d.date_demande,
  d.id_etudiant,
  e.email AS etu_email,
  e.nom AS etu_nom,
  e.prenom AS etu_prenom,
  e.numero_apogee AS etu_apogee,
  e.cin AS etu_cin,
  e.date_naissance AS etu_date_naissance,
  e.lieu_naissance AS etu_lieu_naissance,
  (SELECT CASE 
    WHEN f_latest.nom_filiere = '2AP1' THEN '1er annee'
    WHEN f_latest.nom_filiere = '2AP2' THEN '2éme annee'
    WHEN f_latest.nom_filiere = 'Génie Informatique 1' THEN '3eme annee'
    WHEN f_latest.nom_filiere = 'Génie Informatique 2' THEN '4eme annee'
    WHEN f_latest.nom_filiere = 'Génie Informatique 3' THEN '5eme annee'
    ELSE NULL
  END
  FROM inscription_etudiant ie_latest
  JOIN filiere f_latest ON f_latest.id_filiere = ie_latest.id_filiere
  JOIN annee_universitaire au_latest ON au_latest.id_annee = ie_latest.id_annee
  WHERE ie_latest.id_etudiant = e.id_etudiant
  ORDER BY au_latest.annee_debut DESC
  LIMIT 1) AS etu_niveau,
  ar.annee_universitaire AS ar_annee,
  rn.annee_universitaire AS rn_annee,
  rn.semestre AS rn_semestre,
  cs.nom_entreprise,
  cs.adresse_entreprise,
  cs.sujet_stage,
  cs.date_debut_stage,
  cs.date_fin_stage,
  cs.email_responsable_entreprise,
  cs.nom_responsable_entreprise,
  cs.telephone_responsable_entreprise,
  p.nom AS prof_nom,
  p.prenom AS prof_prenom
FROM demandes d
JOIN etudiants e ON e.id_etudiant = d.id_etudiant
LEFT JOIN attestations_reussite ar ON ar.id_demande = d.id_demande
LEFT JOIN releves_notes rn ON rn.id_demande = d.id_demande
LEFT JOIN conventions_stage cs ON cs.id_demande = d.id_demande
LEFT JOIN professeur p ON p.id_prof = cs.id_prof_encadrant
WHERE d.id_demande = :id
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch();
    
    // Déterminer l'année universitaire selon le type de document et récupérer la filière correcte
    if ($request) {
        $anneeUniv = null;
        $skipFiliereFetch = false;
        $documentType = map_document_type($request['type_document']);
        
        if ($documentType === 'releve_notes' && !empty($request['rn_annee'])) {
            $anneeUniv = $request['rn_annee'];
        } elseif ($documentType === 'attestation_reussite' && !empty($request['ar_annee'])) {
            $anneeUniv = $request['ar_annee'];
        } elseif ($documentType === 'attestation_scolarite') {
            // Pour attestation de scolarité, utiliser la dernière inscription de l'étudiant
            // On récupérera la filière généralisée depuis la dernière inscription
            $latestInscription = get_latest_inscription($pdo, $request['id_etudiant']);
            if ($latestInscription) {
                $request['filiere_nom'] = $latestInscription['filiere_nom'];
                $request['filiere_id'] = $latestInscription['filiere_id'];
                $request['annee_debut'] = $latestInscription['annee_debut'];
                $request['annee_fin'] = $latestInscription['annee_fin'];
                $request['moyenne'] = $latestInscription['moyenne'];
                $request['mention'] = $latestInscription['mention'];
                $request['est_admis'] = $latestInscription['est_admis'];
                // Utiliser l'année universitaire de la dernière inscription pour l'affichage
                $anneeUniv = $latestInscription['annee_debut'] . '-' . $latestInscription['annee_fin'];
            } else {
                // Fallback sur l'année de la demande si aucune inscription trouvée
                $dateDemande = new DateTime($request['date_demande']);
                $anneeDemande = (int)$dateDemande->format('Y');
                $anneeUniv = $anneeDemande . '-' . ($anneeDemande + 1);
            }
            // Ne pas récupérer la filière à nouveau, elle est déjà récupérée ci-dessus
            // On garde $anneeUniv pour l'affichage mais on ne fera pas la récupération supplémentaire
            $skipFiliereFetch = true;
        } elseif ($documentType === 'convention_stage') {
            // Pour convention de stage, utiliser la date de début du stage pour déterminer l'année universitaire
            if (!empty($request['date_debut_stage'])) {
                $dateDebutStage = new DateTime($request['date_debut_stage']);
                $anneeDebut = (int)$dateDebutStage->format('Y');
                $anneeUniv = $anneeDebut . '-' . ($anneeDebut + 1);
            } else {
                // Fallback sur la date de demande si date_debut_stage n'est pas disponible
                $dateDemande = new DateTime($request['date_demande']);
                $anneeDemande = (int)$dateDemande->format('Y');
                $anneeUniv = $anneeDemande . '-' . ($anneeDemande + 1);
            }
        }
        
        // Récupérer la filière depuis inscription_etudiant pour l'année universitaire appropriée
        // (sauf pour attestation_scolarite qui utilise déjà la dernière inscription)
        if ($anneeUniv && !empty($request['id_etudiant']) && !$skipFiliereFetch) {
            $filiereData = get_filiere_for_academic_year($pdo, $request['id_etudiant'], $anneeUniv);
            if ($filiereData) {
                $request['filiere_nom'] = $filiereData['filiere_nom'];
                $request['filiere_id'] = $filiereData['filiere_id'];
                $request['annee_debut'] = $filiereData['annee_debut'];
                $request['annee_fin'] = $filiereData['annee_fin'];
                $request['moyenne'] = $filiereData['moyenne'];
                $request['mention'] = $filiereData['mention'];
                $request['est_admis'] = $filiereData['est_admis'];
            }
        }
    }

    if (!$request) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(404);
        header('Content-Type: text/plain');
        die('Request not found');
    }

    // Vérifier que la demande est acceptée
    // MODIFICATION: Autoriser le téléchargement pour tous les statuts (demande admin)
    /*
    if ($request['statut'] !== 'traite') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(400);
        header('Content-Type: text/plain');
        die('Document not ready. Request must be accepted first.');
    }
    */

    // Générer le HTML du document selon le type
    $documentType = map_document_type($request['type_document']);
    $htmlContent = generate_document_pdf($request, $documentType, $pdo);

    // Déterminer le nom du fichier
    $documentTypeLabels = [
        'attestation_scolarite' => 'Attestation_Scolarite',
        'attestation_reussite' => 'Attestation_Reussite',
        'releve_notes' => 'Releve_Notes',
        'convention_stage' => 'Convention_Stage',
    ];
    $filename = ($documentTypeLabels[$documentType] ?? 'Document') . '_' . $request['numero_reference'] . '.pdf';

    // Utiliser DomPDF si disponible
    if (class_exists('Dompdf\Dompdf')) {
        try {
            // Nettoyer toute sortie précédente
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Vérifier que le HTML n'est pas vide
            if (empty($htmlContent)) {
                throw new \Exception('HTML content is empty');
            }
            
            $options = new \Dompdf\Options();
            // Utiliser une police standard qui est généralement disponible
            $options->set('defaultFont', 'Helvetica');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('isPhpEnabled', false);
            $options->set('chroot', __DIR__);
            $options->set('logOutputFile', __DIR__ . '/dompdf_log.html');
            $options->set('debugKeepTemp', false);
            $options->set('debugCss', false);
            $options->set('debugLayout', false);
            $options->set('fontDir', __DIR__ . '/../vendor/dompdf/dompdf/lib/fonts/');
            
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($htmlContent, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $pdfOutput = $dompdf->output();
            
            if (empty($pdfOutput)) {
                throw new \Exception('PDF output is empty');
            }
            
            // Vérifier que le PDF commence par le header PDF valide
            if (substr($pdfOutput, 0, 4) !== '%PDF') {
                throw new \Exception('Invalid PDF format - PDF header not found');
            }
            
            // S'assurer qu'aucun buffer n'est actif
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            // Vérifier qu'aucun header n'a été envoyé
            if (headers_sent()) {
                throw new \Exception('Headers already sent, cannot send PDF');
            }
            
            // Envoyer les headers
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
            header('Content-Length: ' . strlen($pdfOutput));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            header('X-Content-Type-Options: nosniff');
            
            // Envoyer le PDF
            echo $pdfOutput;
            flush();
            
            // Terminer proprement
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            exit;
        } catch (\Exception $e) {
            error_log("Erreur DomPDF: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            // Fallback vers HTML si erreur
            if (ob_get_level()) {
                ob_clean();
            }
            // En mode développement, afficher l'erreur
            if (isset($_GET['debug']) || in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])) {
                header('Content-Type: text/html; charset=UTF-8');
                echo '<html><body>';
                echo '<h1>Erreur lors de la génération du PDF</h1>';
                echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<p><strong>Fichier:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
                echo '<p><strong>Ligne:</strong> ' . $e->getLine() . '</p>';
                echo '<h2>HTML généré (premiers 2000 caractères):</h2>';
                echo '<pre>' . htmlspecialchars(substr($htmlContent, 0, 2000)) . '...</pre>';
                echo '</body></html>';
                exit;
            }
        }
    }
    
    // Fallback: Retourner le HTML (le navigateur peut le convertir)
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: inline; filename="' . str_replace('.pdf', '.html', $filename) . '"');
    echo $htmlContent;
    exit;
}

/**
 * Calcule le niveau scolaire à partir du nom de la filière
 * @param string $nomFiliere
 * @return string
 */
function calculate_niveau_scolaire(string $nomFiliere): string
{
    $mapping = [
        '2AP1' => '1er annee',
        '2AP2' => '2éme annee',
        'Génie Informatique 1' => '3eme annee',
        'Génie Informatique 2' => '4eme annee',
        'Génie Informatique 3' => '5eme annee'
    ];
    
    return $mapping[$nomFiliere] ?? 'Non spécifié';
}

/**
 * Calcule la mention à partir de la moyenne
 * @param float $moyenne
 * @return string
 */
function calculate_mention(float $moyenne): string
{
    if ($moyenne >= 16.0) {
        return 'Très Bien';
    } elseif ($moyenne >= 14.0) {
        return 'Bien';
    } elseif ($moyenne >= 12.0) {
        return 'Assez Bien';
    } elseif ($moyenne >= 10.0) {
        return 'Passable';
    } else {
        return 'Insuffisant';
    }
}

/**
 * Retourne le seuil de validation selon la filière
 * @param string $nomFiliere
 * @return float Seuil de validation (10.0 pour 2AP1/2AP2, 12.0 pour Génie Informatique)
 */
function get_seuil_validation(string $nomFiliere): float
{
    // 2AP1 et 2AP2 : validation à partir de 10/20
    if ($nomFiliere === '2AP1' || $nomFiliere === '2AP2') {
        return 10.0;
    }
    // Génie Informatique 1, 2, 3 : validation à partir de 12/20
    if (strpos($nomFiliere, 'Génie Informatique') === 0) {
        return 12.0;
    }
    // Par défaut, utiliser 10.0
    return 10.0;
}

/**
 * Détermine si un module est validé selon la note et la filière
 * @param float $note Note du module
 * @param string $nomFiliere Nom de la filière
 * @return bool true si le module est validé, false sinon
 */
function is_module_valide(float $note, string $nomFiliere): bool
{
    $seuil = get_seuil_validation($nomFiliere);
    return $note >= $seuil;
}

/**
 * Récupère la dernière inscription d'un étudiant (la plus récente selon l'année universitaire)
 * Calcule dynamiquement moyenne, mention et est_admis à partir des notes
 * @param PDO $pdo
 * @param string $idEtudiant
 * @return array|null Retourne ['filiere_nom', 'filiere_id', 'annee_debut', 'annee_fin', 'moyenne', 'mention', 'est_admis'] ou null
 */
function get_latest_inscription(PDO $pdo, string $idEtudiant): ?array
{
    try {
        $sql = <<<SQL
SELECT 
    f.nom_filiere AS filiere_nom,
    f.id_filiere AS filiere_id,
    au.annee_debut,
    au.annee_fin,
    au.id_annee,
    AVG(im.note) as moyenne,
    CASE 
        WHEN AVG(im.note) >= 16.0 THEN 'Très Bien'
        WHEN AVG(im.note) >= 14.0 THEN 'Bien'
        WHEN AVG(im.note) >= 12.0 THEN 'Assez Bien'
        WHEN AVG(im.note) >= 10.0 THEN 'Passable'
        ELSE 'Insuffisant'
    END as mention,
    CASE 
        WHEN f.nom_filiere IN ('2AP1', '2AP2') AND AVG(im.note) >= 10.0 THEN 1
        WHEN f.nom_filiere LIKE 'Génie Informatique%' AND AVG(im.note) >= 12.0 THEN 1
        ELSE 0
    END as est_admis
FROM inscription_etudiant ie
JOIN filiere f ON f.id_filiere = ie.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
LEFT JOIN inscrit_module im ON im.id_etudiant = ie.id_etudiant
LEFT JOIN module_filiere mf ON mf.id_module = im.id_module 
    AND mf.id_filiere = ie.id_filiere
WHERE ie.id_etudiant = :id_etudiant
    AND im.note IS NOT NULL
GROUP BY ie.id_etudiant, ie.id_filiere, ie.id_annee, f.nom_filiere, f.id_filiere, au.annee_debut, au.annee_fin, au.id_annee
ORDER BY au.annee_debut DESC
LIMIT 1
SQL;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_etudiant' => $idEtudiant]);
        
        $result = $stmt->fetch();
        
        // Si aucune note trouvée, retourner quand même les infos de base sans moyenne
        if (!$result) {
            $sqlFallback = <<<SQL
SELECT 
    f.nom_filiere AS filiere_nom,
    f.id_filiere AS filiere_id,
    au.annee_debut,
    au.annee_fin,
    au.id_annee
FROM inscription_etudiant ie
JOIN filiere f ON f.id_filiere = ie.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
WHERE ie.id_etudiant = :id_etudiant
ORDER BY au.annee_debut DESC
LIMIT 1
SQL;
            $stmtFallback = $pdo->prepare($sqlFallback);
            $stmtFallback->execute([':id_etudiant' => $idEtudiant]);
            $result = $stmtFallback->fetch();
            if ($result) {
                $result['moyenne'] = null;
                $result['mention'] = null;
                $result['est_admis'] = 0;
            }
        }
        
        return $result ? $result : null;
    } catch (\PDOException $e) {
        error_log("Erreur lors de la récupération de la dernière inscription: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère la filière d'un étudiant pour une année universitaire donnée depuis inscription_etudiant
 * Calcule dynamiquement moyenne, mention et est_admis à partir des notes
 * @param PDO $pdo
 * @param string $idEtudiant
 * @param string $anneeUniv Format 'YYYY-YYYY' ou 'YYYY/YYYY'
 * @return array|null Retourne ['filiere_nom', 'filiere_id', 'annee_debut', 'annee_fin', 'moyenne', 'mention', 'est_admis'] ou null
 */
function get_filiere_for_academic_year(PDO $pdo, string $idEtudiant, string $anneeUniv): ?array
{
    // Convertir le format de l'année universitaire (YYYY-YYYY ou YYYY/YYYY) en année de début
    $anneeDebut = null;
    if (preg_match('/^(\d{4})[-\/]\d{4}$/', $anneeUniv, $matches)) {
        $anneeDebut = (int)$matches[1];
    } elseif (preg_match('/^\d{4}$/', $anneeUniv)) {
        $anneeDebut = (int)$anneeUniv;
    }
    
    if ($anneeDebut === null) {
        return null;
    }
    
    try {
        $sql = <<<SQL
SELECT 
    f.nom_filiere AS filiere_nom,
    f.id_filiere AS filiere_id,
    au.annee_debut,
    au.annee_fin,
    au.id_annee,
    AVG(im.note) as moyenne,
    CASE 
        WHEN AVG(im.note) >= 16.0 THEN 'Très Bien'
        WHEN AVG(im.note) >= 14.0 THEN 'Bien'
        WHEN AVG(im.note) >= 12.0 THEN 'Assez Bien'
        WHEN AVG(im.note) >= 10.0 THEN 'Passable'
        ELSE 'Insuffisant'
    END as mention,
    CASE 
        WHEN f.nom_filiere IN ('2AP1', '2AP2') AND AVG(im.note) >= 10.0 THEN 1
        WHEN f.nom_filiere LIKE 'Génie Informatique%' AND AVG(im.note) >= 12.0 THEN 1
        ELSE 0
    END as est_admis
FROM inscription_etudiant ie
JOIN filiere f ON f.id_filiere = ie.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
LEFT JOIN inscrit_module im ON im.id_etudiant = ie.id_etudiant
LEFT JOIN module_filiere mf ON mf.id_module = im.id_module 
    AND mf.id_filiere = ie.id_filiere
WHERE ie.id_etudiant = :id_etudiant
    AND au.annee_debut = :annee_debut
    AND im.note IS NOT NULL
GROUP BY ie.id_etudiant, ie.id_filiere, ie.id_annee, f.nom_filiere, f.id_filiere, au.annee_debut, au.annee_fin, au.id_annee
LIMIT 1
SQL;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_etudiant' => $idEtudiant,
            ':annee_debut' => $anneeDebut
        ]);
        
        $result = $stmt->fetch();
        
        // Si aucune note trouvée, retourner quand même les infos de base sans moyenne
        if (!$result) {
            $sqlFallback = <<<SQL
SELECT 
    f.nom_filiere AS filiere_nom,
    f.id_filiere AS filiere_id,
    au.annee_debut,
    au.annee_fin,
    au.id_annee
FROM inscription_etudiant ie
JOIN filiere f ON f.id_filiere = ie.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
WHERE ie.id_etudiant = :id_etudiant
    AND au.annee_debut = :annee_debut
LIMIT 1
SQL;
            $stmtFallback = $pdo->prepare($sqlFallback);
            $stmtFallback->execute([
                ':id_etudiant' => $idEtudiant,
                ':annee_debut' => $anneeDebut
            ]);
            $result = $stmtFallback->fetch();
            if ($result) {
                $result['moyenne'] = null;
                $result['mention'] = null;
                $result['est_admis'] = 0;
            }
        }
        
        return $result ? $result : null;
    } catch (\PDOException $e) {
        error_log("Erreur lors de la récupération de la filière: " . $e->getMessage());
        return null;
    }
}

/**
 * Génère le PDF en mémoire (pour pièce jointe email)
 * Retourne un tableau avec 'content' (contenu binaire) et 'filename' (nom du fichier)
 */
function generate_pdf_attachment(PDO $pdo, string $requestId): ?array
{
    // Récupérer toutes les informations de la demande (sans filière, on la récupérera après selon le type de document)
    $sql = <<<SQL
SELECT 
  d.id_demande,
  d.numero_reference,
  d.type_document,
  d.statut,
  d.date_demande,
  d.id_etudiant,
  e.email AS etu_email,
  e.nom AS etu_nom,
  e.prenom AS etu_prenom,
  e.numero_apogee AS etu_apogee,
  e.cin AS etu_cin,
  e.date_naissance AS etu_date_naissance,
  e.lieu_naissance AS etu_lieu_naissance,
  (SELECT CASE 
    WHEN f_latest.nom_filiere = '2AP1' THEN '1er annee'
    WHEN f_latest.nom_filiere = '2AP2' THEN '2éme annee'
    WHEN f_latest.nom_filiere = 'Génie Informatique 1' THEN '3eme annee'
    WHEN f_latest.nom_filiere = 'Génie Informatique 2' THEN '4eme annee'
    WHEN f_latest.nom_filiere = 'Génie Informatique 3' THEN '5eme annee'
    ELSE NULL
  END
  FROM inscription_etudiant ie_latest
  JOIN filiere f_latest ON f_latest.id_filiere = ie_latest.id_filiere
  JOIN annee_universitaire au_latest ON au_latest.id_annee = ie_latest.id_annee
  WHERE ie_latest.id_etudiant = e.id_etudiant
  ORDER BY au_latest.annee_debut DESC
  LIMIT 1) AS etu_niveau,
  ar.annee_universitaire AS ar_annee,
  rn.annee_universitaire AS rn_annee,
  rn.semestre AS rn_semestre,
  cs.nom_entreprise,
  cs.adresse_entreprise,
  cs.sujet_stage,
  cs.date_debut_stage,
  cs.date_fin_stage,
  cs.email_responsable_entreprise,
  cs.nom_responsable_entreprise,
  cs.telephone_responsable_entreprise,
  p.nom AS prof_nom,
  p.prenom AS prof_prenom
FROM demandes d
JOIN etudiants e ON e.id_etudiant = d.id_etudiant
LEFT JOIN attestations_reussite ar ON ar.id_demande = d.id_demande
LEFT JOIN releves_notes rn ON rn.id_demande = d.id_demande
LEFT JOIN conventions_stage cs ON cs.id_demande = d.id_demande
LEFT JOIN professeur p ON p.id_prof = cs.id_prof_encadrant
WHERE d.id_demande = :id
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $requestId]);
    $request = $stmt->fetch();

    if (!$request || $request['statut'] !== 'traite') {
        return null;
    }

    // Déterminer l'année universitaire selon le type de document et récupérer la filière correcte
    $anneeUniv = null;
    $skipFiliereFetch = false;
    $documentType = map_document_type($request['type_document']);
    
    if ($documentType === 'releve_notes' && !empty($request['rn_annee'])) {
        $anneeUniv = $request['rn_annee'];
    } elseif ($documentType === 'attestation_reussite' && !empty($request['ar_annee'])) {
        $anneeUniv = $request['ar_annee'];
    } elseif ($documentType === 'attestation_scolarite') {
        // Pour attestation de scolarité, utiliser la dernière inscription de l'étudiant
        // On récupérera la filière généralisée depuis la dernière inscription
        $latestInscription = get_latest_inscription($pdo, $request['id_etudiant']);
        if ($latestInscription) {
            $request['filiere_nom'] = $latestInscription['filiere_nom'];
            $request['filiere_id'] = $latestInscription['filiere_id'];
            $request['annee_debut'] = $latestInscription['annee_debut'];
            $request['annee_fin'] = $latestInscription['annee_fin'];
            $request['moyenne'] = $latestInscription['moyenne'];
            $request['mention'] = $latestInscription['mention'];
            $request['est_admis'] = $latestInscription['est_admis'];
            // Utiliser l'année universitaire de la dernière inscription pour l'affichage
            $anneeUniv = $latestInscription['annee_debut'] . '-' . $latestInscription['annee_fin'];
        } else {
            // Fallback sur l'année de la demande si aucune inscription trouvée
            $dateDemande = new DateTime($request['date_demande']);
            $anneeDemande = (int)$dateDemande->format('Y');
            $anneeUniv = $anneeDemande . '-' . ($anneeDemande + 1);
        }
        // Ne pas récupérer la filière à nouveau, elle est déjà récupérée ci-dessus
        $skipFiliereFetch = true;
    } elseif ($documentType === 'convention_stage') {
        // Pour convention de stage, utiliser la date de début du stage pour déterminer l'année universitaire
        if (!empty($request['date_debut_stage'])) {
            $dateDebutStage = new DateTime($request['date_debut_stage']);
            $anneeDebut = (int)$dateDebutStage->format('Y');
            $anneeUniv = $anneeDebut . '-' . ($anneeDebut + 1);
        } else {
            // Fallback sur la date de demande si date_debut_stage n'est pas disponible
            $dateDemande = new DateTime($request['date_demande']);
            $anneeDemande = (int)$dateDemande->format('Y');
            $anneeUniv = $anneeDemande . '-' . ($anneeDemande + 1);
        }
    }
    
    // Récupérer la filière depuis inscription_etudiant pour l'année universitaire appropriée
    // (sauf pour attestation_scolarite qui utilise déjà la dernière inscription)
    if ($anneeUniv && !empty($request['id_etudiant']) && !$skipFiliereFetch) {
        $filiereData = get_filiere_for_academic_year($pdo, $request['id_etudiant'], $anneeUniv);
        if ($filiereData) {
            $request['filiere_nom'] = $filiereData['filiere_nom'];
            $request['filiere_id'] = $filiereData['filiere_id'];
            $request['annee_debut'] = $filiereData['annee_debut'];
            $request['annee_fin'] = $filiereData['annee_fin'];
            $request['moyenne'] = $filiereData['moyenne'];
            $request['mention'] = $filiereData['mention'];
            $request['est_admis'] = $filiereData['est_admis'];
        }
    }

    // Générer le HTML du document selon le type
    $htmlContent = generate_document_pdf($request, $documentType, $pdo);

    // Déterminer le nom du fichier
    $documentTypeLabels = [
        'attestation_scolarite' => 'Attestation_Scolarite',
        'attestation_reussite' => 'Attestation_Reussite',
        'releve_notes' => 'Releve_Notes',
        'convention_stage' => 'Convention_Stage',
    ];
    $filename = ($documentTypeLabels[$documentType] ?? 'Document') . '_' . $request['numero_reference'] . '.pdf';

    // Utiliser DomPDF si disponible
    if (class_exists('Dompdf\Dompdf')) {
        try {
            if (empty($htmlContent)) {
                return null;
            }
            
            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Helvetica');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('isPhpEnabled', false);
            $options->set('chroot', __DIR__);
            $options->set('logOutputFile', __DIR__ . '/dompdf_log.html');
            $options->set('debugKeepTemp', false);
            $options->set('debugCss', false);
            $options->set('debugLayout', false);
            $options->set('fontDir', __DIR__ . '/../vendor/dompdf/dompdf/lib/fonts/');
            
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($htmlContent, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $pdfOutput = $dompdf->output();
            
            if (empty($pdfOutput) || substr($pdfOutput, 0, 4) !== '%PDF') {
                return null;
            }
            
            return [
                'content' => $pdfOutput,
                'filename' => $filename,
            ];
        } catch (\Exception $e) {
            error_log("Erreur lors de la génération du PDF pour pièce jointe: " . $e->getMessage());
            return null;
        }
    }
    
    return null;
}

function generate_document_pdf(array $request, ?string $documentType, PDO $pdo): string
{
    // Pour l'instant, on génère un PDF simple avec du HTML
    // En production, utilisez une bibliothèque comme TCPDF, FPDF, ou DomPDF
    
    // Chemin du logo - convertir en base64 pour DomPDF
    $logoPath = __DIR__ . '/../frontend/src/assets/logo.png';
    $logoUrl = '';
    if (file_exists($logoPath)) {
        try {
            $logoData = @file_get_contents($logoPath);
            if ($logoData !== false && strlen($logoData) > 0) {
                $logoBase64 = base64_encode($logoData);
                // Limiter la taille du logo (max 2MB en base64)
                if (strlen($logoBase64) < 3000000) {
                    $logoMimeType = @mime_content_type($logoPath) ?: 'image/png';
                    $logoUrl = 'data:' . $logoMimeType . ';base64,' . $logoBase64;
                }
            }
        } catch (\Exception $e) {
            error_log("Erreur lors du chargement du logo: " . $e->getMessage());
        }
    }
    
    // Si le logo n'a pas pu être chargé, utiliser un placeholder simple
    if (empty($logoUrl)) {
        $logoUrl = 'data:image/svg+xml;base64,' . base64_encode('<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><text x="50" y="50" font-family="Arial" font-size="12" fill="#000" text-anchor="middle">ENSA</text></svg>');
    }
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Document - ' . htmlspecialchars($request['numero_reference']) . '</title>
    <style>
        @page { 
            margin: 20mm 15mm;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body { 
            font-family: "Helvetica Neue", Helvetica, Arial, "Liberation Sans", sans-serif; 
            margin: 0;
            padding: 0;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
        }
        .header { 
            margin-bottom: 30px;
            padding-bottom: 15px;
        }
        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .header-left, .header-right {
            display: table-cell;
            width: 50%;
            font-size: 10pt;
            vertical-align: top;
            line-height: 1.5;
        }
        .header-left {
            text-align: left;
        }
        .header-right {
            text-align: right;
            direction: rtl;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }
        .logo-container {
            text-align: center;
            margin: 20px 0;
            clear: both;
        }
        .logo-container img {
            max-height: 90px;
            max-width: 200px;
            width: auto;
            height: auto;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 18pt;
            margin: 30px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #000;
        }
        .content { 
            margin: 30px 0; 
            line-height: 1.8;
            text-align: justify;
            font-size: 12pt;
        }
        .info-section {
            margin: 25px 0;
        }
        .info-row { 
            margin: 10px 0;
            display: block;
        }
        .info-label { 
            font-weight: bold;
            min-width: 200px;
            display: inline-block;
            vertical-align: top;
        }
        .info-value {
            display: inline-block;
            vertical-align: top;
        }
        .footer { 
            margin-top: 80px;
            padding-top: 20px;
            font-size: 10pt;
            display: table;
            width: 100%;
            border-top: 1px solid #e0e0e0;
        }
        .footer-left, .footer-right {
            display: table-cell;
            vertical-align: top;
            line-height: 1.6;
        }
        .footer-left {
            text-align: left;
            width: 60%;
        }
        .footer-right {
            text-align: right;
            width: 40%;
        }
        .signature-section {
            margin-top: 50px;
            text-align: right;
        }
        .signature-name {
            font-weight: bold;
            margin-top: 30px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
        }
        table th, table td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        table th { 
            background-color: #f2f2f2; 
        }
        .qr-placeholder {
            width: 80px;
            height: 80px;
            border: 1px solid #ccc;
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
            background-color: #f5f5f5;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>';

    switch ($documentType) {
        case 'attestation_scolarite':
            $html .= generate_attestation_scolarite_html($request, $logoUrl);
            break;
        case 'attestation_reussite':
            $html .= generate_attestation_reussite_html($request, $logoUrl);
            break;
        case 'releve_notes':
            $html .= generate_releve_notes_html($request, $pdo, $logoUrl);
            break;
        case 'convention_stage':
            $html .= generate_convention_stage_html($request, $logoUrl);
            break;
        default:
            $html .= '<p>Type de document non reconnu.</p>';
    }

    $html .= '
</body>
</html>';

    return $html;
}

function generate_attestation_scolarite_html(array $request, string $logoUrl): string
{
    // Formatage de la date de naissance
    $dateNaissance = '';
    $lieuNaissance = '';
    if (!empty($request['etu_date_naissance'])) {
        $dateNaissance = date('d/m/Y', strtotime($request['etu_date_naissance']));
    }
    if (!empty($request['etu_lieu_naissance'])) {
        $lieuNaissance = strtoupper(htmlspecialchars($request['etu_lieu_naissance']));
    }
    
    // Formatage de l'année universitaire
    $anneeUniv = '';
    if (!empty($request['annee_debut']) && !empty($request['annee_fin'])) {
        $anneeUniv = $request['annee_debut'] . '/' . $request['annee_fin'];
    } else {
        // Utiliser l'année en cours comme fallback
        $currentYear = (int)date('Y');
        $anneeUniv = $currentYear . '/' . ($currentYear + 1);
    }
    
    // Nom complet de l'étudiant (NOM PRENOM en majuscules)
    $nomComplet = strtoupper(htmlspecialchars($request['etu_nom'] . ' ' . $request['etu_prenom']));
    
    // Filière - Utiliser directement la filière depuis inscription_etudiant (déjà récupérée comme dernière inscription)
    $filiere = !empty($request['filiere_nom']) ? htmlspecialchars($request['filiere_nom']) : 'Non spécifiée';
    
    // Déterminer le diplôme et la description de la filière
    $diplome = 'Ingénieur d\'État';
    
    // Généraliser la description de la filière selon les règles :
    // - 2AP1 ou 2AP2 → "Classes préparatoires"
    // - Génie Informatique 1, 2 ou 3 → "Génie Informatique"
    $filiereDescription = '';
    if ($filiere === '2AP1' ) {
        $filiereDescription = ' 1ère année classe préparatoire (2AP1)';
    }elseif($filiere === '2AP2') {
        $filiereDescription = ' 2ème année classe préparatoire (2AP2)';
    }elseif($filiere === 'Génie Informatique 1') {
        $filiereDescription = 'Génie Informatique 1';
    }elseif($filiere === 'Génie Informatique 2') {
        $filiereDescription = 'Génie Informatique 2';
    }elseif($filiere === 'Génie Informatique 3') {
        $filiereDescription = 'Génie Informatique 3';
    }elseif (!empty($filiere) && $filiere !== 'Non spécifiée') {
        // Fallback: utiliser directement le nom de la filière
        $filiereDescription = $filiere;
    } else {
        $filiereDescription = 'Non spécifiée';
    }
    
    // Date et référence
    $dateGeneration = date('d/m/Y');
    $heureGeneration = date('H:i:s');
    $refDoc = htmlspecialchars($request['numero_reference']);
    
    // Construction du HTML - SANS TEXTE ARABE pour éviter les problèmes d'affichage
    $html = '
    <!-- Header -->
    <div class="header" style="text-align: center; margin-bottom: 20px;">
        <img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="max-height: 70px; margin-bottom: 10px;" />
        <div style="font-weight: bold; font-size: 11pt; margin-bottom: 3px;">Université Abdelmalek Essaâdi</div>
        <div style="font-weight: bold; font-size: 10pt;">ENSA Tétouan - École Nationale des Sciences Appliquées</div>
    </div>

    <!-- Titre -->
    <div style="text-align: center; margin-bottom: 25px;">
        <h1 style="font-size: 18pt; text-decoration: underline; font-family: serif; letter-spacing: 1px; margin: 0;">ATTESTATION DE SCOLARITÉ</h1>
    </div>
    
    <!-- Corps du document -->
    <div style="font-size: 10pt; line-height: 1.5; font-family: sans-serif;">
        <p style="margin-bottom: 15px;">
            Je soussigné, le Directeur de l\'École Nationale des Sciences Appliquées de Tétouan, atteste que :
        </p>
        
        <!-- Informations étudiant en format tableau -->
        <div style="margin-left: 20px; margin-bottom: 15px;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 5px;">
                <tr>
                    <td style="width: 150px; font-weight: bold;">L\'étudiant(e)</td> 
                     <td> ' . $nomComplet . '</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">N° Apogée</td>
                     <td> ' . htmlspecialchars($request['etu_apogee']) . '</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">CIN</td>  
                     <td> ' . htmlspecialchars($request['etu_cin']) . '</td> 
                </tr>';
    
    // Ajouter date et lieu de naissance si disponibles
    if ($dateNaissance && $lieuNaissance) {
        $html .= '
                <tr>
                    <td style="font-weight: bold;">Né(e) le</td> 
                     <td> ' . $dateNaissance . ' &nbsp;&nbsp;&nbsp; à ' . $lieuNaissance . '</td>
                </tr>';
    }
    
    $html .= '
            </table>
        </div>
        
        <p style="margin-bottom: 15px;">
            Poursuit ses études, à ladite École, au titre de l\'année universitaire : <strong>' . $anneeUniv . '</strong>.
        </p>
        
        <!-- Informations académiques -->
        <div style="margin-left: 20px; margin-bottom: 20px;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 5px;">
                <tr>
                    <td style="width: 150px; font-weight: bold;">Diplôme</td> 
                     <td> ' . htmlspecialchars($diplome) . '</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Filière</td> 
                     <td> ' . htmlspecialchars($filiereDescription) . '</td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Footer avec informations de l\'école et signature électronique -->
    <div style="margin-top: 60px; padding-top: 15px; border-top: 1px solid #ccc; font-size: 9pt;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60%; vertical-align: top; line-height: 1.5;">
                    <div style="font-weight: bold; margin-bottom: 2px;">Université Abdelmalek Essaâdi - ENSA Tétouan</div>
                    <div style="margin-bottom: 2px;">École Nationale des Sciences Appliquées de Tétouan</div>
                    <div style="margin-bottom: 2px;">Avenue de Sebta, Mhannech II 95002 - Tétouan - Maroc</div>
                    <div>www.ensatetouan.ac.ma</div>
                </td>
                <td style="width: 40%; vertical-align: top; text-align: right;">
                    <div style="display: inline-block; text-align: left;">
                        <div style="font-weight: bold; margin-bottom: 2px;">Signé électroniquement</div>
                        <div style="margin-bottom: 2px;">par Pr. Kamal Reklaoui</div>
                        <div style="margin-bottom: 2px;">Le: ' . $dateGeneration . '</div>
                        <div style="margin-bottom: 2px;">' . $heureGeneration . '</div>
                        <div style="margin-bottom: 2px;">Réf: ' . $refDoc . '</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>';
    
    return $html;
}


function generate_attestation_reussite_html(array $request, string $logoUrl): string
{
    // Formatage des dates
    setlocale(LC_TIME, 'fr_FR.UTF8', 'fra');
    
    // Date de naissance
    $dateNaissance = '';
    $lieuNaissance = '';
    if (!empty($request['etu_date_naissance'])) {
        $date = new DateTime($request['etu_date_naissance']);
        $dateNaissance = $date->format('d/m/Y');
    }
    if (!empty($request['etu_lieu_naissance'])) {
        $lieuNaissance = strtoupper(htmlspecialchars($request['etu_lieu_naissance']));
    }

    $dateGeneration = date('d/m/Y');
    $heureGeneration = date('H:i:s');
    $refDoc = htmlspecialchars($request['numero_reference'] ?? '');
    
    // Année universitaire
    $anneeUniv = '';
    if (!empty($request['ar_annee'])) {
        // Convertir format 2023-2024 en 2023/2024 pour l'affichage
        $anneeUniv = str_replace('-', '/', $request['ar_annee']);
    } elseif (!empty($request['annee_debut']) && !empty($request['annee_fin'])) {
        $anneeUniv = $request['annee_debut'] . '/' . $request['annee_fin'];
    } else {
        // Utiliser l'année en cours comme fallback
        $currentYear = (int)date('Y');
        $anneeUniv = $currentYear . '/' . ($currentYear + 1);
    }

    // Informations Étudiant
    $nomComplet = strtoupper(htmlspecialchars($request['etu_nom'] . ' ' . $request['etu_prenom']));
    $cne = htmlspecialchars($request['etu_cin']); // Utilisation du CIN comme CNE si pas de champ CNE distinct
    
    // Formater la description de la filière selon son nom
    $filiereRaw = !empty($request['filiere_nom']) ? $request['filiere_nom'] : '';
    $filiere = '';
    if ($filiereRaw === '2AP1') {
        $filiere = '1ère année classe préparatoire (2AP1)';
    } elseif ($filiereRaw === '2AP2') {
        $filiere = '2ème année classe préparatoire (2AP2)';
    } elseif ($filiereRaw === 'Génie Informatique 1') {
        $filiere = 'Génie Informatique 1';
    } elseif ($filiereRaw === 'Génie Informatique 2') {
        $filiere = 'Génie Informatique 2';
    } elseif ($filiereRaw === 'Génie Informatique 3') {
        $filiere = 'Génie Informatique 3';
    } elseif (!empty($filiereRaw)) {
        // Fallback: utiliser directement le nom de la filière
        $filiere = $filiereRaw;
    } else {
        $filiere = 'Non spécifiée';
    }
    $filiere = htmlspecialchars($filiere);
    
    // Mention
    $mention = htmlspecialchars($request['mention'] ?? 'Passable');

    $html = '
    <!-- Header -->
    <div class="header" style="text-align: center; margin-bottom: 20px;">
        <img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="max-height: 70px; margin-bottom: 10px;" />
        <div style="font-weight: bold; font-size: 11pt; margin-bottom: 3px;">Université Abdelmalek Essaâdi</div>
        <div style="font-weight: bold; font-size: 10pt;">ENSA Tétouan - École Nationale des Sciences Appliquées</div>
    </div>

    <!-- Titre -->
    <div style="text-align: center; margin-bottom: 25px;">
        <h1 style="font-size: 18pt; text-decoration: underline; font-family: serif; letter-spacing: 1px; margin: 0;">ATTESTATION DE REUSSITE</h1>
    </div>

    <div style="font-size: 10pt; line-height: 1.5; font-family: sans-serif;">
        
        <p style="margin-bottom: 15px;">
            Le Directeur de l\'École Nationale des Sciences Appliquées de Tétouan, atteste que :
        </p>

        <div style="margin-left: 20px; margin-bottom: 15px;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 5px;">
                <tr>
                    <td style="width: 150px; font-weight: bold;">Mr (Mlle)</td>
                     <td> <strong>' . $nomComplet . '</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Né (e) le</td>
                     <td> ' . $dateNaissance . ' &nbsp;&nbsp;&nbsp; à ' . $lieuNaissance . '</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">CIN</td>
                     <td> ' . $cne . '</td>
                </tr>
            </table>
        </div>

        <p style="margin-bottom: 15px;">
            a réussi les examens de sa filière en validant tous les modules composant la :
        </p>

        <div style="margin-left: 20px; margin-bottom: 20px;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 5px;">
                <tr>
                    <td style="width: 150px; font-weight: bold;">Filière</td>
                     <td> ' . $filiere . '</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Année universitaire</td>
                     <td> <strong>' . htmlspecialchars($anneeUniv) . '</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Avec mention</td>
                     <td> <strong>' . $mention . '</strong></td>
                </tr>
            </table>
        </div>

    </div>

    <!-- Footer avec informations de l\'école et signature électronique -->
    <div style="margin-top: 60px; padding-top: 15px; border-top: 1px solid #ccc; font-size: 9pt;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60%; vertical-align: top; line-height: 1.5;">
                    <div style="font-weight: bold; margin-bottom: 2px;">Université Abdelmalek Essaâdi - ENSA Tétouan</div>
                    <div style="margin-bottom: 2px;">École Nationale des Sciences Appliquées de Tétouan</div>
                    <div style="margin-bottom: 2px;">Avenue de Sebta, Mhannech II 95002 - Tétouan - Maroc</div>
                    <div>www.ensatetouan.ac.ma</div>
                </td>
                <td style="width: 40%; vertical-align: top; text-align: right;">
                    <div style="display: inline-block; text-align: left;">
                        <div style="font-weight: bold; margin-bottom: 2px;">Signé électroniquement</div>
                        <div style="margin-bottom: 2px;">par Pr. Kamal Reklaoui</div>
                        <div style="margin-bottom: 2px;">Le: ' . $dateGeneration . '</div>
                        <div style="margin-bottom: 2px;">' . $heureGeneration . '</div>
                        <div style="margin-bottom: 2px;">Réf: ' . $refDoc . '</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>';

    return $html;
}

function generate_releve_notes_html(array $request, ?PDO $pdo = null, string $logoUrl = ''): string
{
    // Formatage des données de base
    $nomComplet = strtoupper(htmlspecialchars($request['etu_nom'] . ' ' . $request['etu_prenom']));
    $numeroEtudiant = htmlspecialchars($request['etu_apogee']);
    $cne = htmlspecialchars($request['etu_cin']);
    
    // Définir les variables pour le footer
    $heureGeneration = date('H:i:s');
    $refDoc = htmlspecialchars($request['numero_reference'] ?? '');
    
    // Date et lieu de naissance
    $dateNaissance = '';
    $lieuNaissance = '';
    if (!empty($request['etu_date_naissance'])) {
        $date = new DateTime($request['etu_date_naissance']);
        $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
                 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $dateNaissance = $date->format('d') . ' ' . $mois[(int)$date->format('n')] . ' ' . $date->format('Y');
    }
    if (!empty($request['etu_lieu_naissance'])) {
        $lieuNaissance = strtoupper(htmlspecialchars($request['etu_lieu_naissance']));
    }
    
    // Année universitaire
    $anneeUniv = '';
    if (!empty($request['annee_debut']) && !empty($request['annee_fin'])) {
        $anneeUniv = $request['annee_debut'] . '/' . $request['annee_fin'];
    } else {
        $currentYear = (int)date('Y');
        $anneeUniv = $currentYear . '/' . ($currentYear + 1);
    }
    
    // Semestre demandé
    $semestre = htmlspecialchars($request['rn_semestre'] ?? 'S1');
    
    // Récupérer le numéro de semestre pour filtrer les modules
    $semestreNum = null;
    if (!empty($request['rn_semestre'])) {
        $semestreStr = trim($request['rn_semestre']);
        $semestreNum = (int) preg_replace('/^S/i', '', $semestreStr);
    }
    
    // Utiliser la filière depuis inscription_etudiant (déjà récupérée dans la requête principale)
    $filiereNom = !empty($request['filiere_nom']) ? htmlspecialchars($request['filiere_nom']) : '';
    $inscritEn = $filiereNom;
    
    // Formater le libellé selon le type de filière
    if ($filiereNom === '2AP1') {
        $inscritEn = '1ère année classe préparatoire (2AP1)';
    } elseif ($filiereNom === '2AP2') {
        $inscritEn = '2ème année classe préparatoire (2AP2)';
    } elseif (!empty($filiereNom)) {
        $inscritEn = $filiereNom;
    } else {
        $inscritEn = 'Non spécifiée';
    }
    
    // Récupérer l'année universitaire depuis rn_annee si disponible, sinon depuis annee_debut/annee_fin
    $anneeUnivReleve = '';
    if (!empty($request['rn_annee'])) {
        $anneeUnivReleve = $request['rn_annee'];
    } elseif (!empty($request['annee_debut']) && !empty($request['annee_fin'])) {
        $anneeUnivReleve = $request['annee_debut'] . '-' . $request['annee_fin'];
    }
    
    // Récupérer les notes des modules si PDO est fourni
    $modules = [];
    $totalNotes = 0;
    $nombreModules = 0;
    
    if ($pdo && !empty($request['id_etudiant'])) {
        try {
            // Utiliser le $semestreNum déjà calculé plus haut pour filtrer les modules
            
            // Construire la requête avec filtrage par semestre et année universitaire
            // On utilise DISTINCT pour éviter les doublons si un module est dans plusieurs filières
            // L'année universitaire vient de inscription_etudiant via la filière
            $sqlModules = "
                SELECT DISTINCT
                    m.nom_module,
                    im.note,
                    im.session,
                    mf.semestre,
                    f.nom_filiere,
                    CONCAT(au.annee_debut, '-', au.annee_fin) as annee_universitaire
                FROM inscrit_module im
                JOIN module m ON m.id_module = im.id_module
                LEFT JOIN module_filiere mf ON mf.id_module = m.id_module
                LEFT JOIN inscription_etudiant ie ON ie.id_etudiant = im.id_etudiant AND ie.id_filiere = mf.id_filiere
                LEFT JOIN filiere f ON f.id_filiere = ie.id_filiere
                LEFT JOIN annee_universitaire au ON au.id_annee = ie.id_annee
                WHERE im.id_etudiant = :id_etudiant
            ";
            
            $params = [':id_etudiant' => $request['id_etudiant']];
            
            // Filtrer par semestre si spécifié
            if ($semestreNum !== null && $semestreNum > 0) {
                $sqlModules .= " AND mf.semestre = :semestre";
                $params[':semestre'] = $semestreNum;
            }
            
            // Filtrer par année universitaire si spécifiée
            // Comparer avec le format YYYY-YYYY depuis annee_universitaire
            if (!empty($request['rn_annee'])) {
                $anneeUniv = $request['rn_annee'];
                // Si le format est YYYY-YYYY, l'utiliser tel quel
                // Sinon, essayer de le convertir
                if (!preg_match('/^\d{4}-\d{4}$/', $anneeUniv)) {
                    // Si c'est juste YYYY, créer YYYY-YYYY+1
                    if (preg_match('/^\d{4}$/', $anneeUniv)) {
                        $anneeUniv = $anneeUniv . '-' . ((int)$anneeUniv + 1);
                    }
                }
                $sqlModules .= " AND CONCAT(au.annee_debut, '-', au.annee_fin) = :annee_univ";
                $params[':annee_univ'] = $anneeUniv;
            }
            
            $sqlModules .= " ORDER BY m.id_module";
            
            $stmtModules = $pdo->prepare($sqlModules);
            $stmtModules->execute($params);
            $modules = $stmtModules->fetchAll();
            
            // Calculer la moyenne
            foreach ($modules as $module) {
                if ($module['note'] !== null) {
                    $totalNotes += (float)$module['note'];
                    $nombreModules++;
                }
            }
        } catch (Exception $e) {
            error_log("Erreur récupération modules: " . $e->getMessage());
        }
    }
    
    // Moyenne et mention
    if ($nombreModules > 0) {
        $moyenneCalculee = $totalNotes / $nombreModules;
        $moyenne = number_format($moyenneCalculee, 3);
    } else {
        $moyenne = !empty($request['moyenne']) ? number_format((float)$request['moyenne'], 3) : '0.000';
        $moyenneCalculee = (float)$moyenne;
    }
    
    // Calculer le statut d'admission en fonction de la moyenne du semestre et de la filière
    // Récupérer la filière pour déterminer le seuil de validation
    $nomFiliere = null;
    if (!empty($modules) && isset($modules[0]['nom_filiere'])) {
        $nomFiliere = $modules[0]['nom_filiere'];
    } elseif (!empty($request['filiere_nom'])) {
        $nomFiliere = $request['filiere_nom'];
    }
    
    // Déterminer le seuil selon la filière
    $seuilValidation = get_seuil_validation($nomFiliere ?? '');
    
    // Un étudiant est admis si sa moyenne atteint le seuil de validation de sa filière
    // 2AP1/2AP2: >= 10/20, Génie Informatique: >= 12/20
    $estAdmis = $moyenneCalculee >= $seuilValidation;
    $resultatAdmission = $estAdmis ? 'Admis' : 'Non admis';
    
    // Calculer la mention en fonction de la moyenne
    if ($moyenneCalculee >= 16.0) {
        $mention = 'Très Bien';
    } elseif ($moyenneCalculee >= 14.0) {
        $mention = 'Bien';
    } elseif ($moyenneCalculee >= 12.0) {
        $mention = 'Assez Bien';
    } elseif ($moyenneCalculee >= 10.0) {
        $mention = 'Passable';
    } else {
        $mention = 'Insuffisant';
    }
    $mention = htmlspecialchars($mention);
    
    // Date de génération
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
             'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $dateGeneration = date('d') . ' ' . $mois[(int)date('n')] . ' ' . date('Y');
    
    // Construction du HTML
    $html = '
    <!-- Header -->
    <div class="header" style="text-align: center; margin-bottom: 12px;">
        <img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="max-height: 55px; margin-bottom: 5px;" />
        <div style="font-weight: bold; font-size: 10pt; margin-bottom: 2px;">Université Abdelmalek Essaâdi</div>
        <div style="font-weight: bold; font-size: 9pt;">ENSA Tétouan - École Nationale des Sciences Appliquées</div>
    </div>

    <!-- Titre -->
    <div style="text-align: center; margin-bottom: 12px;">
        <h1 style="font-size: 16pt; text-decoration: underline; font-family: serif; letter-spacing: 0.5px; margin: 0;">
        RELEVÉ DE NOTES ET RÉSULTATS
        </h1>
    </div>
    
    <!-- Semestre -->
    <div style="text-align: center; border: 1px solid #000; padding: 4px; margin: 6px 0; font-size: 10pt; font-weight: bold;">
        Semestre : <strong>' . $semestre . '</strong>
    </div>
    
    <!-- Informations étudiant -->
    <div style="margin: 10px 0; font-size: 9pt; line-height: 1.4;">
        <div style="margin-bottom: 3px;"><strong>' . $nomComplet . '</strong></div>
        <div style="margin-bottom: 3px;">
            N° Étudiant : <strong>' . $numeroEtudiant . '</strong>
            &nbsp;&nbsp;&nbsp;
            CIN : <strong>' . $cne . '</strong>
        </div>';
    
    if ($dateNaissance && $lieuNaissance) {
        $html .= '
        <div style="margin-bottom: 3px;">
            Né(e) le : <strong>' . $dateNaissance . '</strong> à <strong>' . $lieuNaissance . '</strong>
        </div>';
    }
    
    $html .= '
        <div style="margin-bottom: 3px;">
            inscrit(e) en <strong>' . htmlspecialchars($inscritEn) . '</strong>';
    
    // Afficher l'année universitaire si disponible
    if ($anneeUnivReleve) {
        $anneeUnivDisplay = str_replace('-', '/', $anneeUnivReleve);
        $html .= ' - Année universitaire <strong>' . htmlspecialchars($anneeUnivDisplay) . '</strong>';
    }
    
    $html .= '
        </div>
        <div style="margin-top: 6px;">a obtenu les notes suivantes :</div>
    </div>
    
    <!-- Tableau des notes avec résultat final -->
    <table border="1" style="width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 9pt;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="padding: 5px; text-align: left; width: 40%;">Module</th>
                <th style="padding: 5px; text-align: center; width: 20%;">Note/Barème</th>
                <th style="padding: 5px; text-align: center; width: 20%;">Résultat</th>
                <th style="padding: 5px; text-align: center; width: 20%;">Session</th>
            </tr>
        </thead>
        <tbody>';
    
    // Afficher les modules
    if (!empty($modules)) {
        foreach ($modules as $module) {
            $nomModule = htmlspecialchars($module['nom_module']);
            $noteModule = $module['note'] !== null ? number_format((float)$module['note'], 2) . '/20' : '-';
            
            // Calculer dynamiquement si le module est validé selon la note et la filière
            $estValide = false;
            if ($module['note'] !== null && !empty($module['nom_filiere'])) {
                $estValide = is_module_valide((float)$module['note'], $module['nom_filiere']);
            }
            $resultat = $estValide ? 'Validé' : 'Non Validé';
            
            // La session indique directement si c'est Normal ou Rattrapage (depuis la colonne session)
            $sessionModule = htmlspecialchars($module['session'] ?? ($estValide ? 'Normal' : 'Rattrapage'));
            
            $html .= '
            <tr>
                <td style="padding: 4px;">' . $nomModule . '</td>
                <td style="padding: 4px; text-align: center;">' . $noteModule . '</td>
                <td style="padding: 4px; text-align: center;">' . $resultat . '</td>
                <td style="padding: 4px; text-align: center;">' . $sessionModule . '</td>
            </tr>';
        }
    } else {
        $html .= '
            <tr>
                <td colspan="4" style="padding: 15px; text-align: center; font-style: italic;">
                    Aucune note disponible pour cet étudiant.
                </td>
            </tr>';
    }
    
    // Ligne du résultat final dans le même tableau
    $html .= '
            <tr style="background-color: #f0f0f0; font-weight: bold;">
                <td style="padding: 5px;">Résultat d\'admission ' . strtolower($semestre) . ' :</td>
                <td style="padding: 5px; text-align: center;"><strong>' . $moyenne . '/20</strong></td>
                <td style="padding: 5px; text-align: center;"><strong>' . $resultatAdmission . '</strong></td>
                <td style="padding: 5px; text-align: center;"><strong>' . $mention . '</strong></td>
            </tr>
        </tbody>
    </table>
    
    <!-- Footer avec informations de l\'école et signature électronique -->
    <div style="margin-top: 25px; padding-top: 8px; border-top: 1px solid #ccc; font-size: 8pt;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60%; vertical-align: top; line-height: 1.5;">
                    <div style="font-weight: bold; margin-bottom: 2px;">Université Abdelmalek Essaâdi - ENSA Tétouan</div>
                    <div style="margin-bottom: 2px;">École Nationale des Sciences Appliquées de Tétouan</div>
                    <div style="margin-bottom: 2px;">Avenue de Sebta, Mhannech II 95002 - Tétouan - Maroc</div>
                    <div>www.ensatetouan.ac.ma</div>
                </td>
                <td style="width: 40%; vertical-align: top; text-align: right;">
                    <div style="display: inline-block; text-align: left;">
                        <div style="font-weight: bold; margin-bottom: 2px;">Signé électroniquement</div>
                        <div style="margin-bottom: 2px;">par Pr. Kamal Reklaoui</div>
                        <div style="margin-bottom: 2px;">Le: ' . $dateGeneration . '</div>
                        <div style="margin-bottom: 2px;">' . $heureGeneration . '</div>
                        <div style="margin-bottom: 2px;">Réf: ' . $refDoc . '</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>';
    
    return $html;
}


function generate_convention_stage_html(array $request, string $logoUrl): string
{
    // Formatage des dates
    setlocale(LC_TIME, 'fr_FR.UTF8', 'fra');
    $dateDebut = $request['date_debut_stage'] ? date('d/m/Y', strtotime($request['date_debut_stage'])) : '....................';
    $dateFin = $request['date_fin_stage'] ? date('d/m/Y', strtotime($request['date_fin_stage'])) : '....................';
    
    // Date de génération formatée
    $moisFr = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    $dateGeneration = date('d') . '-' . $moisFr[(int)date('n')] . '-' . date('Y') . ' ' . date('H:i:s');
    
    // Année universitaire
    $anneeUniv = '';
    if (!empty($request['annee_debut']) && !empty($request['annee_fin'])) {
        $anneeUniv = $request['annee_debut'] . '/' . $request['annee_fin'];
    } else {
        $currentYear = (int)date('Y');
        $anneeUniv = $currentYear . '/' . ($currentYear + 1);
    }

    // Informations Étudiant
    $etudiantNom = strtoupper(htmlspecialchars($request['etu_nom'] . ' ' . $request['etu_prenom']));
    $etudiantApogee = htmlspecialchars($request['etu_apogee']);
    $etudiantCin = htmlspecialchars($request['etu_cin']);
    $filiere = htmlspecialchars($request['filiere_nom'] ?? '...........................');
    
    // Informations Entreprise
    $entrepriseNom = htmlspecialchars($request['nom_entreprise'] ?? '...........................');
    $entrepriseAdresse = htmlspecialchars($request['adresse_entreprise'] ?? '...........................');
    $entrepriseTel = htmlspecialchars($request['telephone_responsable_entreprise'] ?? '...........................');
    $entrepriseEmail = htmlspecialchars($request['email_responsable_entreprise'] ?? '...........................');
    $entrepriseResp = htmlspecialchars($request['nom_responsable_entreprise'] ?? '...........................');
    $entrepriseRespFonction = 'Responsable'; // Fonction par défaut car non stockée en base
    
    // Stage
    $sujet = htmlspecialchars($request['sujet_stage'] ?? '...........................');
    $profEncadrant = htmlspecialchars(($request['prof_prenom'] ?? '') . ' ' . ($request['prof_nom'] ?? ''));
    if (empty(trim($profEncadrant))) {
        $profEncadrant = '...........................';
    }

    $html = '
     <!-- Header -->
    <div class="header" style="text-align: center; margin-bottom: 8px;">
        <img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="max-height: 60px; margin-bottom: 5px;" />
        <div style="font-weight: bold; font-size: 11pt; margin-bottom: 2px;">Université Abdelmalek Essaâdi</div>
        <div style="font-weight: bold; font-size: 10pt;">ENSA Tétouan - École Nationale des Sciences Appliquées</div>
    </div>

    <!-- Titre -->
    <div style="text-align: center; margin-bottom: 8px;">
        <h1 style="font-size: 18pt; text-decoration: underline; font-family: serif; letter-spacing: 1px; margin: 0;">CONVENTION DE STAGE</h1>
    </div>
    <div style="text-align: center; margin-bottom: 5px;">
        <div style="font-size: 10pt; font-style: italic; text-decoration: underline;">(2 exemplaires imprimés en recto-verso)</div>
    </div>

    <div style="font-size: 9pt; line-height: 1.35; text-align: justify; font-family: Calibri, Arial, sans-serif;">
        
        <!-- L\'Établissement -->
        <p style="margin-bottom: 2px;">
            L\'Ecole Nationale des Sciences Appliquées, Université Abdelmalek Essaâdi - Tétouan
        </p>
        <p style="margin-bottom: 2px;">
            B.P. 2222, Mhannech II, Tétouan , Maroc
        </p>
        <p style="margin-bottom: 2px;">
            Tél. +212 5 39 68 80 27 ; Fax. +212 39 99 46 24. Web: <strong><a href="https://ensa-tetouan.ac.ma">https://ensa-tetouan.ac.ma</a></strong>
        </p>
        <p style="margin-bottom: 6px;">
            Représenté par le Professeur <strong>Kamal REKLAOUI</strong> en qualité de Directeur.
        </p>
        <p style="text-align: right; margin-bottom: 8px;">
            Ci-après, dénommé <strong>l\'Etablissement</strong>
        </p>

        <div style="text-align: center; margin: 4px 0; font-size: 10pt;">ET</div>

        <!-- L\'Entreprise -->
        <p style="margin-bottom: 2px;">
            La Société : <strong>' . $entrepriseNom . '</strong>
        </p>
        <p style="margin-bottom: 2px;">
            Adresse :  <strong>' . $entrepriseAdresse . '</strong>
        </p>
        <p style="margin-bottom: 2px;">
            Tél : <strong>' . $entrepriseTel . '</strong> &nbsp;&nbsp;&nbsp; Email: <strong>' . $entrepriseEmail . '</strong>
        </p>
        <p style="margin-bottom: 6px;">
            Représentée par Monsieur <strong>' . $entrepriseResp . '</strong> en qualité <strong>' . $entrepriseRespFonction . '</strong>
        </p>
        <p style="text-align: right; margin-bottom: 10px;">
            Ci-après dénommée <strong>L\'ENTREPRISE</strong>
        </p>

        <!-- Article 1 -->
        <p style="margin-bottom: 2px;"><strong>Article 1 : Engagement</strong></p>
        <p style="margin-bottom: 6px;">
            <strong>L\'ENTREPRISE</strong> accepte de recevoir à titre de stagiaire <strong>' . $etudiantNom . '</strong> étudiant de la filière du Cycle Ingénieur « <strong>' . $filiere . '</strong> » de l\'ENSA de Tétouan, Université Abdelmalek Essaâdi (Tétouan), pour une période allant du <strong>' . $dateDebut . '</strong> au <strong>' . $dateFin . '</strong>
        </p>
        <p style="margin-bottom: 6px; font-weight: bold;">
            En aucun cas, cette convention ne pourra autoriser les étudiants à s\'absenter durant la période des contrôles ou des enseignements.
        </p>

        <!-- Article 2 -->
        <p style="margin-bottom: 2px;"><strong>Article 2 : Objet</strong></p>
        <p style="margin-bottom: 6px;">
            Le stage aura pour objet essentiel d\'assurer l\'application pratique de l\'enseignement donné par <strong>l\'Etablissement</strong>, et ce, en organisant des visites sur les installations et en réalisant des études proposées par <strong>L\'ENTREPRISE</strong>.
        </p>

        <!-- Article 3 -->
        <p style="margin-bottom: 2px;"><strong>Article 3 : Encadrement et suivi</strong></p>
        <p style="margin-bottom: 4px;">
            Pour accompagner le Stagiaire durant son stage, et ainsi instaurer une véritable collaboration L\'ENTREPRISE/Stagiaire/Etablissement, L\'ENTREPRISE désigne Mme/Mr <strong>' . $entrepriseResp . '</strong> encadrant(e) et parrain(e), pour superviser et assurer la qualité du travail fourni par le Stagiaire.
        </p>
        <p style="margin-bottom: 6px;">
            L\'Etablissement désigne <strong>' . $profEncadrant . '</strong> en tant que tuteur qui procurera une assistance pédagogique
        </p>

        <!-- Article 4 -->
        <p style="margin-bottom: 2px;"><strong>Article 4 : Programme:</strong></p>
        <p style="margin-bottom: 4px;">
            Le thème du stage est: <strong>« ' . $sujet . ' »</strong>
        </p>
        <p style="margin-bottom: 4px;">
            Ce programme a été défini conjointement par <strong>l\'Etablissement</strong>, <strong>L\'ENTREPRISE</strong> et le <strong>Stagiaire</strong>.
        </p>
        <p style="margin-bottom: 6px;">
            Le contenu de ce programme doit permettre au Stagiaire une réflexion en relation avec les enseignements ou le projet de fin d\'études qui s\'inscrit dans le programme de formation de <strong>l\'Etablissement</strong>.
        </p>

        <!-- Article 5 -->
        <p style="margin-bottom: 2px;"><strong>Article 5 : Indemnité de stage</strong></p>
        <p style="margin-bottom: 4px;">
            Au cours du stage, l\'étudiant ne pourra prétendre à aucun salaire de la part de <strong>L\'ENTREPRISE</strong>.
        </p>
        <p style="margin-bottom: 6px;">
            Cependant, si <strong>l\'ENTREPRISE</strong> et l\'étudiant le conviennent, ce dernier pourra recevoir une indemnité forfaitaire de la part de l\'ENTREPRISE des frais occasionnés par la mission confiée à l\'étudiant.
        </p>

        <!-- Article 6 -->
        <p style="margin-bottom: 2px;"><strong>Article 6 : Règlement</strong></p>
        <p style="margin-bottom: 4px;">
            Pendant la durée du stage, le Stagiaire reste placé sous la responsabilité de <strong>l\'Etablissement</strong>.
        </p>
        <p style="margin-bottom: 4px; font-weight: bold;">
            Cependant, l\'étudiant est tenu d\'informer l\'école dans un délai de 24h sur toute modification portant sur la convention déjà signée, sinon il en assumera toute sa responsabilité sur son non-respect de la convention signée par l\'école.
        </p>
        <p style="margin-bottom: 4px;">
            Toutefois, le Stagiaire est soumis à la discipline et au règlement intérieur de <strong>L\'ENTREPRISE</strong>.
        </p>
        <p style="margin-bottom: 6px;">
            En cas de manquement, <strong>L\'ENTREPRISE</strong> se réserve le droit de mettre fin au stage après en avoir convenu avec le Directeur de l\'Etablissement.
        </p>

        <!-- Article 7 -->
        <p style="margin-bottom: 2px;"><strong>Article 7 : Confidentialité</strong></p>
        <p style="margin-bottom: 6px;">
            Le Stagiaire et l\'ensemble des acteurs liés à son travail (l\'administration de <strong>l\'Etablissement</strong>, le parrain pédagogique ...) sont tenus au secret professionnel. Ils s\'engagent à ne pas diffuser les informations recueillies à des fins de publications, conférences, communications, sans raccord préalable de <strong>L\'ENTREPRISE</strong>. Cette obligation demeure valable après l\'expiration du stage
        </p>

        <!-- Article 8 -->
        <p style="margin-bottom: 2px;"><strong>Article 8 : Assurance accident de travail</strong></p>
        <p style="margin-bottom: 4px;">
            <strong>Le stagiaire</strong> devra obligatoirement souscrire une assurance couvrant la Responsabilité Civile et Accident de Travail, durant les stages et trajets effectués.
        </p>
        <p style="margin-bottom: 6px;">
            En cas d\'accident de travail survenant durant la période du stage, <strong>L\'ENTREPRISE</strong> s\'engage à faire parvenir immédiatement à l\'Etablissement toutes les informations indispensables à la déclaration dudit accident.
        </p>

        <!-- Article 9 -->
        <p style="margin-bottom: 2px;"><strong>Article 9: Evaluation de L\'ENTREPRISE</strong></p>
        <p style="margin-bottom: 4px;">
            Le stage accompli, le parrain établira un rapport d\'appréciations générales sur le travail effectué et le comportement du Stagiaire durant son séjour chez <strong>L\'ENTREPRISE</strong>.
        </p>
        <p style="margin-bottom: 6px;">
            <strong>L\'ENTREPRISE</strong> remettra au Stagiaire une attestation indiquant la nature et la durée des travaux effectués.
        </p>

        <!-- Article 10 -->
        <p style="margin-bottom: 2px;"><strong>Article 10 : Rapport de stage</strong></p>
        <p style="margin-bottom: 10px;">
            A l\'issue de chaque stage, le Stagiaire rédigera un rapport de stage faisant état de ses travaux et de son vécu au sein de <strong>L\'ENTREPRISE</strong>. Ce rapport sera communiqué à <strong>L\'ENTREPRISE</strong> et restera strictement confidentiel.
        </p>

        <!-- Fait à -->
        <p style="text-align: center; margin: 15px 0;">
            Fait à Tétouan en deux exemplaires, le <strong>' . $dateGeneration . '</strong>
        </p>

    </div>

    <!-- Signatures - 4 blocs comme dans le Word -->
    <table style="border-collapse: collapse; font-size: 7pt; font-family: Calibri, Arial, sans-serif; margin-top: 8px;">
        <tr>
            <td style=" text-align: center; vertical-align: top; padding: 4px;">
                Nom et signature du Stagiaire<br><br>
                <div style="border: 1px solid #fff; min-height: 20px; padding: 2px;">
                    <strong>' . $etudiantNom . '</strong>
                </div>
            </td>
            <td style=" text-align: center; vertical-align: top; padding: 4px;">
                Le Coordonnateur de la filière<br><br>
                <div style="min-height: 20px;"></div>
            </td>
        </tr>
        <tr>
            <td style=" text-align: center; vertical-align: top; padding: 4px;">
                Signature et cachet de L\'Etablissement<br><br>
                <div style="min-height: 20px;"></div>
            </td>
            <td style=" text-align: center; vertical-align: top; padding: 4px;">
                Signature et cachet de L\'ENTREPRISE<br><br>
                <div style="min-height: 20px;"></div>
            </td>
        </tr>
    </table>
    ';

    return $html;
}

function handle_get_complaints(PDO $pdo): void
{
    try {
        // Vérifier si les colonnes existent (pour compatibilité avec/sans migration)
        try {
            $testCols = $pdo->query("SELECT statut, reponse, date_reponse FROM reclamations LIMIT 1");
            $hasStatusColumns = true;
        } catch (\PDOException $e) {
            $hasStatusColumns = false;
        }

        if ($hasStatusColumns) {
            $sql = <<<SQL
SELECT 
  r.id_reclamation,
  r.numero_reference,
  r.date_reclamation,
  r.description,
  r.objet,
  r.statut,
  r.reponse,
  r.date_reponse,
  r.id_demande,
  d.numero_reference AS demande_ref,
  e.email AS etu_email,
  e.numero_apogee AS etu_apogee,
  e.cin AS etu_cin
FROM reclamations r
JOIN etudiants e ON e.id_etudiant = r.id_etudiant
LEFT JOIN demandes d ON d.id_demande = r.id_demande
ORDER BY r.date_reclamation DESC
SQL;
        } else {
            $sql = <<<SQL
SELECT 
  r.id_reclamation,
  r.numero_reference,
  r.date_reclamation,
  r.description,
  r.objet,
  NULL AS statut,
  NULL AS reponse,
  NULL AS date_reponse,
  r.id_demande,
  d.numero_reference AS demande_ref,
  e.email AS etu_email,
  e.numero_apogee AS etu_apogee,
  e.cin AS etu_cin
FROM reclamations r
JOIN etudiants e ON e.id_etudiant = r.id_etudiant
LEFT JOIN demandes d ON d.id_demande = r.id_demande
ORDER BY r.date_reclamation DESC
SQL;
        }

        $rows = $pdo->query($sql)->fetchAll();
    } catch (\PDOException $e) {
        error_log('Erreur lors de la récupération des réclamations: ' . $e->getMessage());
        send_error('Erreur lors de la récupération des réclamations', 500);
    }

    $payload = array_map(function ($row) {
        // Utiliser map_status si la colonne statut existe, sinon déterminer depuis reponse
        $status = 'pending';
        if (isset($row['statut'])) {
            $status = map_status($row['statut']);
        } elseif (!empty($row['reponse'])) {
            $status = 'resolved';
        }
        
        // Format dates properly - ensure ISO 8601 format with time
        $createdAt = $row['date_reclamation'];
        // Si date_reclamation est une chaîne et ne contient pas d'heure (format DATE: 'YYYY-MM-DD')
        // alors ajouter minuit. Sinon, garder l'heure telle quelle (format DATETIME: 'YYYY-MM-DD HH:MM:SS')
        if ($createdAt) {
            $dateStr = is_string($createdAt) ? $createdAt : (string)$createdAt;
            // Vérifier si c'est un format DATE seulement (10 caractères: YYYY-MM-DD)
            // ou s'il contient déjà l'heure (19 caractères: YYYY-MM-DD HH:MM:SS ou plus)
            if (strlen(trim($dateStr)) === 10 && !str_contains($dateStr, ' ') && !str_contains($dateStr, 'T')) {
                // Format DATE uniquement, ajouter l'heure minuit pour les anciennes données
                $createdAt = $dateStr . ' 00:00:00';
            }
            // Sinon, garder tel quel (déjà au format DATETIME avec l'heure)
        }
        $respondedAt = $row['date_reponse'] ?? null;
        // Si date_reponse est une chaîne et ne contient pas d'heure (format DATE: 'YYYY-MM-DD')
        // alors ajouter minuit. Sinon, garder l'heure telle quelle (format DATETIME: 'YYYY-MM-DD HH:MM:SS')
        if ($respondedAt) {
            // Vérifier si c'est un format DATE seulement (10 caractères: YYYY-MM-DD)
            // ou s'il contient déjà l'heure (19 caractères: YYYY-MM-DD HH:MM:SS ou plus)
            $dateStr = is_string($respondedAt) ? $respondedAt : (string)$respondedAt;
            if (strlen(trim($dateStr)) === 10 && !str_contains($dateStr, ' ') && !str_contains($dateStr, 'T')) {
                // Format DATE uniquement, ajouter l'heure minuit
                $respondedAt = $dateStr . ' 00:00:00';
            }
            // Sinon, garder tel quel (déjà au format DATETIME avec l'heure)
        }
        
        return [
            'id' => $row['id_reclamation'],
            'referenceNumber' => $row['numero_reference'],
            'subject' => $row['objet'],
            'description' => $row['description'],
            'status' => $status,
            'createdAt' => $createdAt,
            'response' => $row['reponse'] ?? null,
            'respondedAt' => $respondedAt,
            'studentEmail' => $row['etu_email'],
            'apogee' => $row['etu_apogee'],
            'cin' => $row['etu_cin'],
            'relatedRequestNumber' => $row['demande_ref'] ?? null,
        ];
    }, $rows);

    send_json($payload);
}

function handle_create_complaint(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error('Invalid JSON in request body', 400);
    }
    
    // Validation des champs obligatoires
    if (!isset($input['email']) || !isset($input['apogee']) || !isset($input['cin']) 
        || !isset($input['subject']) || !isset($input['description']) 
        || !isset($input['relatedRequestNumber']) || empty(trim($input['relatedRequestNumber']))) {
        send_error('Missing required fields: email, apogee, cin, subject, description, relatedRequestNumber', 400);
    }

    // Vérifier que l'étudiant existe avec ces informations
    $stmt = $pdo->prepare('
        SELECT id_etudiant FROM etudiants 
        WHERE email = :email AND numero_apogee = :apogee AND cin = :cin
    ');
    $stmt->execute([
        ':email' => $input['email'],
        ':apogee' => $input['apogee'],
        ':cin' => $input['cin'],
    ]);
    $student = $stmt->fetch();
    
    if (!$student) {
        send_error('Student not found with provided credentials', 404);
    }

    // Trouver la demande liée - le numéro de référence est maintenant obligatoire
    $idDemande = null;
    $relatedRequestNumber = trim($input['relatedRequestNumber']);
    
    $stmt = $pdo->prepare('SELECT id_demande FROM demandes WHERE numero_reference = :ref AND id_etudiant = :studentId');
    $stmt->execute([
        ':ref' => $relatedRequestNumber,
        ':studentId' => $student['id_etudiant'],
    ]);
    $demande = $stmt->fetch();
    
    if (!$demande) {
        send_error('Le numéro de référence de document fourni n\'existe pas ou ne vous appartient pas', 404);
    }
    
    $idDemande = $demande['id_demande'];

    // Générer un ID unique et un numéro de référence (format VARCHAR(10) pour id_reclamation)
    $timestamp = substr((string)time(), -6);
    $random = rand(100, 999);
    $idReclamation = 'R' . $timestamp . substr((string)$random, -2);
    $idReclamation = substr($idReclamation, 0, 10);
    
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reclamations WHERE numero_reference LIKE :pattern");
    $stmt->execute([':pattern' => "REC-$year-%"]);
    $count = $stmt->fetch()['count'] ?? 0;
    $referenceNumber = 'REC-' . $year . '-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);

    // Vérifier et modifier le type de colonne si nécessaire (DATE -> DATETIME)
    // Cela doit être fait une seule fois, mais on vérifie à chaque fois pour la compatibilité
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM reclamations LIKE 'date_reclamation'");
        $columnInfo = $checkColumn->fetch(PDO::FETCH_ASSOC);
        if ($columnInfo) {
            $columnType = strtoupper(trim($columnInfo['Type']));
            // Vérifier si c'est de type DATE (pas DATETIME ni TIMESTAMP)
            if (strpos($columnType, 'DATE') === 0 && strpos($columnType, 'DATETIME') === false && strpos($columnType, 'TIMESTAMP') === false) {
                // Modifier la colonne pour accepter DATETIME avec l'heure
                $pdo->exec("ALTER TABLE reclamations MODIFY COLUMN date_reclamation DATETIME NOT NULL");
                error_log('Colonne date_reclamation modifiée de DATE à DATETIME avec succès');
            }
        }
    } catch (\PDOException $e) {
        // Ignorer l'erreur si la colonne n'existe pas ou autre problème
        error_log('Note: Impossible de vérifier/modifier le type de colonne date_reclamation: ' . $e->getMessage());
    }

    // Insérer la réclamation
    try {
        $stmt = $pdo->prepare('
            INSERT INTO reclamations (
                id_reclamation, numero_reference, date_reclamation, description, objet, id_etudiant, id_demande
            ) VALUES (
                :id, :ref, NOW(), :description, :objet, :studentId, :demande
            )
        ');
        $stmt->execute([
            ':id' => $idReclamation,
            ':ref' => $referenceNumber,
            ':description' => $input['description'],
            ':objet' => $input['subject'],
            ':studentId' => $student['id_etudiant'],
            ':demande' => $idDemande,
        ]);
    } catch (\PDOException $e) {
        error_log('Erreur lors de l\'insertion de la réclamation: ' . $e->getMessage());
        // Si l'erreur est due à un ID ou référence déjà existant, générer un nouveau
        if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
            $timestamp = substr((string)time(), -6);
            $random = rand(100, 999);
            $idReclamation = 'R' . $timestamp . substr((string)$random, -2);
            $idReclamation = substr($idReclamation, 0, 10);
            
            $year = date('Y');
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reclamations WHERE numero_reference LIKE :pattern");
            $stmt->execute([':pattern' => "REC-$year-%"]);
            $count = $stmt->fetch()['count'] ?? 0;
            $referenceNumber = 'REC-' . $year . '-' . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
            
            // Réessayer l'insertion
            $stmt = $pdo->prepare('
                INSERT INTO reclamations (
                    id_reclamation, numero_reference, date_reclamation, description, objet, id_etudiant, id_demande
                ) VALUES (
                    :id, :ref, NOW(), :description, :objet, :studentId, :demande
                )
            ');
            $stmt->execute([
                ':id' => $idReclamation,
                ':ref' => $referenceNumber,
                ':description' => $input['description'],
                ':objet' => $input['subject'],
                ':studentId' => $student['id_etudiant'],
                ':demande' => $idDemande,
            ]);
        } else {
            throw $e;
        }
    }

    send_json([
        'ok' => true,
        'id' => $idReclamation,
        'referenceNumber' => $referenceNumber,
        'message' => 'Votre réclamation a été enregistrée avec succès.',
    ], 201);
}

function handle_respond_to_complaint(PDO $pdo, string $id): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error('Invalid JSON in request body', 400);
    }
    
    if (!isset($input['response']) || empty(trim($input['response']))) {
        send_error('Response is required', 400);
    }

    // Récupérer les informations de la réclamation et de l'étudiant
    $sql = <<<SQL
SELECT 
  r.id_reclamation,
  r.numero_reference,
  r.objet,
  r.description,
  e.email AS etu_email,
  e.nom AS etu_nom,
  e.prenom AS etu_prenom
FROM reclamations r
JOIN etudiants e ON e.id_etudiant = r.id_etudiant
WHERE r.id_reclamation = :id
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        send_error('Complaint not found', 404);
    }

    // Vérifier et modifier le type de colonne si nécessaire (DATE -> DATETIME)
    // Cela doit être fait une seule fois, mais on vérifie à chaque fois pour la compatibilité
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM reclamations LIKE 'date_reponse'");
        $columnInfo = $checkColumn->fetch(PDO::FETCH_ASSOC);
        if ($columnInfo) {
            $columnType = strtoupper(trim($columnInfo['Type']));
            // Vérifier si c'est de type DATE (pas DATETIME ni TIMESTAMP)
            if (strpos($columnType, 'DATE') === 0 && strpos($columnType, 'DATETIME') === false && strpos($columnType, 'TIMESTAMP') === false) {
                // Modifier la colonne pour accepter DATETIME avec l'heure
                $pdo->exec("ALTER TABLE reclamations MODIFY COLUMN date_reponse DATETIME DEFAULT NULL");
                error_log('Colonne date_reponse modifiée de DATE à DATETIME avec succès');
            }
        }
    } catch (\PDOException $e) {
        // Ignorer l'erreur si la colonne n'existe pas ou autre problème
        error_log('Note: Impossible de vérifier/modifier le type de colonne date_reponse: ' . $e->getMessage());
    }

    // Mettre à jour la réponse dans la base de données
    // Utiliser NOW() pour inclure la date et l'heure complètes
    $stmt = $pdo->prepare('
        UPDATE reclamations 
        SET reponse = :response, date_reponse = NOW(), id_administrateur = :admin
        WHERE id_reclamation = :id
    ');
    $stmt->execute([
        ':response' => $input['response'],
        ':id' => $id,
        ':admin' => $input['adminId'] ?? null,
    ]);

    // Essayer de mettre à jour le statut si la colonne existe
    try {
        $stmt = $pdo->prepare('
            UPDATE reclamations 
            SET statut = "resolu", id_administrateur = :admin
            WHERE id_reclamation = :id
        ');
        $stmt->execute([':id' => $id, ':admin' => $input['adminId'] ?? null]);
    } catch (\PDOException $e) {
        // La colonne statut n'existe peut-être pas, ce n'est pas grave
    }

    // Envoyer un email à l'étudiant avec la réponse
    $to = $complaint['etu_email'];
    $subject = "Réponse à votre réclamation - " . $complaint['numero_reference'];
    
    $message = "Bonjour " . $complaint['etu_prenom'] . " " . $complaint['etu_nom'] . ",\n\n";
    $message .= "Nous avons le plaisir de vous répondre concernant votre réclamation (Référence: " . $complaint['numero_reference'] . ").\n\n";
    $message .= "Objet de votre réclamation : " . $complaint['objet'] . "\n\n";
    $message .= "Notre réponse :\n";
    $message .= $input['response'] . "\n\n";
    $message .= "Si vous avez d'autres questions, n'hésitez pas à nous contacter.\n\n";
    $message .= "Cordialement,\nLe Service de la Scolarité";

    // Utiliser le service d'email
    $emailServiceFile = __DIR__ . '/EmailService.php';
    $emailSent = false;
    $emailMessage = '';
    
    if (file_exists($emailServiceFile)) {
        require_once $emailServiceFile;
        $result = send_email_to_student($to, $subject, $message, true);
        $emailSent = $result['sent'];
        $emailMessage = $result['message'];
    } else {
        // Fallback : simuler l'envoi
        error_log("=== EMAIL REPONSE RECLAMATION SIMULE ===");
        error_log("A: $to");
        error_log("Sujet: $subject");
        error_log("Message:\n$message");
        error_log("===================");
        $emailMessage = "Email simule (EmailService.php non trouve)";
    }

    send_json([
        'ok' => true,
        'emailSent' => $emailSent,
        'emailMessage' => $emailMessage,
    ]);
}

function handle_validate_student(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error('Invalid JSON in request body', 400);
    }
    
    if (!isset($input['email']) || !isset($input['apogee']) || !isset($input['cin'])) {
        send_error('Missing required fields: email, apogee, cin', 400);
    }

    $stmt = $pdo->prepare('
        SELECT id_etudiant, email, numero_apogee, cin, nom, prenom
        FROM etudiants 
        WHERE email = :email AND numero_apogee = :apogee AND cin = :cin
    ');
    $stmt->execute([
        ':email' => $input['email'],
        ':apogee' => $input['apogee'],
        ':cin' => $input['cin'],
    ]);
    $student = $stmt->fetch();

    if (!$student) {
        send_json(['valid' => false, 'student' => null]);
    }

    send_json([
        'valid' => true,
        'student' => [
            'id' => $student['id_etudiant'],
            'email' => $student['email'],
            'apogee' => $student['numero_apogee'],
            'cin' => $student['cin'],
            'firstName' => $student['prenom'],
            'lastName' => $student['nom'],
        ],
    ]);
}

function handle_login(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error('Invalid JSON in request body', 400);
    }
    
    $identifier = $input['identifier'] ?? $input['email'] ?? ''; // Support both 'identifier' and 'email' for backward compatibility
    $password = $input['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        send_error('Identifier and password are required', 400);
    }

    // Chercher par email OU login (les deux sont des clés uniques)
    $stmt = $pdo->prepare('SELECT id_administrateur, email, password, login FROM administrateurs WHERE email = :identifier OR login = :identifier');
    $stmt->execute([':identifier' => $identifier]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        send_error('Invalid credentials', 401);
    }

    send_json([
        'id' => $admin['id_administrateur'],
        'email' => $admin['email'],
        'name' => $admin['login'],
    ]);
}

function handle_get_academic_years(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT id_annee, annee_debut, annee_fin FROM annee_universitaire ORDER BY annee_debut DESC');
    $years = $stmt->fetchAll();
    
    $formatted = array_map(function ($year) {
        return $year['annee_debut'] . '-' . $year['annee_fin'];
    }, $years);
    
    // Si aucune année n'est trouvée, retourner des valeurs par défaut
    if (empty($formatted)) {
        $currentYear = (int)date('Y');
        $formatted = [
            $currentYear . '-' . ($currentYear + 1),
            ($currentYear - 1) . '-' . $currentYear,
            ($currentYear - 2) . '-' . ($currentYear - 1),
            ($currentYear - 3) . '-' . ($currentYear - 2),
        ];
    }
    
    send_json($formatted);
}

function handle_get_semesters(PDO $pdo): void
{
    // Les semestres sont stockés dans la table releves_notes
    $stmt = $pdo->query('SELECT DISTINCT semestre FROM releves_notes WHERE semestre IS NOT NULL ORDER BY semestre');
    $semesters = $stmt->fetchAll();
    
    $formatted = array_map(function ($sem) {
        return $sem['semestre'];
    }, $semesters);
    
    // Si aucun semestre n'est trouvé, retourner des valeurs par défaut
    if (empty($formatted)) {
        $formatted = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10'];
    }
    
    send_json($formatted);
}

function handle_get_supervisors(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT id_prof, nom, prenom FROM professeur WHERE est_encadrant = 1 ORDER BY nom, prenom');
    $supervisors = $stmt->fetchAll();
    
    $formatted = array_map(function ($sup) {
        return 'Dr. ' . $sup['prenom'] . ' ' . $sup['nom'];
    }, $supervisors);
    
    // Si aucun encadrant n'est trouvé, retourner des valeurs par défaut
    if (empty($formatted)) {
        $formatted = [
            'Dr. Hassan Moussaoui',
            'Dr. Leila Benkirane',
            'Dr. Mohammed Tazi',
            'Dr. Amina El Ouafi',
            'Dr. Rachid Bennani',
        ];
    }
    
    send_json($formatted);
}

function handle_get_student_demands(PDO $pdo): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error('Invalid JSON in request body', 400);
    }
    
    if (!isset($input['email']) || !isset($input['apogee']) || !isset($input['cin'])) {
        send_error('Missing required fields: email, apogee, cin', 400);
    }

    // Vérifier que l'étudiant existe
    $stmt = $pdo->prepare('
        SELECT id_etudiant FROM etudiants 
        WHERE email = :email AND numero_apogee = :apogee AND cin = :cin
    ');
    $stmt->execute([
        ':email' => $input['email'],
        ':apogee' => $input['apogee'],
        ':cin' => $input['cin'],
    ]);
    $student = $stmt->fetch();
    
    if (!$student) {
        send_error('Student not found with provided credentials', 404);
    }

    // Récupérer toutes les demandes de l'étudiant (y compris refusées pour permettre les réclamations)
    // Les étudiants peuvent réclamer sur n'importe quelle demande, même refusée
    $sql = <<<SQL
SELECT 
  d.id_demande,
  d.numero_reference,
  d.type_document,
  d.statut,
  d.date_demande,
  ar.annee_universitaire AS ar_annee,
  rn.annee_universitaire AS rn_annee,
  rn.semestre AS rn_semestre,
  cs.nom_entreprise,
  cs.sujet_stage,
  cs.date_debut_stage,
  cs.date_fin_stage
FROM demandes d
LEFT JOIN attestations_reussite ar ON ar.id_demande = d.id_demande
LEFT JOIN releves_notes rn ON rn.id_demande = d.id_demande
LEFT JOIN conventions_stage cs ON cs.id_demande = d.id_demande
WHERE d.id_etudiant = :studentId
ORDER BY d.date_demande DESC
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':studentId' => $student['id_etudiant']]);
    $rows = $stmt->fetchAll();

    $payload = array_map(function ($row) {
        $documentType = map_document_type($row['type_document']);
        $status = map_status($row['statut']);
        
        // Construire un label descriptif pour le dropdown
        $label = $row['numero_reference'] . ' - ';
        
        switch ($documentType) {
            case 'attestation_scolarite':
                $label .= 'Attestation de scolarité';
                break;
            case 'attestation_reussite':
                $label .= 'Attestation de réussite';
                if ($row['ar_annee']) {
                    $label .= ' (' . $row['ar_annee'] . ')';
                }
                break;
            case 'releve_notes':
                $label .= 'Relevé de notes';
                if ($row['rn_annee']) {
                    $label .= ' (' . $row['rn_annee'];
                    if ($row['rn_semestre']) {
                        $label .= ' - ' . $row['rn_semestre'];
                    }
                    $label .= ')';
                }
                break;
            case 'convention_stage':
                $label .= 'Convention de stage';
                if ($row['nom_entreprise']) {
                    $label .= ' (' . $row['nom_entreprise'] . ')';
                }
                break;
            default:
                $label .= ucfirst(str_replace('_', ' ', $documentType));
        }
        
        // Afficher le statut correctement (en attente, traité, refusé)
        $statusLabel = 'En attente';
        if ($status === 'accepted' || $status === 'processed') {
            $statusLabel = 'Traitée';
        } elseif ($status === 'rejected' || $status === 'refused') {
            $statusLabel = 'Refusée';
        }
        $label .= ' - ' . $statusLabel;
        
        return [
            'id' => $row['id_demande'],
            'referenceNumber' => $row['numero_reference'],
            'documentType' => $documentType,
            'status' => $status,
            'date' => $row['date_demande'],
            'label' => $label,
        ];
    }, $rows);

    send_json($payload);
}

function handle_get_complaint_details(PDO $pdo, string $id): void
{
    try {
        // Vérifier si les colonnes existent (pour compatibilité)
        try {
            $testCols = $pdo->query("SELECT statut, reponse, date_reponse FROM reclamations LIMIT 1");
            $hasStatusColumns = true;
        } catch (\PDOException $e) {
            $hasStatusColumns = false;
        }

        // Récupérer la réclamation avec toutes les informations liées
        $sql = <<<SQL
SELECT 
  r.id_reclamation,
  r.numero_reference,
  r.date_reclamation,
  r.description,
  r.objet,
  r.id_demande,
  r.id_etudiant,
  r.id_administrateur,
  e.email AS etu_email,
  e.numero_apogee AS etu_apogee,
  e.cin AS etu_cin,
  e.nom AS etu_nom,
  e.prenom AS etu_prenom,
  e.date_naissance AS etu_date_naissance,
  e.lieu_naissance AS etu_lieu_naissance,
  (SELECT CASE 
    WHEN f_latest.nom_filiere = '2AP1' THEN '1er annee'
    WHEN f_latest.nom_filiere = '2AP2' THEN '2éme annee'
    WHEN f_latest.nom_filiere = 'Génie Informatique 1' THEN '3eme annee'
    WHEN f_latest.nom_filiere = 'Génie Informatique 2' THEN '4eme annee'
    WHEN f_latest.nom_filiere = 'Génie Informatique 3' THEN '5eme annee'
    ELSE NULL
  END
  FROM inscription_etudiant ie_latest
  JOIN filiere f_latest ON f_latest.id_filiere = ie_latest.id_filiere
  JOIN annee_universitaire au_latest ON au_latest.id_annee = ie_latest.id_annee
  WHERE ie_latest.id_etudiant = e.id_etudiant
  ORDER BY au_latest.annee_debut DESC
  LIMIT 1) AS etu_niveau,
  d.numero_reference AS demande_ref,
  d.type_document AS demande_type,
  d.statut AS demande_statut,
  d.date_demande AS demande_date,
  ar.annee_universitaire AS ar_annee,
  rn.annee_universitaire AS rn_annee,
  rn.semestre AS rn_semestre,
  cs.id_convention,
  cs.nom_entreprise,
  cs.adresse_entreprise,
  cs.sujet_stage,
  cs.date_debut_stage,
  cs.date_fin_stage,
  cs.email_responsable_entreprise,
  cs.nom_responsable_entreprise,
  cs.telephone_responsable_entreprise,
  cs.id_prof_encadrant,
  p.nom AS prof_nom,
  p.prenom AS prof_prenom,
  p.email AS prof_email,
  p.telephone AS prof_telephone
SQL;

        if ($hasStatusColumns) {
            $sql .= ", r.statut, r.reponse, r.date_reponse";
        } else {
            $sql .= ", NULL AS statut, NULL AS reponse, NULL AS date_reponse";
        }

        $sql .= <<<SQL

FROM reclamations r
JOIN etudiants e ON e.id_etudiant = r.id_etudiant
LEFT JOIN demandes d ON d.id_demande = r.id_demande
LEFT JOIN attestations_reussite ar ON ar.id_demande = d.id_demande
LEFT JOIN releves_notes rn ON rn.id_demande = d.id_demande
LEFT JOIN conventions_stage cs ON cs.id_demande = d.id_demande
LEFT JOIN professeur p ON p.id_prof = cs.id_prof_encadrant
WHERE r.id_reclamation = :id
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            send_error('Complaint not found', 404);
        }

        // Déterminer le statut
        $status = 'pending';
        if ($hasStatusColumns && isset($row['statut'])) {
            $status = map_status($row['statut']);
        } elseif (!empty($row['reponse'])) {
            $status = 'resolved';
        }

        // Construire les détails du document lié
        $documentDetails = null;
        if ($row['demande_type']) {
            $documentType = map_document_type($row['demande_type']);
            $documentDetails = [
                'referenceNumber' => $row['demande_ref'],
                'documentType' => $documentType,
                'status' => map_status($row['demande_statut'] ?? 'en attente'),
                'requestDate' => $row['demande_date'],
            ];

            // Ajouter les détails spécifiques selon le type de document
            switch ($documentType) {
                case 'attestation_reussite':
                    if ($row['ar_annee']) {
                        $documentDetails['academicYear'] = $row['ar_annee'];
                    }
                    break;
                case 'releve_notes':
                    if ($row['rn_annee']) {
                        $documentDetails['academicYear'] = $row['rn_annee'];
                    }
                    if ($row['rn_semestre']) {
                        $documentDetails['semester'] = $row['rn_semestre'];
                    }
                    break;
                case 'convention_stage':
                    $documentDetails['companyName'] = $row['nom_entreprise'] ?? null;
                    $documentDetails['companyAddress'] = $row['adresse_entreprise'] ?? null;
                    $documentDetails['stageSubject'] = $row['sujet_stage'] ?? null;
                    $documentDetails['startDate'] = $row['date_debut_stage'] ?? null;
                    $documentDetails['endDate'] = $row['date_fin_stage'] ?? null;
                    $documentDetails['supervisorName'] = $row['nom_responsable_entreprise'] ?? null;
                    $documentDetails['supervisorEmail'] = $row['email_responsable_entreprise'] ?? null;
                    $documentDetails['supervisorPhone'] = $row['telephone_responsable_entreprise'] ?? null;
                    if ($row['prof_nom']) {
                        $documentDetails['academicSupervisor'] = [
                            'id' => $row['id_prof_encadrant'],
                            'name' => $row['prof_prenom'] . ' ' . $row['prof_nom'],
                            'email' => $row['prof_email'],
                            'phone' => $row['prof_telephone'],
                        ];
                    }
                    break;
            }
        }

        // Format dates properly - ensure ISO 8601 format with time
        $createdAt = $row['date_reclamation'];
        // Si date_reclamation est une chaîne et ne contient pas d'heure (format DATE: 'YYYY-MM-DD')
        // alors ajouter minuit. Sinon, garder l'heure telle quelle (format DATETIME: 'YYYY-MM-DD HH:MM:SS')
        if ($createdAt) {
            $dateStr = is_string($createdAt) ? $createdAt : (string)$createdAt;
            // Vérifier si c'est un format DATE seulement (10 caractères: YYYY-MM-DD)
            // ou s'il contient déjà l'heure (19 caractères: YYYY-MM-DD HH:MM:SS ou plus)
            if (strlen(trim($dateStr)) === 10 && !str_contains($dateStr, ' ') && !str_contains($dateStr, 'T')) {
                // Format DATE uniquement, ajouter l'heure minuit pour les anciennes données
                $createdAt = $dateStr . ' 00:00:00';
            }
            // Sinon, garder tel quel (déjà au format DATETIME avec l'heure)
        }
        $respondedAt = $row['date_reponse'] ?? null;
        // Si date_reponse est une chaîne et ne contient pas d'heure (format DATE: 'YYYY-MM-DD')
        // alors ajouter minuit. Sinon, garder l'heure telle quelle (format DATETIME: 'YYYY-MM-DD HH:MM:SS')
        if ($respondedAt) {
            // Vérifier si c'est un format DATE seulement (10 caractères: YYYY-MM-DD)
            // ou s'il contient déjà l'heure (19 caractères: YYYY-MM-DD HH:MM:SS ou plus)
            $dateStr = is_string($respondedAt) ? $respondedAt : (string)$respondedAt;
            if (strlen(trim($dateStr)) === 10 && !str_contains($dateStr, ' ') && !str_contains($dateStr, 'T')) {
                // Format DATE uniquement, ajouter l'heure minuit
                $respondedAt = $dateStr . ' 00:00:00';
            }
            // Sinon, garder tel quel (déjà au format DATETIME avec l'heure)
        }
        
        $payload = [
            'id' => $row['id_reclamation'],
            'referenceNumber' => $row['numero_reference'],
            'subject' => $row['objet'],
            'description' => $row['description'],
            'status' => $status,
            'createdAt' => $createdAt,
            'response' => $row['reponse'] ?? null,
            'respondedAt' => $respondedAt,
            'student' => [
                'id' => $row['id_etudiant'],
                'email' => $row['etu_email'],
                'apogee' => $row['etu_apogee'],
                'cin' => $row['etu_cin'],
                'firstName' => $row['etu_prenom'],
                'lastName' => $row['etu_nom'],
                'dateOfBirth' => $row['etu_date_naissance'],
                'placeOfBirth' => $row['etu_lieu_naissance'],
                'level' => $row['etu_niveau'],
            ],
            'relatedRequest' => $documentDetails,
        ];

        send_json($payload);
    } catch (\PDOException $e) {
        error_log('Erreur lors de la récupération des détails de la réclamation: ' . $e->getMessage());
        send_error('Erreur lors de la récupération des détails de la réclamation', 500);
    }
}
