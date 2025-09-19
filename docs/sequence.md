# Sequence

Sequence is an ordered, immutable collection of elements of the same type that provides a rich set of functional operations. Sequence draws inspiration from Rust's Sequence type but is adapted to PHP's ecosystem. Type safety is enforced via static analysis only (no runtime type checking).

Sequences are particularly useful when you need to:
- Maintain an ordered collection of items
- Perform a series of transformations on a collection
- Ensure your collection's data remains consistent throughout your application
- Apply functional programming patterns

## Table of Contents

- [Creation](#creation)
- [Basic Operations](#basic-operations)
- [Inspection](#inspection)
- [Transformation](#transformation)
- [Element Access](#element-access)
- [Searching and Filtering](#searching-and-filtering)
- [Iteration](#iteration)
- [Aggregation](#aggregation)
- [Combination](#combination)
- [Advanced Operations](#advanced-operations)
- [Type Safety](#type-safety)

## Creation

### From Elements

Creates a new collection from the given individual elements.

```php
// Create a Sequence from individual elements
$seq = Sequence::from(1, 2, 3, 4, 5);
```

### From Array

Creates a new collection from the given array. This is a helper method to avoid performance issues with variadic arguments in the `Sequence::from` method when working with large arrays.

```php
// Create a Sequence from an existing array
$array = [1, 2, 3, 4, 5];
$seq = Sequence::fromArray($array);
```

### Empty Sequence

Creates a new empty collection.

```php
// Create an empty Sequence
$emptySequence = Sequence::new();
```

## Basic Operations

### Length

Gets the number of elements in the collection.

```php
$seq = Sequence::from(1, 2, 3);
$length = $seq->len(); // 3

$empty = Sequence::new();
$length = $empty->len(); // 0
```

### Is Empty

Checks if the collection contains no elements.

```php
$seq = Sequence::new();
$isEmpty = $seq->isEmpty(); // true

$seq = Sequence::from(1);
$isEmpty = $seq->isEmpty(); // false
```

### String Representation

Returns a string representation of the collection, including the type of elements it contains.

```php
$seq = Sequence::from(1, 2, 3);
echo $seq; // "Sequence<integer> [1, 2, 3]"

$empty = Sequence::new();
echo $empty; // "Sequence<> []"
```

### Equality

Checks if two Sequences contain the same elements in the same order. The comparison is done using strict equality (`===`).

```php
$seq1 = Sequence::from(1, 2, 3);
$seq2 = Sequence::from(1, 2, 3);
$areEqual = $seq1->eq($seq2); // true

$seq3 = Sequence::from(3, 2, 1);
$areEqual = $seq1->eq($seq3); // false
```

## Inspection

### Contains

Checks if the collection contains the given element. Uses strict comparison (`===`).

```php
$seq = Sequence::from(1, 2, 3);
$hasTwo = $seq->contains(2); // true
$hasFour = $seq->contains(4); // false
```

### All

Checks if all elements in the collection satisfy the given predicate. Returns true if all elements satisfy the predicate, false otherwise.

```php
$seq = Sequence::from(1, 2, 3);
$allPositive = $seq->all(fn($n) => $n > 0); // true
$allEven = $seq->all(fn($n) => $n % 2 === 0); // false
```

### Any

Checks if any element in the collection satisfies the given predicate. Returns true if at least one element satisfies the predicate, false otherwise.

```php
$seq = Sequence::from(1, 2, 3);
$anyEven = $seq->any(fn($n) => $n % 2 === 0); // true
$anyNegative = $seq->any(fn($n) => $n < 0); // false
```

### Index Of

Gets the index of the first occurrence of the given element. Returns an Option containing the index if found, or None if not found.

```php
$seq = Sequence::from("apple", "banana", "cherry");
$index = $seq->indexOf("banana"); // Option::some(1)
$notFound = $seq->indexOf("grape"); // Option::none()
```

## Transformation

### Map

Maps each element in the collection to a new value using the given callback function. Returns a new Sequence containing the transformed elements.

```php
$seq = Sequence::from(1, 2, 3);
$doubled = $seq->map(fn($n) => $n * 2); // Sequence [2, 4, 6]

$seq = Sequence::from('a', 'b', 'c');
$upper = $seq->map(fn($s) => strtoupper($s)); // Sequence [A, B, C]
```

### Filter

Filters the elements of the collection using the given callback. Returns a new Sequence containing only the elements for which the callback returns true.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$evenNumbers = $seq->filter(fn($n) => $n % 2 === 0); // Sequence [2, 4]

$seq = Sequence::from('apple', 'banana', 'cherry');
$longWords = $seq->filter(fn($s) => strlen($s) > 5); // Sequence [banana, cherry]
```

**Note:** Array keys are not preserved in the resulting collection.

### Filter Map

Maps each element to an optional value and filters out the None values. This combines the functionality of `map` and `filter` in a single operation.

```php
$seq = Sequence::from(1, -2, 3, -4, 5);
$positiveSquared = $seq->filterMap(function($n) {
    if ($n > 0) {
        return Option::some($n * $n);
    }
    return Option::none();
}); // Sequence [1, 9, 25]
```

### Flat Map

Maps elements to iterables and then flattens the result into a single collection. This is equivalent to calling `map()` followed by `flatten()`.

```php
$seq = Sequence::from(1, 2, 3);
$result = $seq->flatMap(fn($n) => Sequence::from($n, $n * 10)); // Sequence [1, 10, 2, 20, 3, 30]
```

### Flatten

Flattens a collection of collections into a single collection. If the collection contains Sequence instances or other iterables (like arrays), this method extracts all elements from those nested collections and combines them into a single Sequence. Only one level of nesting is flattened.

```php
$nested = Sequence::from(
    Sequence::from(1, 2),
    Sequence::from(3, 4),
    5
);
$flat = $nested->flatten(); // Sequence [1, 2, 3, 4, 5]
```

### Reverse

Reverses the order of elements in the Sequence.

```php
$seq = Sequence::from(1, 2, 3);
$reversed = $seq->reverse(); // Sequence [3, 2, 1]
```

### Sort

Sorts the collection using the default sorting algorithm. The sorting type is determined automatically based on the type of the first element:
- Numbers are sorted numerically
- Strings are sorted alphabetically
- Other types use PHP's default comparison

```php
$seq = Sequence::from(3, 1, 2);
$sorted = $seq->sort(); // Sequence [1, 2, 3]
```

### Sort By

Sorts the collection using a custom comparator. The comparison function must return an integer:
- Less than 0 if the first argument is less than the second
- 0 if they are equal
- Greater than 0 if the first argument is greater than the second

```php
$people = Sequence::from(
    ["name" => "Alice", "age" => 30],
    ["name" => "Bob", "age" => 25],
    ["name" => "Charlie", "age" => 35]
);

$sortedByAge = $people->sortBy(fn($person) => $person["age"]);
// Sequence [["name" => "Bob", "age" => 25], ["name" => "Alice", "age" => 30], ["name" => "Charlie", "age" => 35]]
```

### Dedup (Remove Duplicates)

Returns a new Sequence with duplicate elements removed. For scalar types (integer, float, string, boolean), this uses a hash map approach. For objects, objects are compared by reference, so only identical object instances are considered duplicates.

```php
$seq = Sequence::from(1, 2, 2, 3, 3, 3, 4);
$unique = $seq->dedup(); // Sequence [1, 2, 3, 4]
```

## Element Access

### Get

Gets an element from the collection at the given index. Returns an Option containing the element if the index is valid, or None if the index is out of bounds.

```php
$seq = Sequence::from("apple", "banana", "cherry");
$second = $seq->get(1); // Option::some("banana")
$outOfBounds = $seq->get(10); // Option::none()
```

### First

Returns the first element of the collection. If the collection is empty, returns None.

```php
$seq = Sequence::from("apple", "banana", "cherry");
$first = $seq->first(); // Option::some("apple")

$empty = Sequence::new();
$noFirst = $empty->first(); // Option::none()
```

### Last

Returns the last element of the collection. If the collection is empty, returns None.

```php
$seq = Sequence::from("apple", "banana", "cherry");
$last = $seq->last(); // Option::some("cherry")

$empty = Sequence::new();
$noLast = $empty->last(); // Option::none()
```

## Searching and Filtering

### Find

Finds the first element in the collection that satisfies the given predicate. Returns an Option containing the first matching element, or None if no element matches.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$found = $seq->find(fn($n) => $n > 3); // Option::some(4)
$notFound = $seq->find(fn($n) => $n > 10); // Option::none()
```

### Find Map

Maps each element to an optional value and returns the first Some value. This is useful when you want to transform elements while searching for a specific condition. It combines the functionality of `find` and `map` in a single operation.

```php
$seq = Sequence::from(1, -2, 3, -4, 5);
$found = $seq->findMap(function($n) {
    if ($n < 0) {
        return Option::some($n * -1);
    }
    return Option::none();
}); // Option::some(2) - finds first negative number and returns its absolute value
```

### Take

Takes elements from the beginning of the Sequence up to the specified count and returns a new Sequence.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$firstThree = $seq->take(3); // Sequence [1, 2, 3]
```

### Take While

Takes elements from the beginning of the Sequence while the predicate is true and returns a new Sequence.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$lessThanFour = $seq->takeWhile(fn($n) => $n < 4); // Sequence [1, 2, 3]

$seq = Sequence::from(2, 4, 6, 7, 8);
$evens = $seq->takeWhile(fn($n) => $n % 2 === 0); // Sequence [2, 4, 6]
```

### Skip

Skips elements from the beginning of the Sequence up to the specified count and returns a new Sequence with the remaining elements.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$withoutFirstTwo = $seq->skip(2); // Sequence [3, 4, 5]
```

### Skip While

Creates a new Sequence by skipping elements from the beginning of the Sequence while the predicate is true.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$skipLessThanThree = $seq->skipWhile(fn($n) => $n < 3); // Sequence [3, 4, 5]

$seq = Sequence::from(2, 4, 6, 7, 8, 10);
$afterFirstOdd = $seq->skipWhile(fn($n) => $n % 2 === 0); // Sequence [7, 8, 10]
```

## Iteration

### For Each

Applies the given callback to each element of the collection. This should be used when you want to perform an action that has a side effect, such as printing, logging, or updating a database.

```php
$seq = Sequence::from(1, 2, 3);
$seq->forEach(fn($n) => echo "$n, "); // Outputs: 1, 2, 3,
```

**Note:** Unlike most Sequence methods, this method doesn't return a new Sequence as it's intended for side effects only. This is one of the few non-pure functions in the Sequence API.

### Iterator

Returns an iterable for the collection, allowing you to use the Sequence in foreach loops.

```php
$seq = Sequence::from(1, 2, 3);
foreach ($seq->iter() as $item) {
    echo "$item, "; // Outputs: 1, 2, 3,
}
```

## Aggregation

### Fold

Folds the collection to a single value using the given callback. The callback takes an accumulator and the current element, and returns a new accumulator value.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$sum = $seq->fold(fn($acc, $n) => $acc + $n, 0); // 15
$product = $seq->fold(fn($acc, $n) => $acc * $n, 1); // 120

$seq = Sequence::from('a', 'b', 'c');
$concat = $seq->fold(fn($acc, $s) => $acc . $s, ''); // "abc"
```

## Combination

### Append

Appends another collection to the current one, returning a new Sequence containing elements from both collections.

```php
$seq1 = Sequence::from(1, 2, 3);
$seq2 = Sequence::from(4, 5, 6);
$combined = $seq1->append($seq2); // Sequence [1, 2, 3, 4, 5, 6]
```

### Zip

Combines two Sequences into a single Sequence of pairs. Each pair is represented as a Sequence with two elements.

```php
$seq1 = Sequence::from(1, 2, 3);
$seq2 = Sequence::from(4, 5, 6);
$zipped = $seq1->zip($seq2); // Sequence [Sequence [1, 4], Sequence [2, 5], Sequence [3, 6]]
```

**Note:** If the Sequences are of different lengths, the resulting Sequence will be as long as the shorter one.

### Push

Appends an element to the end of the collection, returning a new Sequence with the added element.

```php
$seq = Sequence::from(1, 2, 3);
$withFour = $seq->push(4); // Sequence [1, 2, 3, 4]
```

### Insert At

Inserts an element at the given index, returning a new Sequence with the inserted element.

```php
$seq = Sequence::from(1, 3, 4);
$withTwo = $seq->insertAt(1, 2); // Sequence [1, 2, 3, 4]
```

## Advanced Operations

### Windows

Generates sliding windows of the given size from the collection. Creates overlapping views into the original collection, each with the specified size.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$windows = $seq->windows(3);
// Sequence [Sequence [1, 2, 3], Sequence [2, 3, 4], Sequence [3, 4, 5]]
```

**Note:** If the collection is smaller than the window size, an empty Sequence is returned.

### Resize

Resizes the collection to a given size, filling with a specified value if necessary. If the new size is smaller, elements are truncated. If it's larger, the specified value is used to fill the additional slots.

```php
$seq = Sequence::from(1, 2, 3);
$larger = $seq->resize(5, 0); // Sequence [1, 2, 3, 0, 0]
$smaller = $seq->resize(2, 0); // Sequence [1, 2]
```

### Truncate

Truncates the Sequence to the specified size, keeping only the first N elements.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$truncated = $seq->truncate(3); // Sequence [1, 2, 3]
```

**Note:** If the specified size is 0, an empty Sequence is returned.

### Swap

Swaps two elements in the Sequence at the given indices and returns a Result containing the modified Sequence or an error if any index is out of bounds.

```php
$seq = Sequence::from("a", "b", "c", "d");
$swapped = $seq->swap(0, 3); // Result::ok(Sequence ["d", "b", "c", "a"])
```

### Remove At

Removes an element at the specified index, returning a new Sequence without that element.

```php
$seq = Sequence::from(1, 2, 3, 4, 5);
$withoutThird = $seq->removeAt(2); // Sequence [1, 2, 4, 5]
```

**Note:** If the index is out of bounds or the collection is empty, the original Sequence is returned unchanged.

### Clear

Clears the collection, removing all elements.

```php
$seq = Sequence::from(1, 2, 3);
$empty = $seq->clear(); // Sequence []
```

## Type Safety

Type safety in Sequence is enforced through static analysis tools like Psalm. When working with Sequence, it's recommended to maintain consistent types within a collection to ensure all operations behave as expected.

```php
// This will work at runtime but will fail static analysis
$mixedSequence = Sequence::from(1, "two", 3.0);

// This is the recommended approach
$intSequence = Sequence::from(1, 2, 3);
$strSequence = Sequence::from("one", "two", "three");
```

Remember that Sequence is immutable, so every operation returns a new Sequence instance rather than modifying the original. This ensures that your data remains consistent and predictable throughout your application.
