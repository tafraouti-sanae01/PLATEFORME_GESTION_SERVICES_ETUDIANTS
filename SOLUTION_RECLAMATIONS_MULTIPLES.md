# Solution : Réclamations Multiples sur le Même Document

## Problème Identifié

La plateforme ne permettait pas aux étudiants de créer plusieurs réclamations sur la même demande de document. Par exemple :
- Un étudiant demande un relevé de notes
- Après une semaine sans traitement, il réclame (1ère réclamation)
- L'admin traite et envoie le document
- L'étudiant remarque des erreurs dans les informations personnelles ou les notes
- Il veut réclamer une deuxième fois, mais le système l'empêchait

## Cause du Problème

La table `reclamations` dans la base de données avait une **contrainte UNIQUE** sur la colonne `id_demande`, ce qui empêchait plusieurs réclamations d'être liées à la même demande.

```sql
-- AVANT (problématique)
ALTER TABLE `reclamations`
  ADD UNIQUE KEY `id_demande` (`id_demande`),  -- ❌ Empêche plusieurs réclamations
```

## Solution Implémentée

### 1. Modification de la Base de Données

**Fichier créé :** `backend/migration_allow_multiple_complaints.sql`

Ce script supprime la contrainte UNIQUE sur `id_demande` :

```sql
ALTER TABLE `reclamations` DROP INDEX `id_demande`;
```

**Fichier modifié :** `backend/ecole_db.sql`

La contrainte UNIQUE a été supprimée du fichier SQL principal pour les futures installations :

```sql
-- APRÈS (corrigé)
ALTER TABLE `reclamations`
  ADD KEY `id_demande` (`id_demande`),  -- ✅ Index simple, pas UNIQUE
```

### 2. Vérification du Code

Le code backend et frontend était déjà compatible avec plusieurs réclamations :

- ✅ **Backend** : La fonction `handle_create_complaint()` ne vérifie pas si une réclamation existe déjà pour la demande
- ✅ **Backend** : La fonction `handle_get_complaints()` récupère toutes les réclamations sans filtre
- ✅ **Frontend** : Le formulaire de réclamation permet de sélectionner n'importe quelle demande
- ✅ **Frontend** : L'interface admin affiche toutes les réclamations correctement

## Instructions d'Installation

### Pour une Base de Données Existante

1. Exécutez le script de migration :
   ```sql
   source backend/migration_allow_multiple_complaints.sql
   ```
   
   Ou via phpMyAdmin :
   - Ouvrez phpMyAdmin
   - Sélectionnez la base de données `ecole_db`
   - Allez dans l'onglet "SQL"
   - Copiez-collez le contenu de `backend/migration_allow_multiple_complaints.sql`
   - Cliquez sur "Exécuter"

### Pour une Nouvelle Installation

Le fichier `backend/ecole_db.sql` a été mis à jour. Aucune action supplémentaire n'est nécessaire.

## Fonctionnement Après Correction

Maintenant, un étudiant peut :

1. **Créer une première réclamation** sur une demande (ex: "Retard dans le traitement")
2. **Créer une deuxième réclamation** sur la même demande après traitement (ex: "Erreurs dans les informations")
3. **Créer une troisième réclamation** si nécessaire (ex: "Erreurs dans les notes")

Chaque réclamation aura :
- Un **ID unique** (`id_reclamation`)
- Un **numéro de référence unique** (`numero_reference`, format : `REC-2024-001`, `REC-2024-002`, etc.)
- Un **statut indépendant** (en attente / résolu)
- Une **date de création** propre
- Une **réponse de l'admin** indépendante

## Exemple de Scénario

### Scénario : Réclamations sur un Relevé de Notes

1. **Demande initiale** : `REQ-2024-003` - Relevé de notes S1
2. **1ère réclamation** : `REC-2024-001` - "Retard dans le traitement" (après 1 semaine)
3. **Admin traite** : Envoie le relevé de notes
4. **2ème réclamation** : `REC-2024-002` - "Erreur dans le nom (écrit 'Benali' au lieu de 'Ben Ali')"
5. **Admin corrige** : Met à jour le document
6. **3ème réclamation** : `REC-2024-003` - "Note du module MOD001 manquante"
7. **Admin corrige** : Ajoute la note manquante

Toutes ces réclamations sont liées à la même demande `REQ-2024-003` mais sont des entités distinctes avec leurs propres statuts et réponses.

## Vérification

Pour vérifier que la correction fonctionne :

1. Connectez-vous en tant qu'étudiant
2. Créez une réclamation sur une demande existante
3. Créez une deuxième réclamation sur la même demande
4. Vérifiez dans l'interface admin que les deux réclamations apparaissent

## Notes Techniques

- La contrainte UNIQUE sur `numero_reference` est conservée (chaque réclamation doit avoir un numéro unique)
- La clé étrangère `id_demande` est conservée (relation avec la table `demandes`)
- L'index sur `id_demande` est conservé (pour les performances des requêtes)
- Aucun changement n'est nécessaire dans le code PHP ou TypeScript

## Fichiers Modifiés

1. ✅ `backend/ecole_db.sql` - Suppression de la contrainte UNIQUE
2. ✅ `backend/migration_allow_multiple_complaints.sql` - Script de migration créé

## Fichiers Vérifiés (Aucun Changement Nécessaire)

- ✅ `backend/index.php` - Déjà compatible
- ✅ `frontend/src/components/forms/ComplaintForm.tsx` - Déjà compatible
- ✅ `frontend/src/components/admin/ComplaintsTable.tsx` - Déjà compatible
- ✅ `frontend/src/lib/api.ts` - Déjà compatible

