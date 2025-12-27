# Requêtes SQL pour Calcul Dynamique

Ce document contient les requêtes SQL pour calculer dynamiquement la moyenne, la mention et le statut d'admission à partir des notes stockées dans `inscrit_module`.

---

## 1. Calcul de la Moyenne pour un Semestre

### Pour un étudiant, un semestre et une année universitaire donnés:

```sql
SELECT 
    im.id_etudiant,
    mf.semestre,
    au.id_annee,
    CONCAT(au.annee_debut, '-', au.annee_fin) as annee_universitaire,
    AVG(im.note) as moyenne,
    COUNT(im.id_module) as nombre_modules,
    SUM(im.note) as somme_notes
FROM inscrit_module im
JOIN module_filiere mf ON mf.id_module = im.id_module
JOIN inscription_etudiant ie ON ie.id_etudiant = im.id_etudiant 
    AND ie.id_filiere = mf.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
WHERE im.id_etudiant = :id_etudiant
    AND mf.semestre = :semestre
    AND au.id_annee = :id_annee
    AND im.note IS NOT NULL
GROUP BY im.id_etudiant, mf.semestre, au.id_annee;
```

**Exemple d'utilisation:**
```sql
-- Calculer la moyenne de l'étudiant E001 pour le semestre 1 (S1) de l'année 2023-2024
SELECT 
    AVG(im.note) as moyenne
FROM inscrit_module im
JOIN module_filiere mf ON mf.id_module = im.id_module
JOIN inscription_etudiant ie ON ie.id_etudiant = im.id_etudiant 
    AND ie.id_filiere = mf.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
WHERE im.id_etudiant = 'E001'
    AND mf.semestre = 1
    AND au.id_annee = 'AN2023'
    AND im.note IS NOT NULL;
```

---

## 2. Calcul de la Mention à partir de la Moyenne

### Fonction SQL pour calculer la mention:

```sql
SELECT 
    moyenne,
    CASE 
        WHEN moyenne >= 16.0 THEN 'Très Bien'
        WHEN moyenne >= 14.0 THEN 'Bien'
        WHEN moyenne >= 12.0 THEN 'Assez Bien'
        WHEN moyenne >= 10.0 THEN 'Passable'
        ELSE 'Insuffisant'
    END as mention
FROM (
    SELECT AVG(im.note) as moyenne
    FROM inscrit_module im
    JOIN module_filiere mf ON mf.id_module = im.id_module
    JOIN inscription_etudiant ie ON ie.id_etudiant = im.id_etudiant 
        AND ie.id_filiere = mf.id_filiere
    JOIN annee_universitaire au ON au.id_annee = ie.id_annee
    WHERE im.id_etudiant = :id_etudiant
        AND mf.semestre = :semestre
        AND au.id_annee = :id_annee
        AND im.note IS NOT NULL
) as calc_moyenne;
```

---

## 3. Calcul du Statut d'Admission

### Un étudiant est admis si sa moyenne >= 10.0:

```sql
SELECT 
    moyenne,
    CASE 
        WHEN moyenne >= 10.0 THEN 1
        ELSE 0
    END as est_admis
FROM (
    SELECT AVG(im.note) as moyenne
    FROM inscrit_module im
    JOIN module_filiere mf ON mf.id_module = im.id_module
    JOIN inscription_etudiant ie ON ie.id_etudiant = im.id_etudiant 
        AND ie.id_filiere = mf.id_filiere
    JOIN annee_universitaire au ON au.id_annee = ie.id_annee
    WHERE im.id_etudiant = :id_etudiant
        AND mf.semestre = :semestre
        AND au.id_annee = :id_annee
        AND im.note IS NOT NULL
) as calc_moyenne;
```

---

## 4. Requête Complète: Moyenne + Mention + Statut pour un Semestre

```sql
SELECT 
    im.id_etudiant,
    mf.semestre,
    au.id_annee,
    CONCAT(au.annee_debut, '-', au.annee_fin) as annee_universitaire,
    AVG(im.note) as moyenne,
    CASE 
        WHEN AVG(im.note) >= 16.0 THEN 'Très Bien'
        WHEN AVG(im.note) >= 14.0 THEN 'Bien'
        WHEN AVG(im.note) >= 12.0 THEN 'Assez Bien'
        WHEN AVG(im.note) >= 10.0 THEN 'Passable'
        ELSE 'Insuffisant'
    END as mention,
    CASE 
        WHEN AVG(im.note) >= 10.0 THEN 1
        ELSE 0
    END as est_admis,
    COUNT(im.id_module) as nombre_modules
FROM inscrit_module im
JOIN module_filiere mf ON mf.id_module = im.id_module
JOIN inscription_etudiant ie ON ie.id_etudiant = im.id_etudiant 
    AND ie.id_filiere = mf.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
WHERE im.id_etudiant = :id_etudiant
    AND mf.semestre = :semestre
    AND au.id_annee = :id_annee
    AND im.note IS NOT NULL
GROUP BY im.id_etudiant, mf.semestre, au.id_annee;
```

---

## 5. Calcul pour une Année Universitaire Complète (Tous les Semestres)

### Moyenne générale pour une année universitaire:

```sql
SELECT 
    im.id_etudiant,
    au.id_annee,
    CONCAT(au.annee_debut, '-', au.annee_fin) as annee_universitaire,
    AVG(im.note) as moyenne_annuelle,
    CASE 
        WHEN AVG(im.note) >= 16.0 THEN 'Très Bien'
        WHEN AVG(im.note) >= 14.0 THEN 'Bien'
        WHEN AVG(im.note) >= 12.0 THEN 'Assez Bien'
        WHEN AVG(im.note) >= 10.0 THEN 'Passable'
        ELSE 'Insuffisant'
    END as mention,
    CASE 
        WHEN AVG(im.note) >= 10.0 THEN 1
        ELSE 0
    END as est_admis,
    COUNT(im.id_module) as nombre_modules_total
FROM inscrit_module im
JOIN module_filiere mf ON mf.id_module = im.id_module
JOIN inscription_etudiant ie ON ie.id_etudiant = im.id_etudiant 
    AND ie.id_filiere = mf.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
WHERE im.id_etudiant = :id_etudiant
    AND au.id_annee = :id_annee
    AND im.note IS NOT NULL
GROUP BY im.id_etudiant, au.id_annee;
```

---

## 6. Fonction Helper pour Récupérer les Informations d'Inscription avec Calcul Dynamique

### Remplacement de `get_latest_inscription()`:

```sql
SELECT 
    f.nom_filiere AS filiere_nom,
    f.id_filiere AS filiere_id,
    au.annee_debut,
    au.annee_fin,
    au.id_annee,
    -- Calcul dynamique de la moyenne pour l'année complète
    (SELECT AVG(im2.note)
     FROM inscrit_module im2
     JOIN module_filiere mf2 ON mf2.id_module = im2.id_module
     WHERE im2.id_etudiant = ie.id_etudiant
         AND mf2.id_filiere = ie.id_filiere
         AND im2.note IS NOT NULL
    ) as moyenne,
    -- Calcul dynamique de la mention
    CASE 
        WHEN (SELECT AVG(im2.note)
              FROM inscrit_module im2
              JOIN module_filiere mf2 ON mf2.id_module = im2.id_module
              WHERE im2.id_etudiant = ie.id_etudiant
                  AND mf2.id_filiere = ie.id_filiere
                  AND im2.note IS NOT NULL) >= 16.0 THEN 'Très Bien'
        WHEN (SELECT AVG(im2.note)
              FROM inscrit_module im2
              JOIN module_filiere mf2 ON mf2.id_module = im2.id_module
              WHERE im2.id_etudiant = ie.id_etudiant
                  AND mf2.id_filiere = ie.id_filiere
                  AND im2.note IS NOT NULL) >= 14.0 THEN 'Bien'
        WHEN (SELECT AVG(im2.note)
              FROM inscrit_module im2
              JOIN module_filiere mf2 ON mf2.id_module = im2.id_module
              WHERE im2.id_etudiant = ie.id_etudiant
                  AND mf2.id_filiere = ie.id_filiere
                  AND im2.note IS NOT NULL) >= 12.0 THEN 'Assez Bien'
        WHEN (SELECT AVG(im2.note)
              FROM inscrit_module im2
              JOIN module_filiere mf2 ON mf2.id_module = im2.id_module
              WHERE im2.id_etudiant = ie.id_etudiant
                  AND mf2.id_filiere = ie.id_filiere
                  AND im2.note IS NOT NULL) >= 10.0 THEN 'Passable'
        ELSE 'Insuffisant'
    END as mention,
    -- Calcul dynamique du statut d'admission
    CASE 
        WHEN (SELECT AVG(im2.note)
              FROM inscrit_module im2
              JOIN module_filiere mf2 ON mf2.id_module = im2.id_module
              WHERE im2.id_etudiant = ie.id_etudiant
                  AND mf2.id_filiere = ie.id_filiere
                  AND im2.note IS NOT NULL) >= 10.0 THEN 1
        ELSE 0
    END as est_admis
FROM inscription_etudiant ie
JOIN filiere f ON f.id_filiere = ie.id_filiere
JOIN annee_universitaire au ON au.id_annee = ie.id_annee
WHERE ie.id_etudiant = :id_etudiant
ORDER BY au.annee_debut DESC
LIMIT 1;
```

**Note:** Cette requête utilise des sous-requêtes qui peuvent être lentes. Pour de meilleures performances, utilisez une approche avec JOIN et GROUP BY (voir requête #5).

---

## 7. Version Optimisée avec JOIN (Recommandée)

```sql
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
        WHEN AVG(im.note) >= 10.0 THEN 1
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
LIMIT 1;
```

---

## Notes d'Implémentation

1. **Performance**: Les requêtes avec JOIN sont généralement plus rapides que les sous-requêtes corrélées.

2. **Gestion des NULL**: Utilisez `COALESCE()` ou `IFNULL()` si vous voulez une valeur par défaut quand il n'y a pas de notes:
   ```sql
   COALESCE(AVG(im.note), 0) as moyenne
   ```

3. **Précision**: Pour la moyenne, utilisez `ROUND()` ou `FORMAT()` selon vos besoins:
   ```sql
   ROUND(AVG(im.note), 2) as moyenne  -- 2 décimales
   ```

4. **Validation**: Assurez-vous que `im.note IS NOT NULL` pour éviter d'inclure les modules sans notes dans le calcul.

