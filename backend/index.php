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
        return [
            'id' => $row['id_demande'],
            'referenceNumber' => $row['numero_reference'],
            'studentId' => $row['id_etudiant'],
            'documentType' => $documentType,
            'status' => map_status($row['statut']),
            'createdAt' => $row['date_demande'],
            'processedAt' => $processedAt,
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
    
    if ($input['documentType'] === 'attestation_reussite' && isset($input['academicYear'])) {
        // Générer un ID unique pour l'attestation (format VARCHAR(10))
        $timestamp = substr((string)time(), -6);
        $random = rand(100, 999);
        $idAttestation = 'AR' . $timestamp . substr((string)$random, -1);
        $idAttestation = substr($idAttestation, 0, 10);
        
        // S'assurer que l'année universitaire est au bon format (YYYY-YYYY)
        $academicYear = $input['academicYear'];
        // Si le format est déjà YYYY-YYYY, l'utiliser tel quel
        // Sinon, essayer de le convertir
        if (!preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
            // Si c'est juste YYYY, créer YYYY-YYYY+1
            if (preg_match('/^\d{4}$/', $academicYear)) {
                $academicYear = $academicYear . '-' . ((int)$academicYear + 1);
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
        if (isset($input['academicYear']) && !empty($input['academicYear'])) {
            $academicYear = $input['academicYear'];
            // S'assurer que le format est YYYY-YYYY
            if (!preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
                if (preg_match('/^\d{4}$/', $academicYear)) {
                    $academicYear = $academicYear . '-' . ((int)$academicYear + 1);
                }
            }
        }
        
        try {
            $stmt = $pdo->prepare('
                INSERT INTO releves_notes (id_releve, annee_universitaire, semestre, id_demande)
                VALUES (:id, :annee, :semestre, :demande)
            ');
            $stmt->execute([
                ':id' => $idReleve,
                ':annee' => $academicYear,
                ':semestre' => $input['semester'] ?? null,
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
            $message = "Bonjour " . $student['prenom'] . " " . $student['nom'] . ",\n\n";
            $message .= "Nous avons bien reçu votre demande de " . strtolower($docLabel) . ".\n\n";
            $message .= "Détails de votre demande :\n";
            $message .= "----------------------------------------\n";
            $message .= "Numéro de référence : " . $referenceNumber . "\n";
            $message .= "Type de document : " . $docLabel . "\n";
            $message .= "Date de la demande : " . date('d/m/Y') . "\n";
            
            // Ajouter les détails spécifiques selon le type de document
            if ($input['documentType'] === 'attestation_reussite' && isset($input['academicYear'])) {
                $message .= "Année universitaire : " . $input['academicYear'] . "\n";
            }
            if ($input['documentType'] === 'releve_notes') {
                if (isset($input['academicYear'])) {
                    $message .= "Année universitaire : " . $input['academicYear'] . "\n";
                }
                if (isset($input['semester'])) {
                    $message .= "Semestre : " . $input['semester'] . "\n";
                }
            }
            if ($input['documentType'] === 'convention_stage') {
                if (isset($input['companyName'])) {
                    $message .= "Entreprise : " . $input['companyName'] . "\n";
                }
                if (isset($input['stageSubject'])) {
                    $message .= "Sujet du stage : " . $input['stageSubject'] . "\n";
                }
                if (isset($input['stageStartDate']) && isset($input['stageEndDate'])) {
                    $message .= "Période : " . date('d/m/Y', strtotime($input['stageStartDate'])) . " - " . date('d/m/Y', strtotime($input['stageEndDate'])) . "\n";
                }
            }
            
            $message .= "----------------------------------------\n\n";
            $message .= "Votre demande est en cours de traitement. Nous vous tiendrons informé dès que votre document sera prêt.\n\n";
            $message .= "Vous pouvez utiliser le numéro de référence ci-dessus pour suivre l'état de votre demande.\n\n";
            $message .= "Cordialement,\nLe Service de la Scolarité";
            
            // Charger le service d'email et envoyer
            $emailServiceFile = __DIR__ . '/EmailService.php';
            if (file_exists($emailServiceFile)) {
                require_once $emailServiceFile;
                send_email_to_student($student['email'], $subject, $message, false);
            } else {
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
                
                // Ajouter les raisons du refus si fournies
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
        // We assume year comes from 'session' column in inscrit_module
        $sql = <<<SQL
SELECT DISTINCT 
    im.session as annee_universitaire,
    mf.semestre
FROM inscrit_module im
JOIN module_filiere mf ON mf.id_module = im.id_module
WHERE im.id_etudiant = :studentId
ORDER BY im.session DESC, mf.semestre ASC
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
            $message .= "Votre document est disponible en téléchargement depuis votre espace.\n\n";
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
  e.niveau_scolaire AS etu_niveau,
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
  p.prenom AS prof_prenom,
  f.nom_filiere AS filiere_nom,
  f.id_filiere AS filiere_id,
  au.annee_debut AS annee_debut,
  au.annee_fin AS annee_fin,
  ie.moyenne AS moyenne,
  ie.mention AS mention,
  ie.est_admis AS est_admis
FROM demandes d
JOIN etudiants e ON e.id_etudiant = d.id_etudiant
LEFT JOIN attestations_reussite ar ON ar.id_demande = d.id_demande
LEFT JOIN releves_notes rn ON rn.id_demande = d.id_demande
LEFT JOIN conventions_stage cs ON cs.id_demande = d.id_demande
LEFT JOIN professeur p ON p.id_prof = cs.id_prof_encadrant
LEFT JOIN inscription_etudiant ie ON ie.id_etudiant = e.id_etudiant
LEFT JOIN annee_universitaire au ON au.id_annee = ie.id_annee
LEFT JOIN filiere f ON f.id_filiere = ie.id_filiere
WHERE d.id_demande = :id
ORDER BY COALESCE(au.annee_debut, 0) DESC
LIMIT 1
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch();

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
 * Génère le PDF en mémoire (pour pièce jointe email)
 * Retourne un tableau avec 'content' (contenu binaire) et 'filename' (nom du fichier)
 */
function generate_pdf_attachment(PDO $pdo, string $requestId): ?array
{
    // Récupérer toutes les informations de la demande
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
  e.niveau_scolaire AS etu_niveau,
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
  p.prenom AS prof_prenom,
  f.nom_filiere AS filiere_nom,
  f.id_filiere AS filiere_id,
  au.annee_debut AS annee_debut,
  au.annee_fin AS annee_fin,
  ie.moyenne AS moyenne,
  ie.mention AS mention,
  ie.est_admis AS est_admis
FROM demandes d
JOIN etudiants e ON e.id_etudiant = d.id_etudiant
LEFT JOIN attestations_reussite ar ON ar.id_demande = d.id_demande
LEFT JOIN releves_notes rn ON rn.id_demande = d.id_demande
LEFT JOIN conventions_stage cs ON cs.id_demande = d.id_demande
LEFT JOIN professeur p ON p.id_prof = cs.id_prof_encadrant
LEFT JOIN inscription_etudiant ie ON ie.id_etudiant = e.id_etudiant
LEFT JOIN annee_universitaire au ON au.id_annee = ie.id_annee
LEFT JOIN filiere f ON f.id_filiere = ie.id_filiere
WHERE d.id_demande = :id
ORDER BY COALESCE(au.annee_debut, 0) DESC
LIMIT 1
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $requestId]);
    $request = $stmt->fetch();

    if (!$request || $request['statut'] !== 'traite') {
        return null;
    }

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
    
    // Filière
    $filiere = !empty($request['filiere_nom']) ? htmlspecialchars($request['filiere_nom']) : 'Non spécifiée';
    
    // Déterminer le diplôme et la description de la filière
    $diplome = 'Ingénieur d\'État';
    
    // Niveau formaté
    $niveau = htmlspecialchars($request['etu_niveau'] ?? '');
    if (strpos($niveau, '1er') !== false || strpos($niveau, '1') !== false) {
        $anneeTexte = '1ère Année';
    } elseif (strpos($niveau, '2') !== false) {
        $anneeTexte = '2ème Année';
    } elseif (strpos($niveau, '3') !== false) {
        $anneeTexte = '3ème Année';
    } else {
        $anneeTexte = $niveau;
    }
    
    // Mapper les filières aux descriptions complètes
    if ($filiere === '2AP1' || $filiere === '2AP2') {
        $filiereDescription = $anneeTexte . ' Classe preparatoire';
    } else {
        $filiereDescription = $anneeTexte . ' ' . $filiere;
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
    
    // Année universitaire / Session
    $anneeUniv = htmlspecialchars($request['ar_annee'] ?? '');
    if (empty($anneeUniv) && !empty($request['annee_debut'])) {
        $anneeUniv = $request['annee_debut'] . '/' . $request['annee_fin'];
    }
    
    // Session (par défaut "Ordinaire" ou "Principale" si non spécifiée, ou basée sur l'année)
    // Le template montre "Printemps 2020/2021", on va mettre l'année universitaire comme session par défaut
    $session = $anneeUniv;

    // Informations Étudiant
    $nomComplet = strtoupper(htmlspecialchars($request['etu_nom'] . ' ' . $request['etu_prenom']));
    $cne = htmlspecialchars($request['etu_cin']); // Utilisation du CIN comme CNE si pas de champ CNE distinct
    $filiere = htmlspecialchars($request['filiere_nom'] ?? '');
    
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
                    <td style="font-weight: bold;">Session</td>
                     <td> ' . $session . '</td>
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
    
    // Niveau/Filière
    $niveau = htmlspecialchars($request['etu_niveau'] ?? '');
    $filiere = !empty($request['filiere_nom']) ? htmlspecialchars($request['filiere_nom']) : '';
    
    if (strpos($niveau, '1er') !== false || strpos($niveau, '1') !== false) {
        $niveauTexte = '1ère année';
    } elseif (strpos($niveau, '2') !== false) {
        $niveauTexte = '2ème année';
    } elseif (strpos($niveau, '3') !== false) {
        $niveauTexte = '3ème année';
    } else {
        $niveauTexte = $niveau;
    }
    
    if ($filiere === '2AP1' || $filiere === '2AP2') {
        $inscritEn = $niveauTexte . ' Préparatoire';
    } else {
        $inscritEn = $niveauTexte . ($filiere ? ' ' . $filiere : '');
    }
    
    // Session
    $session = htmlspecialchars($request['rn_semestre'] ?? 'Session 1');
    
    // Récupérer les notes des modules si PDO est fourni
    $modules = [];
    $totalNotes = 0;
    $nombreModules = 0;
    
    if ($pdo && !empty($request['id_etudiant'])) {
        try {
            $sqlModules = "
                SELECT 
                    m.nom_module,
                    im.note,
                    im.est_valide,
                    im.session
                FROM inscrit_module im
                JOIN module m ON m.id_module = im.id_module
                WHERE im.id_etudiant = :id_etudiant
                ORDER BY m.id_module
            ";
            $stmtModules = $pdo->prepare($sqlModules);
            $stmtModules->execute([':id_etudiant' => $request['id_etudiant']]);
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
    }
    
    $mention = htmlspecialchars($request['mention'] ?? 'Passable');
    $estAdmis = !empty($request['est_admis']) && $request['est_admis'] == 1;
    $resultatAdmission = $estAdmis ? 'Admis' : 'Non admis';
    
    // Date de génération
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
             'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $dateGeneration = date('d') . ' ' . $mois[(int)date('n')] . ' ' . date('Y');
    
    // Construction du HTML
    $html = '
    <!-- Header -->
    <div class="header" style="text-align: center; margin-bottom: 20px;">
        <img src="' . htmlspecialchars($logoUrl) . '" alt="Logo" style="max-height: 70px; margin-bottom: 10px;" />
        <div style="font-weight: bold; font-size: 11pt; margin-bottom: 3px;">Université Abdelmalek Essaâdi</div>
        <div style="font-weight: bold; font-size: 10pt;">ENSA Tétouan - École Nationale des Sciences Appliquées</div>
    </div>

    <!-- Titre -->
    <div style="text-align: center; margin-bottom: 25px;">
        <h1 style="font-size: 18pt; text-decoration: underline; font-family: serif; letter-spacing: 1px; margin: 0;">
        RELEVÉ DE NOTES ET RÉSULTATS
        </h1>
    </div>
    
    <!-- Session -->
    <div style="text-align: center; border: 1px solid #000; padding: 5px; margin: 10px 0; font-size: 11pt; font-weight: bold;">
        ' . $session . '
    </div>
    
    <!-- Informations étudiant -->
    <div style="margin: 15px 0; font-size: 10pt; line-height: 1.6;">
        <div style="margin-bottom: 5px;"><strong>' . $nomComplet . '</strong></div>
        <div style="margin-bottom: 5px;">
            N° Étudiant : <strong>' . $numeroEtudiant . '</strong>
            &nbsp;&nbsp;&nbsp;&nbsp;
            CIN : <strong>' . $cne . '</strong>
        </div>';
    
    if ($dateNaissance && $lieuNaissance) {
        $html .= '
        <div style="margin-bottom: 5px;">
            Né(e) le : <strong>' . $dateNaissance . '</strong> à <strong>' . $lieuNaissance . '</strong>
        </div>';
    }
    
    $html .= '
        <div style="margin-bottom: 5px;">
            inscrit(e) en <strong>' . $inscritEn . '</strong>
        </div>
        <div style="margin-top: 10px;">a obtenu les notes suivantes :</div>
    </div>
    
    <!-- Tableau des notes avec résultat final -->
    <table border="1" style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 10pt;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th style="padding: 8px; text-align: left; width: 40%;"></th>
                <th style="padding: 8px; text-align: center; width: 20%;">Note/Barème</th>
                <th style="padding: 8px; text-align: center; width: 20%;">Résultat</th>
                <th style="padding: 8px; text-align: center; width: 20%;">Session</th>
            </tr>
        </thead>
        <tbody>';
    
    // Afficher les modules
    if (!empty($modules)) {
        foreach ($modules as $module) {
            $nomModule = htmlspecialchars($module['nom_module']);
            $noteModule = $module['note'] !== null ? number_format((float)$module['note'], 2) . '/20' : '-';
            $resultat = $module['est_valide'] == 1 ? 'Validé' : 'Val après Rat';
            $sessionModule = htmlspecialchars($module['session'] ?? $session);
            
            $html .= '
            <tr>
                <td style="padding: 6px;">' . $nomModule . '</td>
                <td style="padding: 6px; text-align: center;">' . $noteModule . '</td>
                <td style="padding: 6px; text-align: center;">' . $resultat . '</td>
                <td style="padding: 6px; text-align: center;">' . $sessionModule . '</td>
            </tr>';
        }
    } else {
        $html .= '
            <tr>
                <td colspan="4" style="padding: 20px; text-align: center; font-style: italic;">
                    Aucune note disponible pour cet étudiant.
                </td>
            </tr>';
    }
    
    // Ligne du résultat final dans le même tableau
    $html .= '
            <tr style="background-color: #f0f0f0; font-weight: bold;">
                <td style="padding: 8px;">Résultat d\'admission ' . strtolower($session) . ' :</td>
                <td style="padding: 8px; text-align: center;"><strong>' . $moyenne . '/20</strong></td>
                <td style="padding: 8px; text-align: center;"><strong>' . $resultatAdmission . '</strong></td>
                <td style="padding: 8px; text-align: center;"><strong>' . $mention . '</strong></td>
            </tr>
        </tbody>
    </table>
    
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
        
        return [
            'id' => $row['id_reclamation'],
            'referenceNumber' => $row['numero_reference'],
            'subject' => $row['objet'],
            'description' => $row['description'],
            'status' => $status,
            'createdAt' => $row['date_reclamation'],
            'response' => $row['reponse'] ?? null,
            'respondedAt' => $row['date_reponse'] ?? null,
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

    // Insérer la réclamation
    try {
        $stmt = $pdo->prepare('
            INSERT INTO reclamations (
                id_reclamation, numero_reference, date_reclamation, description, objet, id_etudiant, id_demande
            ) VALUES (
                :id, :ref, CURDATE(), :description, :objet, :studentId, :demande
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
                    :id, :ref, CURDATE(), :description, :objet, :studentId, :demande
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

    // Mettre à jour la réponse dans la base de données
    $stmt = $pdo->prepare('
        UPDATE reclamations 
        SET reponse = :response, date_reponse = CURDATE(), id_administrateur = :admin
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
        $formatted = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6'];
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

    // Récupérer les demandes de l'étudiant (en attente ou traitées, pas refusées)
    // On inclut toutes les demandes sauf celles refusées pour permettre les réclamations
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
  AND d.statut != 'refuse'
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
  e.niveau_scolaire AS etu_niveau,
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

        $payload = [
            'id' => $row['id_reclamation'],
            'referenceNumber' => $row['numero_reference'],
            'subject' => $row['objet'],
            'description' => $row['description'],
            'status' => $status,
            'createdAt' => $row['date_reclamation'],
            'response' => $row['reponse'] ?? null,
            'respondedAt' => $row['date_reponse'] ?? null,
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
