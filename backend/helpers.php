<?php

function send_json($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function send_error(string $message, int $status = 400): void
{
    send_json(['error' => $message], $status);
}

function map_document_type(?string $dbType): ?string
{
    $map = [
        'attestations_scolarite' => 'attestation_scolarite',
        'attestations_reussite' => 'attestation_reussite',
        'releves_notes' => 'releve_notes',
        'conventions_stage' => 'convention_stage',
    ];

    return $dbType ? ($map[$dbType] ?? null) : null;
}

function map_status(?string $dbStatus): string
{
    if (!$dbStatus) {
        return 'pending';
    }

    // Normaliser pour éviter les variantes d'accent/majuscules
    $normalized = function_exists('mb_strtolower')
        ? mb_strtolower(trim($dbStatus), 'UTF-8')
        : strtolower(trim($dbStatus));

    // Map des statuts de la base de données vers le frontend
    $statusMap = [
        'en attente' => 'pending',
        'attente' => 'pending',
        'traite' => 'accepted',
        'traité' => 'accepted',
        'refuse' => 'rejected',
        'refusé' => 'rejected',
        'refusée' => 'rejected',
        'rejetee' => 'rejected',
        'rejeté' => 'rejected',
        'rejetée' => 'rejected',
        'resolu' => 'resolved',
        'résolu' => 'resolved',
    ];

    return $statusMap[$normalized] ?? 'pending';
}

function map_status_to_db(string $frontendStatus): string
{
    // Map des statuts du frontend vers la base de données
    $statusMap = [
        'pending' => 'en attente',
        'accepted' => 'traite',
        'rejected' => 'refuse',
        'resolved' => 'resolu',
    ];

    return $statusMap[$frontendStatus] ?? 'en attente';
}

