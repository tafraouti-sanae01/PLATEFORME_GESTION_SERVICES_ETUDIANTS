# Service Scolarité - Plateforme de Gestion des Documents Scolaires

## Résumé Exécutif

Le **Service Scolarité** est une application web complète conçue pour la gestion numérique des demandes de documents académiques et des réclamations étudiantes. Cette plateforme offre une interface moderne pour les étudiants et un tableau de bord administratif complet pour la gestion des processus académiques.

---

## Table des Matières

1. [Introduction](#introduction)
2. [Architecture Technique](#architecture-technique)
3. [Prérequis](#prérequis)
4. [Installation et Configuration](#installation-et-configuration)
5. [Structure du Projet](#structure-du-projet)
6. [Fonctionnalités](#fonctionnalités)
7. [Documentation de l'API](#documentation-de-lapi)
8. [Guide d'Utilisation](#guide-dutilisation)
9. [Base de Données](#base-de-données)
10. [Technologies Utilisées](#technologies-utilisées)
11. [Développement](#développement)

---

## Introduction

### Contexte

Le Service Scolarité répond au besoin croissant de digitalisation des services administratifs universitaires. Cette solution permet aux étudiants de soumettre leurs demandes de documents académiques de manière sécurisée et transparente, tout en offrant aux administrateurs un outil efficace pour gérer ces demandes et suivre leur traitement.

### Objectifs

- **Dématérialisation** : Transformation complète du processus de demande de documents en processus numérique
- **Traçabilité** : Suivi en temps réel du statut des demandes avec numéros de référence uniques
- **Efficacité** : Automatisation de la génération de documents PDF et de l'envoi de notifications
- **Transparence** : Interface claire permettant aux étudiants de suivre leurs demandes et réclamations
- **Gestion centralisée** : Tableau de bord administratif unifié pour le traitement des demandes

### Portée du Projet

Le système couvre les processus suivants :
- Gestion des demandes de documents (4 types principaux)
- Traitement des réclamations étudiantes
- Génération automatique de documents PDF
- Système de notification par email
- Suivi et historique des demandes

---

## Architecture Technique

### Vue d'Ensemble

Le projet suit une architecture **client-serveur** avec séparation claire entre le frontend et le backend :

```
┌─────────────────┐         ┌─────────────────┐         ┌─────────────────┐
│   Frontend      │         │    Backend      │         │   Base de       │
│   (React)       │◄───────►│    (PHP API)    │◄───────►│   Données       │
│                 │  HTTP   │                 │   PDO   │   (MySQL)       │
│  Port: 5173     │         │  Port: 80/8000  │         │  Port: 3306     │
└─────────────────┘         └─────────────────┘         └─────────────────┘
```

### Stack Technologique

**Frontend :**
- Framework : React 18.3+ avec TypeScript
- Build Tool : Vite 5.4+
- UI Framework : TailwindCSS 3.4+ avec shadcn/ui
- State Management : React Context API + React Query
- Routing : React Router DOM 6.30+
- Validation : Zod 3.25+ avec React Hook Form

**Backend :**
- Langage : PHP 8.2+ (strict types)
- Architecture : API REST monolithique avec routing personnalisé
- Base de données : MySQL/MariaDB via PDO
- Dépendances : Composer (PHPMailer, dompdf)
- Serveur : Apache (XAMPP) ou serveur PHP intégré

---

## Prérequis

### Logiciels Requis

| Logiciel | Version Minimale | Description |
|----------|------------------|-------------|
| **XAMPP** | 8.2+ | Serveur web local (Apache + MySQL) |
| **Node.js** | 18.0+ | Runtime JavaScript pour le frontend |
| **npm** | 9.0+ | Gestionnaire de paquets Node.js |
| **Composer** | 2.6+ | Gestionnaire de dépendances PHP |
| **Git** | 2.40+ | Contrôle de version  |
| **PHP** | 8.2+ | Inclus dans XAMPP |

### Vérification des Prérequis

```powershell
# Vérifier Node.js
node --version

# Vérifier npm
npm --version

# Vérifier Composer
composer --version

# Vérifier PHP
php --version

# Vérifier Git
git --version
```

---

## Installation et Configuration

### Étape 1 : Clonage du Projet

```powershell
# Cloner le dépôt
git clone https://github.com/tafraouti-sanae01/PLATEFORME_GESTION_SERVICES_ETUDIANTS.git
cd PLATEFORME_GESTION_SERVICES_ETUDIANTS
```

### Étape 2 : Configuration de la Base de Données

#### 2.1 Création de la Base de Données

1. Démarrer **XAMPP** (Apache + MySQL)
2. Ouvrir phpMyAdmin : `http://localhost/phpmyadmin`
3. Créer une nouvelle base de données :
   - Nom : `ecole_db`
   - Encodage : `utf8mb4_general_ci`

#### 2.2 Import du Schéma

1. Sélectionner la base `ecole_db`
2. Aller dans l'onglet **Importer**
3. Choisir le fichier : `backend/ecole_db.sql`
4. Cliquer sur **Exécuter**

#### 2.3 Configuration de la Connexion

Éditer `backend/config.php` ou définir les variables d'environnement :

```php
<?php
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'dbname' => getenv('DB_NAME') ?: 'ecole_db',
    'user' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
```

**Variables d'environnement (optionnel) :**
```powershell
$env:DB_HOST="127.0.0.1"
$env:DB_NAME="ecole_db"
$env:DB_USER="root"
$env:DB_PASSWORD=""
```

### Étape 3 : Configuration du Backend

#### 3.1 Installation des Dépendances PHP

```powershell
cd backend
composer install
```

**Vérification :**
- Le dossier `vendor/` doit être créé
- Le fichier `vendor/autoload.php` doit exister

#### 3.2 Configuration Email 

```powershell
# Copier le fichier d'exemple
copy email_config.example.php email_config.php

# Éditer email_config.php avec vos identifiants SMTP
```

**Note :** Le fichier `email_config.php` est dans `.gitignore` et ne sera pas versionné.

#### 3.3 Test du Backend

**Option A : Via XAMPP Apache**
```
URL : http://localhost/Service-scolarite/backend/api/health
Réponse attendue : {"status":"ok","timestamp":...}
```

**Option B : Via Serveur PHP Intégré**
```powershell
cd backend
php -S localhost:8000
# URL : http://localhost:8000/api/health
```

### Étape 4 : Configuration du Frontend

#### 4.1 Installation des Dépendances

```powershell
# Important : Toutes les commandes npm doivent être exécutées depuis frontend/
cd frontend
npm install
```

**Note :** Cette étape peut prendre plusieurs minutes lors de la première installation.

#### 4.2 Configuration de l'URL API

Le proxy Vite est configuré dans `frontend/vite.config.ts`. En développement, les requêtes `/api/*` sont automatiquement redirigées vers le backend.

**Personnalisation (optionnel) :**

Créer un fichier `.env` dans `frontend/` :
```env
VITE_API_URL=http://localhost/PLATEFORME_GESTION_SERVICES_ETUDIANTS/backend
```

**Important :** Ne pas inclure `/api` dans l'URL, il est ajouté automatiquement.

#### 4.3 Lancement du Serveur de Développement

```powershell
cd frontend
npm run dev
```

**Accès :** `http://localhost:5173`

---

## Structure du Projet

```
Service-scolarite/
├── backend/                          # API REST Backend
│   ├── index.php                     # Point d'entrée unique et routeur API
│   ├── Database.php                  # Gestion de la connexion PDO
│   ├── config.php                    # Configuration de la base de données
│   ├── helpers.php                   # Fonctions utilitaires (send_json, send_error)
│   ├── EmailService.php              # Service d'envoi d'emails (PHPMailer)
│   ├── email_config.example.php      # Template de configuration email
│   ├── composer.json                 # Dépendances PHP (PHPMailer, dompdf)
│   ├── composer.lock                 # Verrouillage des versions
│   ├── .htaccess                     # Configuration Apache (routing)
│   └── ecole_db.sql                  # Schéma de la base de données
│
├── frontend/                         # Application React Frontend
│   ├── src/
│   │   ├── pages/                    # Pages de l'application
│   │   │   ├── Index.tsx             # Page d'accueil publique
│   │   │   ├── Reclamation.tsx       # Page de réclamation
│   │   │   ├── NotFound.tsx          # Page 404
│   │   │   └── admin/                # Pages administration
│   │   │       ├── Login.tsx         # Authentification admin
│   │   │       ├── Dashboard.tsx     # Tableau de bord
│   │   │       ├── Demandes.tsx      # Gestion des demandes
│   │   │       ├── Historique.tsx    # Historique des demandes
│   │   │       └── Reclamations.tsx  # Gestion des réclamations
│   │   │
│   │   ├── components/               # Composants React
│   │   │   ├── forms/                # Formulaires
│   │   │   │   ├── UnifiedRequestForm.tsx    # Formulaire unifié de demande
│   │   │   │   ├── RequestTracker.tsx        # Suivi de demande
│   │   │   │   └── ComplaintForm.tsx         # Formulaire de réclamation
│   │   │   ├── admin/                # Composants admin
│   │   │   │   ├── RequestsTable.tsx # Tableau des demandes
│   │   │   │   ├── ComplaintsTable.tsx       # Tableau des réclamations
│   │   │   │   └── StatsCard.tsx     # Cartes statistiques
│   │   │   ├── layout/               # Composants de mise en page
│   │   │   │   ├── Header.tsx        # En-tête public
│   │   │   │   ├── AdminLayout.tsx   # Layout administration
│   │   │   │   └── AdminSidebar.tsx  # Navigation admin
│   │   │   ├── auth/                 # Authentification
│   │   │   │   └── ProtectedRoute.tsx # Protection des routes
│   │   │   └── ui/                   # Composants UI (shadcn/ui)
│   │   │
│   │   ├── contexts/                 # Contextes React
│   │   │   └── AppContext.tsx        # État global de l'application
│   │   │
│   │   ├── lib/                      # Bibliothèques et utilitaires
│   │   │   ├── api.ts                # Client API (fonctions fetch)
│   │   │   └── utils.ts              # Fonctions utilitaires
│   │   │
│   │   ├── types/                    # Définitions TypeScript
│   │   │   └── index.ts              # Types et interfaces
│   │   │
│   │   ├── hooks/                    # Hooks React personnalisés
│   │   ├── data/                     # Données mockées (développement)
│   │   │   └── mockData.ts
│   │   ├── App.tsx                   # Composant racine
│   │   ├── main.tsx                  # Point d'entrée React
│   │   └── index.css                 # Styles globaux
│   │
│   ├── public/                       # Fichiers statiques
│   ├── index.html                    # HTML principal
│   ├── package.json                  # Dépendances Node.js
│   ├── vite.config.ts                # Configuration Vite
│   ├── tsconfig.json                 # Configuration TypeScript
│   ├── tailwind.config.ts            # Configuration TailwindCSS
│   └── postcss.config.js             # Configuration PostCSS
│
├── .gitignore                        # Fichiers ignorés par Git
└── README.md                         # Ce fichier
```

---

## Fonctionnalités

### 1. Gestion des Demandes de Documents

Le système prend en charge **quatre types de documents** :

#### 1.1 Attestation de Scolarité
- Document certifiant l'inscription d'un étudiant
- Génération PDF automatique
- Validation des informations étudiantes

#### 1.2 Attestation de Réussite
- Certificat de réussite pour une année universitaire spécifique
- Association avec une année académique
- Format PDF standardisé

#### 1.3 Relevé de Notes
- Relevé des notes pour un semestre donné
- Intégration avec les données académiques de la base
- Affichage des modules et notes associées

#### 1.4 Convention de Stage
- Document de convention entre l'établissement et l'entreprise
- Champs spécifiques : entreprise, encadrant, dates, sujet
- Validation des informations académiques et professionnelles

### 2. Système de Réclamations

- **Soumission** : Les étudiants peuvent soumettre des réclamations liées à leurs demandes
- **Suivi** : Numéro de référence unique pour chaque réclamation
- **Traitement** : Interface admin pour répondre aux réclamations
- **Notifications** : Emails automatiques lors des changements de statut

### 3. Interface Étudiante (Publique)

- **Page d'accueil** : Présentation des services et accès rapide aux fonctionnalités
- **Formulaire unifié** : Interface unique pour tous les types de demandes
- **Validation en temps réel** : Vérification des informations étudiantes (email, apogée, CIN)
- **Suivi de demande** : Recherche par numéro de référence
- **Gestion des réclamations** : Formulaire dédié avec lien aux demandes

### 4. Interface Administrative

#### 4.1 Authentification
- Système de connexion sécurisé
- Gestion de session via localStorage
- Protection des routes sensibles

#### 4.2 Tableau de Bord
- **Statistiques globales** : Vue d'ensemble des demandes
- **Répartition** : Distribution par type de document et statut
- **Demandes urgentes** : Liste des demandes en attente
- **Graphiques** : Visualisations des tendances

#### 4.3 Gestion des Demandes
- **Liste complète** : Affichage de toutes les demandes avec filtres
- **Actions** : Acceptation, refus, changement de statut
- **Téléchargement PDF** : Génération et téléchargement des documents
- **Envoi d'email** : Notifications personnalisées aux étudiants
- **Recherche** : Par nom, apogée, référence

#### 4.4 Historique
- Consultation de toutes les demandes traitées
- Filtres avancés (date, type, statut)
- Export des données (fonctionnalité future)

#### 4.5 Gestion des Réclamations
- Liste des réclamations avec filtres par statut
- Détails complets de chaque réclamation
- Réponse aux réclamations avec traçabilité admin
- Lien avec les demandes associées

### 5. Fonctionnalités Techniques

#### 5.1 Génération PDF
- **Bibliothèque** : dompdf 3.1+
- **Templates** : HTML/CSS convertis en PDF
- **Types** : Support de tous les types de documents
- **Encodage** : UTF-8 pour caractères spéciaux

#### 5.2 Système d'Email
- **Service** : PHPMailer 7.0+
- **Templates HTML** : Emails stylisés et responsives
- **Notifications automatiques** :
  - Confirmation de création de demande
  - Changement de statut
  - Réponse à réclamation
- **Pièces jointes** : Support PDF en annexe

#### 5.3 Numérotation
- **Format** : Numéros de référence uniques
- **Exemple** : `REQ-2024-001`, `REC-2024-001`
- **Traçabilité** : Association avec chaque demande/réclamation

---

## Documentation de l'API

### Base URL

- **Développement** : `http://localhost/PLATEFORME_GESTION_SERVICES_ETUDIANTS/backend`
- **Production** : `[À configurer]`

### Format des Réponses

Toutes les réponses sont au format JSON, avec les codes HTTP standards :

- `200 OK` : Succès
- `201 Created` : Ressource créée
- `400 Bad Request` : Erreur de validation
- `401 Unauthorized` : Non authentifié
- `404 Not Found` : Ressource introuvable
- `500 Internal Server Error` : Erreur serveur

### Endpoints Principaux

#### Santé de l'API

```http
GET /api/health
```

**Réponse :**
```json
{
  "status": "ok",
  "timestamp": 1234567890
}
```

---

#### Authentification

##### Connexion Administrateur

```http
POST /api/login
Content-Type: application/json

{
  "email": "admin@uae.ac.ma",
  "password": "admin123"
}
```

**Réponse :**
```json
{
  "id": "ADM001",
  "email": "admin@uae.ac.ma",
  "name": "Admin"
}
```

---

#### Demandes de Documents

##### Liste des Demandes

```http
GET /api/requests
```

**Réponse :**
```json
[
  {
    "id": "D001",
    "referenceNumber": "REQ-2024-001",
    "studentId": "ETU001",
    "student": {
      "id": "ETU001",
      "email": "etudiant@example.com",
      "apogee": "123456",
      "cin": "AB123456",
      "firstName": "Prénom",
      "lastName": "Nom",
      "filiere": "Informatique",
      "niveau": "L3"
    },
    "documentType": "attestation_scolarite",
    "status": "pending",
    "createdAt": "2024-01-15T10:30:00Z"
  }
]
```

##### Création d'une Demande

```http
POST /api/requests
Content-Type: application/json

{
  "email": "etudiant@example.com",
  "apogee": "123456",
  "cin": "AB123456",
  "documentType": "attestation_scolarite",
  "academicYear": "2023-2024"
}
```

**Réponse :**
```json
{
  "id": "D001",
  "referenceNumber": "REQ-2024-001",
  "message": "Demande créée avec succès"
}
```

##### Mise à Jour du Statut

```http
POST /api/requests/{id}/status
Content-Type: application/json

{
  "status": "accepted",
  "adminId": "ADM001"
}
```

##### Téléchargement PDF

```http
GET /api/requests/{id}/download
```

**Réponse :** Fichier PDF en binaire

##### Envoi d'Email Personnalisé

```http
POST /api/requests/{id}/send-email
Content-Type: application/json

{
  "message": "Message personnalisé à l'étudiant"
}
```

---

#### Réclamations

##### Liste des Réclamations

```http
GET /api/complaints
```

##### Création d'une Réclamation

```http
POST /api/complaints
Content-Type: application/json

{
  "email": "etudiant@example.com",
  "apogee": "123456",
  "cin": "AB123456",
  "subject": "Sujet de la réclamation",
  "description": "Description détaillée",
  "relatedRequestNumber": "REQ-2024-001"
}
```

##### Réponse à une Réclamation

```http
POST /api/complaints/{id}/response
Content-Type: application/json

{
  "response": "Réponse de l'administrateur",
  "adminId": "ADM001"
}
```

##### Détails d'une Réclamation

```http
GET /api/complaints/{id}
```

---

#### Validation et Données de Référence

##### Validation d'un Étudiant

```http
POST /api/students/validate
Content-Type: application/json

{
  "email": "etudiant@example.com",
  "apogee": "123456",
  "cin": "AB123456"
}
```

**Réponse :**
```json
{
  "valid": true,
  "student": {
    "id": "ETU001",
    "email": "etudiant@example.com",
    "firstName": "Prénom",
    "lastName": "Nom",
    "filiere": "Informatique",
    "niveau": "L3"
  }
}
```

##### Années Académiques

```http
GET /api/academic-years
```

##### Semestres

```http
GET /api/semesters
```

##### Encadrants Pédagogiques

```http
GET /api/supervisors
```

##### Demandes d'un Étudiant

```http
POST /api/students/demands
Content-Type: application/json

{
  "apogee": "123456",
  "cin": "AB123456"
}
```

---

## Guide d'Utilisation

### Pour les Étudiants

#### Soumettre une Demande

1. Accéder à la page d'accueil : `http://localhost:5173`
2. Remplir le formulaire avec :
   - Email, numéro d'apogée, CIN
   - Type de document souhaité
   - Informations supplémentaires selon le type
3. Valider le formulaire
4. Recevoir une confirmation par email
5. Noter le **numéro de référence** généré

#### Suivre une Demande

1. Accéder à la section "Suivi de demande"
2. Entrer le numéro de référence (format : `REQ-YYYY-XXX`)
3. Consulter le statut et les détails

#### Soumettre une Réclamation

1. Accéder à la page "Réclamation"
2. Remplir le formulaire
3. Optionnellement, lier à une demande existante
4. Soumettre la réclamation

### Pour les Administrateurs

#### Connexion

1. Accéder à : `http://localhost:5173/admin/login`
2. S'authentifier avec :
   - **Email** : `admin@uae.ac.ma`
   - **Mot de passe** : `admin123`

#### Traiter une Demande

1. Accéder au tableau de bord ou à la section "Demandes"
2. Filtrer par statut (ex: "En attente")
3. Sélectionner une demande
4. Actions disponibles :
   - **Accepter** : Change le statut à "acceptée"
   - **Refuser** : Change le statut à "refusée"
   - **Télécharger PDF** : Génère et télécharge le document
   - **Envoyer Email** : Notifie l'étudiant
5. Les actions enregistrent automatiquement l'ID de l'admin

#### Gérer les Réclamations

1. Accéder à la section "Réclamations"
2. Filtrer par statut
3. Consulter les détails d'une réclamation
4. Répondre avec un message personnalisé
5. Le statut passe automatiquement à "résolu"

---

## Base de Données

### Schéma Relationnel

Le système utilise une base de données MySQL avec les tables principales suivantes :

#### Tables Principales

| Table | Description | Relations |
|-------|-------------|-----------|
| `etudiants` | Informations des étudiants | - |
| `demandes` | Demandes de documents | → `etudiants`, `administrateurs` |
| `reclamations` | Réclamations étudiantes | → `etudiants`, `demandes` |
| `administrateurs` | Comptes administrateurs | - |
| `attestations_reussite` | Détails attestations de réussite | → `demandes` |
| `releves_notes` | Détails relevés de notes | → `demandes` |
| `conventions_stage` | Détails conventions de stage | → `demandes` |

#### Structure des Tables Clés

**Table `demandes`**
- `id_demande` (PK) : Identifiant unique
- `numero_reference` : Numéro de référence (REQ-YYYY-XXX)
- `id_etudiant` (FK) : Référence à l'étudiant
- `type_document` : Type de document
- `statut` : pending, accepted, rejected
- `date_creation` : Date de création
- `date_traitement` : Date de traitement
- `id_administrateur` (FK) : Admin ayant traité

**Table `reclamations`**
- `id_reclamation` (PK) : Identifiant unique
- `numero_reference` : Numéro de référence (REC-YYYY-XXX)
- `id_etudiant` (FK) : Référence à l'étudiant
- `sujet` : Sujet de la réclamation
- `description` : Description détaillée
- `statut` : pending, resolved
- `reponse` : Réponse de l'administrateur
- `date_reponse` : Date de réponse
- `id_administrateur` (FK) : Admin ayant répondu

### Données de Test

Le schéma SQL inclut des données de test pour faciliter le développement :
- **Administrateurs** : 5 comptes par défaut (voir identifiants ci-dessous)
- **Étudiants** : Exemples d'étudiants
- **Années académiques** : De 2020-2021 à 2024-2025

### Identifiants par Défaut

**Administrateurs :**
- Email : `admin@uae.ac.ma` / Mot de passe : `admin123`
- Email : `scolarite@uae.ac.ma` / Mot de passe : `admin123`
- Email : `secretariat@uae.ac.ma` / Mot de passe : `admin123`
- Email : `directeur@uae.ac.ma` / Mot de passe : `admin123`
- Email : `stage@uae.ac.ma` / Mot de passe : `admin123`

**Note :** Tous les mots de passe par défaut sont hashés avec `password_hash()` de PHP.

---

## Technologies Utilisées

### Frontend

| Technologie | Version | Rôle |
|-------------|---------|------|
| **React** | 18.3.1 | Bibliothèque UI |
| **TypeScript** | 5.8.3 | Typage statique |
| **Vite** | 5.4.19 | Build tool et dev server |
| **TailwindCSS** | 3.4.17 | Framework CSS utility-first |
| **shadcn/ui** | Latest | Composants UI réutilisables |
| **React Router DOM** | 6.30.1 | Routing côté client |
| **React Hook Form** | 7.61.1 | Gestion de formulaires |
| **Zod** | 3.25.76 | Validation de schémas |
| **TanStack Query** | 5.83.0 | Gestion de l'état serveur |
| **Lucide React** | 0.462.0 | Icônes |

### Backend

| Technologie | Version | Rôle |
|-------------|---------|------|
| **PHP** | 8.2+ | Langage serveur |
| **PDO** | Native | Accès base de données |
| **PHPMailer** | 7.0+ | Envoi d'emails SMTP |
| **dompdf** | 3.1+ | Génération PDF |
| **Composer** | 2.6+ | Gestionnaire de dépendances |

### Base de Données

| Technologie | Version | Rôle |
|-------------|---------|------|
| **MySQL/MariaDB** | 10.4+ | Système de gestion de base de données |
| **utf8mb4** | - | Encodage des caractères |

### Outils de Développement

| Outil | Rôle |
|-------|------|
| **XAMPP** | Environnement de développement local |
| **phpMyAdmin** | Interface web pour MySQL |
| **Git** | Contrôle de version |
| **ESLint** | Linter JavaScript/TypeScript |
| **Prettier** | Formateur de code (optionnel) |

---

## Développement

### Scripts Disponibles

#### Frontend

```powershell
# Serveur de développement
npm run dev

# Build de production
npm run build

# Preview du build de production
npm run preview

# Linter
npm run lint
```

#### Backend

Le backend n'utilise pas de scripts npm. Les commandes principales sont :

```powershell
# Installer les dépendances
composer install

# Mettre à jour les dépendances
composer update

# Serveur PHP intégré (alternative à Apache)
php -S localhost:8000
```

### Structure de Développement

Le projet suit une architecture modulaire :

- **Séparation des responsabilités** : Frontend et Backend indépendants
- **API REST** : Communication standardisée entre client et serveur
- **TypeScript** : Typage fort pour réduire les erreurs
- **Composants réutilisables** : Architecture component-based avec React
- **Gestion d'état centralisée** : Context API pour l'état global

### Bonnes Pratiques

1. **Code** :
   - Respect des conventions PSR pour PHP
   - ESLint configuré pour TypeScript/React
   - Commentaires pour les fonctions complexes

2. **Sécurité** :
   - Prepared statements pour toutes les requêtes SQL
   - Validation côté serveur et client
   - Protection CORS configurée
   - Mots de passe hashés (bcrypt)

3. **Performance** :
   - Lazy loading des composants React
   - Cache avec React Query
   - Optimisation des requêtes SQL avec indexes

### Dépannage

#### Erreur : "Cannot connect to backend"

**Solutions :**
1. Vérifier que Apache est démarré dans XAMPP
2. Vérifier l'URL dans `vite.config.ts` (proxy)
3. Vérifier l'URL dans `frontend/src/lib/api.ts`
4. Vérifier la console du navigateur (F12) pour les erreurs CORS

#### Erreur : "Database connection error"

**Solutions :**
1. Vérifier que MySQL est démarré dans XAMPP
2. Vérifier les paramètres dans `backend/config.php`
3. Vérifier que la base de données existe
4. Vérifier que le schéma a été importé

#### Erreur : "Port 5173 already in use"

**Solution :**
```powershell
# Trouver le processus
netstat -ano | findstr :5173

# Tuer le processus (remplacer PID)
taskkill /PID <PID> /F
```

#### Erreur : Module not found (frontend)

**Solution :**
```powershell
cd frontend
rm -rf node_modules package-lock.json
npm install
```

---

## Références

- **React Documentation** : https://react.dev
- **PHP Documentation** : https://www.php.net/docs.php
- **MySQL Documentation** : https://dev.mysql.com/doc/
- **Vite Documentation** : https://vitejs.dev
- **TailwindCSS Documentation** : https://tailwindcss.com/docs
- **shadcn/ui** : https://ui.shadcn.com


