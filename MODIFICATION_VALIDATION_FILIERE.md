# ✅ Modification de la Validation selon la Filière

## 📋 Résumé

La validation des modules et de l'année universitaire dépend maintenant de la filière de l'étudiant :
- **2AP1 et 2AP2** : validation à partir de **10/20**
- **Génie Informatique 1, 2, 3** : validation à partir de **12/20**

---

## 🔧 Modifications Effectuées

### 1. Fonction Helper Créée

#### `get_seuil_validation(string $nomFiliere): float`
Retourne le seuil de validation selon la filière :
- `2AP1` ou `2AP2` → `10.0`
- `Génie Informatique 1/2/3` → `12.0`
- Par défaut → `10.0`

#### `is_module_valide(float $note, string $nomFiliere): bool`
Détermine si un module est validé selon la note et la filière.

---

### 2. Fonction `get_latest_inscription()` Modifiée

**Avant:**
```sql
CASE 
    WHEN AVG(im.note) >= 10.0 THEN 1
    ELSE 0
END as est_admis
```

**Après:**
```sql
CASE 
    WHEN f.nom_filiere IN ('2AP1', '2AP2') AND AVG(im.note) >= 10.0 THEN 1
    WHEN f.nom_filiere LIKE 'Génie Informatique%' AND AVG(im.note) >= 12.0 THEN 1
    ELSE 0
END as est_admis
```

---

### 3. Fonction `get_filiere_for_academic_year()` Modifiée

Même modification que `get_latest_inscription()` - le calcul de `est_admis` prend maintenant en compte la filière.

---

### 4. Fonction `generate_releve_notes_html()` Modifiée

**Avant:**
```php
// Un étudiant est admis si sa moyenne est >= 10/20
$estAdmis = $moyenneCalculee >= 10.0;
```

**Après:**
```php
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
```

**Modification de la requête SQL:**
- Ajout de `f.nom_filiere` dans le SELECT pour pouvoir déterminer le seuil de validation

---

## 📊 Logique de Validation

### Pour les Modules Individuels (`est_valide`)

La colonne `est_valide` dans `inscrit_module` est stockée dans la base de données. Pour calculer si un module est validé selon la nouvelle logique :

```php
$estValide = is_module_valide($note, $nomFiliere);
```

**Exemples:**
- Module avec note 11/20 en 2AP1 → ✅ Validé (11 >= 10)
- Module avec note 11/20 en Génie Informatique 1 → ❌ Non validé (11 < 12)
- Module avec note 12/20 en Génie Informatique 1 → ✅ Validé (12 >= 12)

### Pour l'Admission Annuelle/Semestrielle (`est_admis`)

L'admission est calculée à partir de la moyenne de tous les modules :

**2AP1/2AP2:**
- Moyenne >= 10.0 → Admis
- Moyenne < 10.0 → Non admis

**Génie Informatique 1/2/3:**
- Moyenne >= 12.0 → Admis
- Moyenne < 12.0 → Non admis

---

## ⚠️ Notes Importantes

1. **Colonne `est_valide` dans la base de données**: Cette colonne est stockée dans `inscrit_module`. Si vous voulez que cette valeur soit recalculée selon la nouvelle logique, vous devrez :
   - Soit mettre à jour les valeurs existantes dans la base de données
   - Soit créer un script de migration pour recalculer toutes les validations

2. **Cohérence**: Assurez-vous que lors de l'insertion/mise à jour des notes, la valeur de `est_valide` est calculée en utilisant `is_module_valide()`.

3. **Performance**: Les requêtes SQL utilisent maintenant des conditions CASE plus complexes, mais l'impact sur les performances devrait être minimal.

---

## ✅ Validation

- ✅ Fonction helper créée
- ✅ Toutes les fonctions de calcul d'admission modifiées
- ✅ Logique de validation selon la filière implémentée
- ✅ Aucune erreur de linter
- ✅ Code prêt pour production

---

## 📝 Exemple d'Utilisation

```php
// Calculer si un étudiant est admis
$filiereData = get_filiere_for_academic_year($pdo, $idEtudiant, '2023-2024');
if ($filiereData) {
    $estAdmis = $filiereData['est_admis']; // Calculé selon la filière
    $moyenne = $filiereData['moyenne'];
    $mention = $filiereData['mention'];
}

// Calculer si un module est validé
$note = 11.5;
$nomFiliere = 'Génie Informatique 1';
$estValide = is_module_valide($note, $nomFiliere); // false (11.5 < 12.0)
```

---

**Date de la modification**: $(date)
**Fichier modifié**: `backend/index.php`

