# Service Scolarité – Front + Backend PHP

## Structure du projet
```
Service-scolarite/
├── backend/          # API PHP
├── frontend/         # Application React (Vite)
└── ecole_db.sql      # Schéma de base de données
```

## Base de données
- Schéma : `ecole_db.sql`
- Données de test : `backend/sample_data.sql`
  - administrateur : email `admin@univ.ma` / mot de passe `admin123`

## Backend PHP (API)
1. Importer le schéma puis les données de test dans phpMyAdmin :
   ```sql
   SOURCE ecole_db.sql;
   SOURCE backend/sample_data.sql;
   ```
2. Mettre vos identifiants MySQL dans `backend/config.php` ou via les variables d'environnement `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
3. Le backend est accessible via XAMPP Apache :
   - URL : `http://localhost/Service-scolarite/backend/`
   - Ou via un serveur PHP intégré :
     ```bash
     cd backend
     php -S localhost:8000
     ```
4. Endpoints exposés :
   - `GET /api/health`
   - `GET /api/requests` : demandes + infos étudiants
   - `POST /api/requests/{id}/status` body `{ "status": "accepted"|"rejected"|"pending", "adminId": "..." }`
   - `GET /api/complaints`
   - `POST /api/complaints` : créer une réclamation
   - `POST /api/complaints/{id}/response` : répondre à une réclamation
   - `POST /api/login` body `{ "email", "password" }`
   - `POST /api/students/validate` : valider un étudiant

## Frontend (Vite React)

⚠️ **IMPORTANT** : Toutes les commandes npm doivent être exécutées depuis le dossier `frontend` !

1. Aller dans le dossier frontend :
   ```powershell
   cd frontend
   ```

2. Installer les dépendances (première fois seulement) :
   ```powershell
   npm install
   ```

3. Configurer l'URL API (optionnel) :
   - En développement, le proxy Vite redirige automatiquement `/api` vers `http://localhost/Service-scolarite/backend`
   - Pour la production, créer un fichier `.env` dans `frontend/` :
     ```
     VITE_API_URL=http://localhost/Service-scolarite/backend/api
     ```

4. Lancer le serveur de développement :
   ```powershell
   npm run dev
   ```
   Le front sera accessible sur `http://localhost:5173`

### ⚠️ Erreur courante

Si vous obtenez l'erreur `ENOENT: no such file or directory, open 'package.json'`, c'est que vous êtes dans le mauvais dossier. Assurez-vous d'être dans `frontend/` :

```powershell
# ❌ Ne pas faire depuis la racine
PS C:\xampp\htdocs\Service-scolarite> npm install

# ✅ Faire depuis le dossier frontend
PS C:\xampp\htdocs\Service-scolarite> cd frontend
PS C:\xampp\htdocs\Service-scolarite\frontend> npm install
```

## Fonctionnement
- Le front récupère les demandes et réclamations via l'API. Si l'API est indisponible, il affiche les données de démonstration existantes (`frontend/src/data/mockData.ts`).
- La connexion admin utilise l'API ; un fallback de démo reste disponible (`admin@univ.ma` / `admin123`).
- La session admin est persistante via `localStorage` (reste connecté après refresh).
- Les actions admin (accepter/rejeter demandes, répondre aux réclamations) enregistrent automatiquement l'ID de l'administrateur dans la base de données.
