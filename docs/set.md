# Set

Set is an immutable collection of unique values that provides mathematical set operations and functional programming methods. It is backed by a `Map<T, bool>` internally, inheriting its O(1) lookup performance. Type safety is enforced via static analysis only (no runtime type checking).

Sets are particularly useful when you need to:
- Maintain a collection of unique elements
- Perform set operations (union, intersection, difference)
- Check membership efficiently
- Apply functional transformations while preserving uniqueness

## Table of Contents

- [Creation](#creation)
- [Basic Operations](#basic-operations)
- [Inspection](#inspection)
- [Set Operations](#set-operations)
- [Transformation](#transformation)
- [Searching and Filtering](#searching-and-filtering)
- [Iteration](#iteration)
- [Aggregation](#aggregation)
- [Conversion](#conversion)
- [Type Safety](#type-safety)

## Creation

### From Elements

Creates a new Set from individual elements. Duplicates are automatically removed.

```php
$set = Set::of(1, 2, 3, 4, 5);
$set = Set::of('apple', 'banana', 'cherry');

// Duplicates are ignored
$set = Set::of(1, 2, 2, 3, 3, 3);
$set->size(); // Integer::of(3)
```

### From Array

Creates a new Set from an existing array.

```php
$array = [1, 2, 3, 2, 1];
$set = Set::ofArray($array);
$set->size(); // Integer::of(3)
```

## Basic Operations

### Size

Gets the number of elements in the Set.

```php
$set = Set::of(1, 2, 3);
$size = $set->size(); // Integer::of(3)
```

### Is Empty

Checks if the Set contains no elements.

```php
$set = Set::of(1, 2, 3);
$isEmpty = $set->isEmpty(); // false

$empty = Set::of(1)->remove(1);
$isEmpty = $empty->isEmpty(); // true
```

### Equality

Checks if two Sets contain exactly the same elements.

```php
$set1 = Set::of(1, 2, 3);
$set2 = Set::of(3, 1, 2);
$areEqual = $set1->eq($set2); // true (order doesn't matter)

$set3 = Set::of(1, 2, 4);
$areEqual = $set1->eq($set3); // false
```

### Clear

Returns a new empty Set.

```php
$set = Set::of(1, 2, 3);
$empty = $set->clear();
$empty->isEmpty(); // true
```

## Inspection

### Contains

Checks if the Set contains a given element.

```php
$languages = Set::of('PHP', 'Rust', 'Go');
$hasPHP = $languages->contains('PHP');   // true
$hasJava = $languages->contains('Java'); // false
```

### Any

Checks if any element satisfies a predicate.

```php
$numbers = Set::of(1, 3, 5, 7);
$hasEven = $numbers->any(fn($n) => $n % 2 === 0); // false
$hasOdd = $numbers->any(fn($n) => $n % 2 === 1);  // true
```

### All

Checks if all elements satisfy a predicate.

```php
$numbers = Set::of(2, 4, 6, 8);
$allEven = $numbers->all(fn($n) => $n % 2 === 0);   // true
$allPositive = $numbers->all(fn($n) => $n > 0);      // true
$allSmall = $numbers->all(fn($n) => $n < 5);         // false
```

## Set Operations

### Add

Adds an element to the Set. Has no effect if the element already exists.

```php
$set = Set::of(1, 2, 3);
$withFour = $set->add(4);     // Set { 1, 2, 3, 4 }
$unchanged = $set->add(2);     // Set { 1, 2, 3 } (2 already present)
```

### Remove

Removes an element from the Set.

```php
$set = Set::of(1, 2, 3);
$smaller = $set->remove(2); // Set { 1, 3 }
```

### Append (Union)

Combines two Sets, returning a new Set containing all elements from both.

```php
$set1 = Set::of(1, 2, 3);
$set2 = Set::of(3, 4, 5);
$union = $set1->append($set2); // Set { 1, 2, 3, 4, 5 }
```

### Intersection

Returns a new Set containing only elements present in both Sets.

```php
$set1 = Set::of(1, 2, 3, 4);
$set2 = Set::of(3, 4, 5, 6);
$common = $set1->intersection($set2); // Set { 3, 4 }
```

### Difference

Returns a new Set containing elements present in the first Set but not in the second.

```php
$set1 = Set::of(1, 2, 3, 4);
$set2 = Set::of(3, 4, 5, 6);
$diff = $set1->difference($set2); // Set { 1, 2 }
```

### Is Disjoint

Checks if two Sets share no common elements.

```php
$set1 = Set::of(1, 2, 3);
$set2 = Set::of(4, 5, 6);
$set1->isDisjoint($set2); // true

$set3 = Set::of(3, 4, 5);
$set1->isDisjoint($set3); // false (3 is shared)
```

### Is Subset

Checks if all elements of this Set are contained in another Set.

```php
$small = Set::of(1, 2);
$large = Set::of(1, 2, 3, 4, 5);
$small->isSubset($large); // true
$large->isSubset($small); // false
```

### Is Superset

Checks if this Set contains all elements of another Set.

```php
$large = Set::of(1, 2, 3, 4, 5);
$small = Set::of(1, 2);
$large->isSuperset($small); // true
$small->isSuperset($large); // false
```

## Transformation

### Map

Transforms each element using a mapper function. The result is a new Set with unique mapped values.

```php
$numbers = Set::of(1, 2, 3, 4);
$doubled = $numbers->map(fn($n) => $n * 2); // Set { 2, 4, 6, 8 }

// Mapping can reduce size if results collide
$modulo = Set::of(1, 2, 3, 4)->map(fn($n) => $n % 2); // Set { 0, 1 }
```

### FlatMap

Maps each element to an iterable and flattens the results into a single Set.

```php
$words = Set::of('hello', 'world');
$chars = $words->flatMap(fn($w) => str_split($w));
// Set { 'h', 'e', 'l', 'o', 'w', 'r', 'd' }
```

### Flatten

Flattens a Set of iterables into a single Set.

```php
$nested = Set::of([1, 2], [3, 4]);
$flat = $nested->flatten(); // Set { 1, 2, 3, 4 }
```

## Searching and Filtering

### Filter

Filters elements based on a predicate.

```php
$numbers = Set::of(1, 2, 3, 4, 5, 6, 7, 8);
$evens = $numbers->filter(fn($n) => $n % 2 === 0); // Set { 2, 4, 6, 8 }
```

### Filter Map

Maps each element to an optional value and keeps only the `Some` values.

```php
$strings = Set::of('42', 'hello', '7', 'world');
$numbers = $strings->filterMap(function($s) {
    $n = filter_var($s, FILTER_VALIDATE_INT);
    return $n !== false ? Option::some($n) : Option::none();
}); // Set { 42, 7 }
```

## Iteration

### For Each

Applies a callback to each element. Intended for side effects.

```php
$tags = Set::of('php', 'functional', 'immutable');
$tags->forEach(fn($tag) => echo "#$tag ");
// Outputs: #php #functional #immutable
```

## Aggregation

### Fold

Reduces the Set to a single value using an accumulator function.

```php
$numbers = Set::of(1, 2, 3, 4, 5);
$sum = $numbers->fold(fn($acc, $n) => $acc + $n, 0); // 15

$words = Set::of('PHP', 'Core', 'Library');
$joined = $words->fold(fn($acc, $w) => $acc === '' ? $w : "$acc $w", '');
```

## Conversion

### To Array

Converts the Set to a plain array.

```php
$set = Set::of(3, 1, 2);
$array = $set->toArray(); // [3, 1, 2]
```

### To Sequence

Converts the Set to a Sequence.

```php
$set = Set::of(1, 2, 3);
$seq = $set->toSequence(); // Sequence [1, 2, 3]

// Chain with Sequence operations
$sorted = $set->toSequence()->sort(); // Sequence [1, 2, 3]
```

## Type Safety

Type safety in Set is enforced through static analysis tools like Psalm. Element types are tracked via generics.

```php
// Psalm infers Set<int>
$numbers = Set::of(1, 2, 3);

// This will fail static analysis: wrong element type
// $numbers->add('four');

// Psalm infers Set<string>
$tags = Set::of('php', 'rust');
```

Remember that Set is immutable: every operation returns a new Set instance rather than modifying the original.
