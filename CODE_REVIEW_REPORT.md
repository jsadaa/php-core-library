# PHP Core Library - Rapport d'Analyse Exhaustive

> **Date**: 2026-02-01 | **Reviewer**: Code Review Agent | **Projet**: `jsadaa/php-core-library`

## Résumé Exécutif

Ce projet est une bibliothèque PHP inspirée de Rust, fournissant des types type-safe et immutables (Sequence, Option, Result, Str, Integer, Double) ainsi que des modules utilitaires (FileSystem, Path, Time, Process, Json). L'architecture est solide et cohérente, avec une bonne couverture de tests, mais plusieurs problèmes critiques doivent être résolus avant une v0.1.

| Métrique | Valeur |
|----------|--------|
| Lignes de code (src/) | ~7500+ |
| Tests | 859 total |
| Tests en erreur | 5 |
| Tests skipped | 2 |
| Erreurs Psalm | 16 |
| Modules complets | 6 (Option, Result, Sequence, FileSystem, Path, Time) |
| Modules en cours | 3 (Process, Json, Char) |

---

## Problèmes Identifiés

### 🔴 CRITIQUES (Must Fix)

#### 1. Bug logique dans `Process::readStderr()` - Condition inversée

**Fichier**: `src/Modules/Process/Process.php:214`

```php
// ACTUEL (BUG - ligne 214)
if ($stderr->isSome()) {  // ❌ Devrait être isNone()
    return Result::err("Failed to get stderr");
}

// CORRIGÉ
if ($stderr->isNone()) {  // ✅
    return Result::err("Failed to get stderr");
}
```

#### 2. Méthode incomplète `Char::isAscii()`

**Fichier**: `src/Primitives/Char/Char.php:84-87`

```php
public function isAscii(): bool
{
    // Corps vide - ne retourne rien!
}
```

**Fix suggéré**:

```php
public function isAscii(): bool
{
    return ord($this->value) < 128;
}
```

#### 3. Accès à propriété privée depuis une autre classe

**Fichier**: `src/Modules/Process/Command.php:237`

```php
$currentStreams = $builder->streams ?? ProcessStreams::defaults();
// ❌ ProcessBuilder::$streams est private
```

**Fix**: Ajouter un getter `getStreams()` dans `ProcessBuilder`.

#### 4. Méthode `Option::isOk()` appelée mais inexistante

**Fichier**: `src/Modules/Process/Command.php:226`

```php
if ($stdoutResult->isOk()) {  // ❌ Option n'a pas isOk()
```

**Fix**: Remplacer par `isSome()`.

---

### 🟡 HIGH (Should Fix)

#### 5. Annotation PHPDoc incorrecte sur `Process::stdin/stdout/stderr()`

**Fichier**: `src/Modules/Process/Process.php:133-154`

```php
/**
 * @return Option<resource, string>  // ❌ Option n'a qu'un seul type param
 */
public function stdin(): Option
```

**Fix**: `@return Option<resource>`

#### 6. Signature `Sequence::get()` incohérente

**Fichier**: `src/Modules/Collections/Sequence/Sequence.php:266`

Le code tente de convertir un `Integer` mais le type hint n'accepte que `int`:

```php
$index = $index instanceof Integer ? $index->toInt() : $index;
// ❌ TypeDoesNotContainType - $index est déjà int selon le type hint
```

> **Note**: Tu as corrigé le PHPDoc mais le type hint dans la signature doit aussi être `int|Integer`.

#### 7. Tests en échec (5 erreurs)

Les tests PHPUnit échouent - probablement liés aux modules Process/Json en cours de développement. À investiguer et corriger.

#### 8. Map n'est pas une vraie HashMap

**Fichier**: `src/Modules/Collections/Map/Map.php:12-14`

```php
// Ce type is not a real Hash Map, but a Sequence of Pairs for now,
// so expect performance issues.
```

Complexité O(n) pour les opérations de recherche au lieu de O(1).

---

### 🟢 MEDIUM (Nice to Fix)

#### 9. Annotations Psalm génériques manquantes dans `Map::flatMap()`

Types `mixed` inférés au lieu de types génériques.

#### 10. `Char` manque l'annotation `@psalm-immutable`

Contrairement aux autres Primitives.

#### 11. Incohérence de nommage: `size()` vs `len()`

- `Sequence::size()` retourne `Integer`
- Certains tests référencent `testLenOn*`

#### 12. `declare(strict_types=1)` - inconsistance de formatage

Certains fichiers utilisent `= 1` avec espaces, d'autres sans.

#### 13. Documentation manquante pour plusieurs modules

Process, Json, Map, Set, Char ne sont pas documentés dans le README ou dans `/docs`.

---

## Points Forts ✅

| Aspect | Évaluation |
|--------|------------|
| **Architecture** | Excellente séparation Modules/Primitives, cohérence des patterns |
| **Immutabilité** | Appliquée systématiquement avec `readonly`, patterns fonctionnels |
| **Error Handling** | Utilisation cohérente de `Result<T, E>` et `Option<T>` |
| **Documentation code** | PHPDoc génériques bien utilisés (`@template`, `@psalm-immutable`) |
| **Tests** | 859 tests, structure Unit/Functional, couverture extensive |
| **Static Analysis** | Configuration Psalm en place, plupart du code passe |
| **API Design** | Inspirée de Rust, intuitive, fluent interface |
| **Type Safety** | Enforcement via static analysis, pas de runtime overhead |

---

## Points Faibles ❌

| Aspect | Problème |
|--------|----------|
| **Modules incomplets** | Process, Json, Char non terminés |
| **Performance Map** | O(n) au lieu de O(1) pour HashMap |
| **Tests Process/Json** | Aucun test pour ces modules |
| **Code non-commité** | 8 fichiers Process ajoutés, 5 modifiés |
| **Erreurs Psalm** | 16 erreurs dont certaines critiques |
| **Documentation externe** | Modules récents non documentés |

---

## Anti-Patterns Détectés

### 1. Violation d'encapsulation

Tentative d'accès à une propriété `private` depuis une autre classe (`Command` accède à `ProcessBuilder::$streams`).

### 2. Méthode vide

`Char::isAscii()` est déclarée mais son corps est vide - erreur de compilation potentielle en strict mode.

### 3. Condition inversée

Bug classique où la condition est l'opposé de l'intention (`isSome` au lieu de `isNone`).

### 4. Confusion Option/Result

Appel de `isOk()` sur un `Option` suggère une confusion entre les deux types monoids.

### 5. Performance O(n) pour structure indexée

Le `Map` devrait utiliser un hash pour les clés plutôt qu'une recherche linéaire.

---

## Fichiers Non-Commités (Analyse)

### Nouveaux fichiers (non trackés)

| Fichier | Status |
|---------|--------|
| `src/Modules/Process/FileDescriptor.php` | OK |
| `src/Modules/Process/Process.php` | **1 bug critique** |
| `src/Modules/Process/ProcessBuilder.php` | OK mais manque getter |
| `src/Modules/Process/ProcessStreams.php` | OK |
| `src/Modules/Process/StreamDescriptor.php` | OK |
| `src/Modules/Process/StreamReader.php` | OK |
| `src/Modules/Process/StreamType.php` | OK |
| `src/Modules/Process/StreamWriter.php` | OK |
| `src/Primitives/Char/Char.php` | **Méthode incomplète** |

### Fichiers modifiés

| Fichier | Changements |
|---------|-------------|
| `src/Modules/Collections/Map/Map.php` | Modifications mineures |
| `src/Modules/Collections/Sequence/Sequence.php` | Modifications mineures |
| `src/Modules/Json/Json.php` | Nouveau module JSON (~100 lignes) |
| `src/Modules/Process/Command.php` | **4 bugs/erreurs Psalm** |
| `src/Primitives/Str/Str.php` | Modifications |

---

## Recommandations pour v0.1

### Phase 1: Corrections Critiques (Priorité 1)

1. [ ] Corriger `Process::readStderr()` - inverser la condition
2. [ ] Compléter `Char::isAscii()`
3. [ ] Ajouter getter `ProcessBuilder::getStreams()`
4. [ ] Remplacer `isOk()` par `isSome()` dans `Command.php`
5. [ ] Corriger les annotations PHPDoc de `Process::stdin/stdout/stderr()`
6. [ ] Fixer les 5 tests en échec

### Phase 2: Stabilisation (Priorité 2)

1. [ ] Résoudre les 16 erreurs Psalm
2. [ ] Ajouter tests unitaires pour Json et Process
3. [ ] Harmoniser le type hint de `Sequence::get()` (accepter `int|Integer`)
4. [ ] Ajouter `@psalm-immutable` à `Char`

### Phase 3: Documentation & Polish (Priorité 3)

1. [ ] Documenter Process module dans README
2. [ ] Documenter Json module dans README
3. [ ] Créer `/docs/process.md` et `/docs/json.md`
4. [ ] Harmoniser le formatage `declare(strict_types=1)`

### Phase 4: Performance (Future)

1. [ ] Implémenter une vraie HashMap avec SplObjectStorage ou array hashé
2. [ ] Benchmarker les collections avec de gros datasets

---

## Conclusion

Le projet démontre une excellente compréhension des patterns Rust adaptés à PHP. L'architecture est solide, la couverture de tests est bonne, et l'API est cohérente. Les problèmes identifiés sont principalement dans le code en cours de développement (Process, Char) et sont facilement corrigeables.

**Estimation pour v0.1-ready**: 4-6 heures de travail pour les corrections critiques et la stabilisation.

> [!IMPORTANT]
> Les 4 premiers items de la Phase 1 sont bloquants pour toute utilisation du module Process.
