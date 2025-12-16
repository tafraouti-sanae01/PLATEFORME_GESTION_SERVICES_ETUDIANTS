<?php
/**
 * Configuration Email - Exemple
 * 
 * Copiez ce fichier vers email_config.php et remplissez vos identifiants SMTP
 */

return [
    // Configuration SMTP
    'smtp_host' => 'smtp.gmail.com',        // Serveur SMTP
    'smtp_port' => 465,                      // Port SMTP (587 pour TLS, 465 pour SSL)
    'smtp_user' => 'votre-email@gmail.com', // Votre email
    'smtp_pass' => 'votre-mot-de-passe-app', // Mot de passe ou mot de passe d'application
    'smtp_from' => 'scolarite@univ.ma',     // Email expéditeur
    'smtp_from_name' => 'Service Scolarité', // Nom de l'expéditeur
    
    // Pour Gmail, vous devez utiliser un "Mot de passe d'application"
    // Voir: https://support.google.com/accounts/answer/185833
    // Note: Port 465 utilise SSL, Port 587 utilise TLS
    
    // Mode développement (simule l'envoi au lieu d'envoyer vraiment)
    'dev_mode' => false, // Mettez à false en production pour envoyer de vrais emails
];

