# Cas d'envoi automatique d'e-mails dans la plateforme

## Analyse complète du projet

Après analyse complète du codebase, voici tous les cas où des e-mails sont envoyés automatiquement.

---

## ⚠️ IMPORTANT : Aucun envoi quotidien programmé

**Aucun e-mail n'est envoyé automatiquement au quotidien** (pas de tâches cron, pas de scripts planifiés). Tous les e-mails sont envoyés de manière **événementielle**, c'est-à-dire déclenchés par des actions spécifiques des utilisateurs ou des administrateurs.

---

## 📧 Liste complète des cas d'envoi automatique d'e-mails

### 1. **Confirmation de création de demande** ✅
**Fichier**: `backend/index.php` (lignes 535-611)  
**Fonction**: `handle_create_request()`  
**Déclencheur**: Lorsqu'un étudiant crée une nouvelle demande de document  
**Quand**: Immédiatement après la création de la demande  
**Destinataire**: L'étudiant qui a créé la demande  
**Contenu**:
- Confirmation de réception de la demande
- Numéro de référence
- Type de document demandé
- Date de la demande
- Détails spécifiques selon le type (année universitaire, semestre, entreprise, etc.)
- Message indiquant que la demande est en cours de traitement

**Code concerné**:
```php
// Ligne 598
send_email_to_student($student['email'], $subject, $message, false);
```

---

### 2. **Acceptation d'une demande (avec PDF)** ✅
**Fichier**: `backend/index.php` (lignes 658-718)  
**Fonction**: `handle_update_request_status()`  
**Déclencheur**: Lorsqu'un administrateur accepte une demande (statut → "accepted"/"traite")  
**Quand**: Immédiatement après le changement de statut à "accepted"  
**Destinataire**: L'étudiant propriétaire de la demande  
**Contenu**:
- Notification que la demande a été traitée avec succès
- Numéro de référence
- Type de document
- **Pièce jointe**: PDF du document généré automatiquement

**Code concerné**:
```php
// Ligne 705
$result = send_email_to_student($request['etu_email'], $subject, $message, true, $pdfAttachment);
```

---

### 3. **Refus d'une demande (sans PDF)** ✅
**Fichier**: `backend/index.php` (lignes 720-787)  
**Fonction**: `handle_update_request_status()`  
**Déclencheur**: Lorsqu'un administrateur refuse une demande (statut → "rejected"/"refuse")  
**Quand**: Immédiatement après le changement de statut à "rejected"  
**Destinataire**: L'étudiant propriétaire de la demande  
**Contenu**:
- Notification que la demande a été refusée
- Numéro de référence
- Type de document
- Raisons du refus (si fournies par l'administrateur)
- Message invitant à contacter le service de la scolarité

**Code concerné**:
```php
// Ligne 774
$result = send_email_to_student($request['etu_email'], $subject, $message, true, $pdfAttachment);
```

---

### 4. **Envoi manuel d'e-mail par administrateur** ✅
**Fichier**: `backend/index.php` (lignes 859-973)  
**Fonction**: `handle_send_email()`  
**Déclencheur**: Lorsqu'un administrateur envoie manuellement un e-mail via l'interface  
**Quand**: Sur action manuelle de l'administrateur  
**Route API**: `POST /api/requests/{id}/send-email`  
**Destinataire**: L'étudiant propriétaire de la demande  
**Contenu**:
- Sujet et message personnalisés par l'administrateur (ou template par défaut)
- Si la demande est traitée, le PDF est automatiquement joint

**Code concerné**:
```php
// Ligne 939
$result = send_email_to_student($to, $subject, $message, true, $pdfAttachment);
```

---

### 5. **Réponse à une réclamation** ✅
**Fichier**: `backend/index.php` (lignes 3049-3085)  
**Fonction**: `handle_respond_to_complaint()`  
**Déclencheur**: Lorsqu'un administrateur répond à une réclamation  
**Quand**: Immédiatement après l'enregistrement de la réponse  
**Destinataire**: L'étudiant qui a soumis la réclamation  
**Contenu**:
- Réponse de l'administrateur à la réclamation
- Numéro de référence de la réclamation
- Objet de la réclamation
- Message invitant à contacter le service si besoin

**Code concerné**:
```php
// Ligne 3068
$result = send_email_to_student($to, $subject, $message, true);
```

---

### ❌ **Création d'une réclamation** (AUCUN E-MAIL)
**Important**: Lorsqu'un étudiant crée une réclamation, **aucun e-mail automatique n'est envoyé**. Seule une notification s'affiche dans l'interface indiquant que la réclamation a été enregistrée et qu'une réponse sera fournie dans les plus brefs délais.

---

## 📋 Résumé des cas

| # | Cas | Déclencheur | Fréquence | PDF joint |
|---|-----|-------------|-----------|-----------|
| 1 | Confirmation de création | Création de demande | Événementiel | ❌ Non |
| 2 | Acceptation de demande | Changement statut → "accepted" | Événementiel | ✅ Oui |
| 3 | Refus de demande | Changement statut → "rejected" | Événementiel | ❌ Non |
| 4 | Envoi manuel | Action administrateur | Événementiel | ✅ Si traité |
| 5 | Réponse réclamation | Réponse administrateur | Événementiel | ❌ Non |
| - | **Création réclamation** | **Création réclamation** | **❌ AUCUN** | **❌ Non** |

---

## 🔍 Détails techniques

### Service d'e-mail utilisé
- **Fichier**: `backend/EmailService.php`
- **Fonction principale**: `send_email_to_student()`
- **Support**: PHPMailer (si disponible) ou fonction `mail()` native PHP
- **Configuration**: `backend/email_config.php`

### Gestion des erreurs
Tous les envois d'e-mails sont encapsulés dans des blocs `try-catch` pour ne pas faire échouer les opérations principales si l'envoi d'e-mail échoue. Les erreurs sont loggées dans les logs PHP.

### Mode développement
En mode développement (localhost), les e-mails sont simulés et loggés dans les logs PHP au lieu d'être réellement envoyés.

---

## ❌ Cas NON implémentés (quotidiens)

Aucun des cas suivants n'est actuellement implémenté :
- ❌ Rappels quotidiens pour les demandes en attente
- ❌ Notifications quotidiennes aux administrateurs (demandes en attente)
- ❌ Rappels de réclamations non résolues
- ❌ Statistiques quotidiennes par e-mail
- ❌ Notifications de délais dépassés
- ❌ Tâches cron planifiées

---

## 💡 Recommandations

Si vous souhaitez ajouter des envois quotidiens automatiques, vous devrez :

1. **Créer un script PHP** (ex: `backend/cron/daily_emails.php`)
2. **Configurer une tâche cron** sur le serveur pour exécuter ce script quotidiennement
3. **Implémenter la logique** pour :
   - Identifier les demandes nécessitant un rappel
   - Générer les e-mails appropriés
   - Utiliser `send_email_to_student()` pour l'envoi

Exemple de commande cron :
```bash
# Exécuter tous les jours à 9h00
0 9 * * * /usr/bin/php /chemin/vers/backend/cron/daily_emails.php
```

---

**Date d'analyse**: Analyse effectuée le jour de la demande  
**Fichiers analysés**: 
- `backend/index.php`
- `backend/EmailService.php`
- Tous les fichiers du projet (recherche exhaustive)

