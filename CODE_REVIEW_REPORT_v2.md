# Rapport d'Analyse de Code Exhaustive (v2)

> **Projet**: `jsadaa/php-core-library` | **Date**: 1er Février 2026

## 1. Résumé Exécutif

L'analyse approfondie du code source a révélé une base solide inspirée de Rust, avec une architecture modulaire et un typage strict. Cependant, plusieurs **bugs critiques** dans les modules en cours de développement (`Process`, `Char`) et des **problèmes de performance** structurels dans les `Collections` rendent la bibliothèque inutilisable en production dans son état actuel "v0.1-candidate".

Les tests unitaires sont actuellement en échec (5 erreurs fatales) bloquant toute intégration continue.

---

## 2. Anomalies Critiques (Must Fix)

Ces anomalies provoquent des *Fatal Errors* ou des comportements diamétralement opposés à ceux attendus.

### 🔴 2.1 Bug Logique : Inversion de Condition dans `Process`

**Fichier** : `src/Modules/Process/Process.php` (Ligne 214)

La méthode `readStderr` retourne une erreur si stderr est... présent !

```php
// ACTUEL (BUG)
if ($stderr->isSome()) { // <--- Si stderr existe, on retourne une erreur !
    return Result::err("Failed to get stderr");
}

// CORRECTIONREQUISE
if ($stderr->isNone()) {
    return Result::err("Failed to get stderr");
}
```

### 🔴 2.2 Méthode Vide : `Char::isAscii`

**Fichier** : `src/Primitives/Char/Char.php` (Lignes 84-87)

La méthode est déclarée mais n'a pas de corps, ce qui ne retournera rien (void) alors qu'un booléen est attendu.

```php
// ACTUEL
public function isAscii(): bool
{
    
}

// CORRECTION REQUISE
public function isAscii(): bool
{
    return \mb_ord($this->value) < 128;
}
```

### 🔴 2.3 Violation d'Encapsulation : Accès à Propriété Privée

**Fichier** : `src/Modules/Process/Command.php` (Ligne 237)

La classe `Command` tente d'accéder directement à la propriété `$streams` de `ProcessBuilder`, qui est définie comme `private`.

```php
$currentStreams = $builder->streams ?? ProcessStreams::defaults(); 
// Fatal Error: Cannot access private property
```

**Correction** : Ajouter un accesseur `getStreams()` public à `ProcessBuilder`.

### 🔴 2.4 Confusion de Type : `Option` vs `Result`

**Fichier** : `src/Modules/Process/Command.php` (Ligne 226)

Appel de la méthode `isOk()` (propre à `Result`) sur un objet `Option`.

```php
if ($stdoutResult->isOk()) { // ❌ Call to undefined method Option::isOk()
```

**Correction** : Remplacer par `$stdoutResult->isSome()`.

---

## 3. Problèmes d'Architecture & Performance (High Severity)

### 🟠 3.1 Complexité Algorithmique des Collections

- **Problème** : `Map` et `Set` sont implémentés comme des wrappers autour de `Sequence` (itératif).
- **Impact** : Toutes les opérations de recherche (`get`, `contains`, `add`) sont en **O(n)**. Pour une librairie "Core", une `Map` doit être en **O(1)** (Table de hachage).
- **Conséquence** : Inutilisable pour de grands jeux de données.

### 🟠 3.2 Régression des Tests Unitaires (`Str` vs `Char`)

5 tests échouent avec `TypeError`. Une modification récente dans `Str` ou `Sequence` fait qu'un objet `Char` est passé là où un `Str` est attendu (ou inversement).

Exemple : `Argument #1 ($char) must be of type ...\Str, ...\Char given`.

### 🟠 3.3 "Busy Waiting" dans `Process::wait`

L'implémentation actuelle utilise une boucle `while` avec `usleep(10000)` (10ms).

- **Impact** : Consommation CPU inutile et latence minimale imposée de 10ms.
- **Recommandation** : Utiliser `stream_select()` sur les pipes ou `pcntl_wait` si disponible pour une attente événementielle.

---

## 4. Recommandations pour le Plan d'Implémentation

### Phase 1 : Correctifs Immédiats (Hotfix)

Refactorer les modules `Process` et `Char` pour corriger les 4 bugs critiques identifiés ci-dessus. C'est un pré-requis absolu à toute utilisation.

### Phase 2 : Réparation des Tests

Investiguer et corriger l'incompatibilité de type entre `Str` et `Char` pour remettre la CI au vert.

### Phase 3 : Optimisation

Réécrire le coeur de `Map` et `Set` pour utiliser les arrays PHP natifs (Hash Tables) pour les clés scalaires, garantissant des performances O(1).
