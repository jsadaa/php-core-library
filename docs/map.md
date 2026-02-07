# Map

Map is an immutable, homogeneous collection of key-value pairs where keys are unique. All values must be of the same type `V`. It provides O(1) lookups for both scalar and object keys through a dual-storage architecture. Type safety is enforced via static analysis only (no runtime type checking).

Maps are particularly useful when you need to:
- Associate values with unique keys
- Perform fast lookups by key
- Maintain a dictionary-like structure with functional operations
- Use objects as keys with identity-based comparison

> [!NOTE]
> Object keys are compared by identity using `spl_object_id()`. The Map holds strong references to object keys, preventing garbage collection and ensuring ID stability.

## Table of Contents

- [Creation](#creation)
- [Basic Operations](#basic-operations)
- [Inspection](#inspection)
- [Element Access](#element-access)
- [Transformation](#transformation)
- [Composition](#composition)
- [Iteration](#iteration)
- [Aggregation](#aggregation)
- [Conversion](#conversion)
- [Type Safety](#type-safety)

## Creation

### Single Entry

Creates a new Map with a single key-value pair.

```php
$map = Map::of('host', 'localhost');
$map = Map::of(42, 'answer');
```

### From Keys

Creates a new Map from a list of keys, assigning the same value to each.

```php
$keys = ['read', 'write', 'execute'];
$permissions = Map::fromKeys($keys, false); // All permissions disabled
```

### Empty Map

Creates a new empty Map.

```php
$map = Map::new();
```

## Basic Operations

### Size

Gets the number of key-value pairs in the Map.

```php
$map = Map::of('a', 1)->add('b', 2)->add('c', 3);
$size = $map->size(); // Integer::of(3)
```

### Is Empty

Checks if the Map contains no elements.

```php
$map = Map::new();
$isEmpty = $map->isEmpty(); // true

$map = Map::of('key', 'value');
$isEmpty = $map->isEmpty(); // false
```

### Equality

Checks if two Maps contain the same key-value pairs. Uses strict equality (`===`) for both keys and values.

```php
$map1 = Map::of('a', 1)->add('b', 2);
$map2 = Map::of('b', 2)->add('a', 1);
$areEqual = $map1->eq($map2); // true (order doesn't matter)

$map3 = Map::of('a', 1)->add('b', 99);
$areEqual = $map1->eq($map3); // false
```

### Clear

Returns a new empty Map.

```php
$map = Map::of('a', 1)->add('b', 2);
$empty = $map->clear();
$empty->isEmpty(); // true
```

## Inspection

### Contains Key

Checks if the Map contains a given key.

```php
$scores = Map::of('Alice', 95)->add('Bob', 78);
$hasAlice = $scores->containsKey('Alice'); // true
$hasEve = $scores->containsKey('Eve');     // false
```

### Contains Value

Checks if the Map contains a given value.

```php
$scores = Map::of('Alice', 95)->add('Bob', 78);
$has95 = $scores->containsValue(95); // true
$has50 = $scores->containsValue(50); // false
```

## Element Access

### Get

Gets the value associated with a key. Returns an `Option` to handle the case where the key might not exist.

```php
$scores = Map::of('Alice', 95)
    ->add('Bob', 78)
    ->add('Charlie', 92);

$alice = $scores->get('Alice');   // Option::some(95)
$missing = $scores->get('Eve');   // Option::none()

// Safe extraction with default
$bob = $scores->get('Bob')->unwrapOr(0);   // 78
$eve = $scores->get('Eve')->unwrapOr(0);   // 0

// Pattern matching
$scores->get('Charlie')->match(
    fn($score) => "Charlie scored: $score",
    fn() => "Charlie not found"
);
```

### Add

Adds a key-value pair to the Map. If the key already exists, the value is replaced.

```php
$scores = Map::new()
    ->add('Alice', 95)
    ->add('Bob', 78);

// Overwrite existing key
$scores = $scores->add('Bob', 82); // Bob's score is now 82
```

### Remove

Removes a key-value pair from the Map. Returns the Map unchanged if the key doesn't exist.

```php
$map = Map::of('a', 1)->add('b', 2)->add('c', 3);
$smaller = $map->remove('b');
$smaller->size(); // Integer::of(2)
$smaller->containsKey('b'); // false
```

### Find

Finds the first key-value pair that satisfies a predicate. Returns an `Option<Pair<K, V>>`.

```php
$scores = Map::of('Alice', 95)
    ->add('Bob', 78)
    ->add('Charlie', 92);

$topScorer = $scores->find(fn($name, $score) => $score > 90);
// Option::some(Pair::of('Alice', 95))

$failing = $scores->find(fn($name, $score) => $score < 50);
// Option::none()

// Extract the pair
$scores->find(fn($name, $score) => $score > 90)
    ->match(
        fn($pair) => "Top scorer: " . $pair->key() . " (" . $pair->value() . ")",
        fn() => "No top scorers found"
    );
```

## Transformation

### Map

Transforms all values using a mapper function, preserving keys.

```php
$prices = Map::of('apple', 1.50)
    ->add('banana', 0.75)
    ->add('cherry', 3.00);

// Apply 10% discount
$discounted = $prices->map(fn($fruit, $price) => $price * 0.9);
// Map { 'apple' => 1.35, 'banana' => 0.675, 'cherry' => 2.7 }
```

### Filter

Filters key-value pairs based on a predicate.

```php
$scores = Map::of('Alice', 95)
    ->add('Bob', 78)
    ->add('Charlie', 92)
    ->add('Diana', 65);

$passing = $scores->filter(fn($name, $score) => $score >= 80);
// Map { 'Alice' => 95, 'Charlie' => 92 }
```

### FlatMap

Maps each key-value pair to a Map and flattens the results.

```php
$scores = Map::of('Alice', 95)
    ->add('Bob', 78);

$expanded = $scores->flatMap(fn($name, $score) =>
    Map::of($name . '_best', $score)
        ->add($name . '_avg', intdiv($score, 2))
);
// Map { 'Alice_best' => 95, 'Alice_avg' => 47, 'Bob_best' => 78, 'Bob_avg' => 39 }
```

## Composition

### Append

Merges all key-value pairs from another Map. If both Maps share keys, the values from the other Map take precedence.

```php
$defaults = Map::of('Alice', 90)
    ->add('Bob', 75)
    ->add('Charlie', 80);

$updates = Map::of('Bob', 82)
    ->add('Diana', 95);

$merged = $defaults->append($updates);
// Map { 'Alice' => 90, 'Bob' => 82, 'Charlie' => 80, 'Diana' => 95 }
```

### Keys

Returns a `Set` of all keys in the Map.

```php
$map = Map::of('a', 1)->add('b', 2)->add('c', 3);
$keys = $map->keys(); // Set { 'a', 'b', 'c' }
```

### Values

Returns a `Sequence` of all values in the Map.

```php
$map = Map::of('a', 1)->add('b', 2)->add('c', 3);
$values = $map->values(); // Sequence [1, 2, 3]
```

## Iteration

### For Each

Applies a callback to each key-value pair. Intended for side effects.

```php
$scores = Map::of('Alice', 95)
    ->add('Bob', 78);

$scores->forEach(fn($name, $score) => echo "$name: $score\n");
// Outputs:
// Alice: 95
// Bob: 78
```

## Aggregation

### Fold

Reduces the Map to a single value using an accumulator function.

```php
$scores = Map::of('Alice', 95)
    ->add('Bob', 78)
    ->add('Charlie', 92);

// Sum all scores
$total = $scores->fold(
    fn($acc, $name, $score) => $acc + $score,
    0
); // 265

// Build a summary string
$summary = $scores->fold(
    fn($acc, $name, $score) => $acc . "$name: $score\n",
    ""
);
```

## Conversion

### To Array

Converts the Map to an array of `[key, value]` pairs.

```php
$map = Map::of('a', 1)->add('b', 2);
$array = $map->toArray(); // [['a', 1], ['b', 2]]
```

## Object Keys

Map supports objects as keys, using identity comparison (same object instance).

```php
$user1 = new User('Alice');
$user2 = new User('Bob');

$roles = Map::of($user1, 'admin')
    ->add($user2, 'editor');

$roles->get($user1)->unwrap(); // 'admin'
$roles->containsKey($user2);   // true

// Different object, same data -- not found
$user3 = new User('Alice');
$roles->containsKey($user3);   // false (different instance)
```

## Type Safety

Type safety in Map is enforced through static analysis tools like Psalm. Both key and value types are tracked via generics.

```php
// Psalm infers Map<string, int>
$map = Map::of('a', 1)->add('b', 2);

// This will fail static analysis: wrong value type
// $map->add('c', 'three');

// Psalm infers Map<string, string>
$config = Map::of('host', 'localhost')->add('port', '8080');
```

Remember that Map is immutable: every operation returns a new Map instance rather than modifying the original.
