<?php
// Database connection settings. Update with your own credentials.
// For local dev with XAMPP/WAMP/MAMP:
//   host: 127.0.0.1
//   user: root
//   password: "" (empty by default on Windows XAMPP)
//   dbname: ecole_db
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'dbname' => getenv('DB_NAME') ?: 'ecole_db',
    'user' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];

