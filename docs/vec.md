# Vec (Vector)

Vector is an ordered, immutable collection of elements of the same type that provides a rich set of functional operations. Vec draws inspiration from Rust's Vec type but is adapted to PHP's ecosystem. Type safety is enforced via static analysis only (no runtime type checking).

Vecs are particularly useful when you need to:
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
// Create a vector from individual elements
$vec = Vec::from(1, 2, 3, 4, 5);
```

### From Array

Creates a new collection from the given array. This is a helper method to avoid performance issues with variadic arguments in the `Vec::from` method when working with large arrays.

```php
// Create a vector from an existing array
$array = [1, 2, 3, 4, 5];
$vec = Vec::fromArray($array);
```

### Empty Vector

Creates a new empty collection.

```php
// Create an empty vector
$emptyVec = Vec::new();
```

## Basic Operations

### Length

Gets the number of elements in the collection.

```php
$vec = Vec::from(1, 2, 3);
$length = $vec->len(); // 3

$empty = Vec::new();
$length = $empty->len(); // 0
```

### Is Empty

Checks if the collection contains no elements.

```php
$vec = Vec::new();
$isEmpty = $vec->isEmpty(); // true

$vec = Vec::from(1);
$isEmpty = $vec->isEmpty(); // false
```

### String Representation

Returns a string representation of the collection, including the type of elements it contains.

```php
$vec = Vec::from(1, 2, 3);
echo $vec; // "Vec<integer> [1, 2, 3]"

$empty = Vec::new();
echo $empty; // "Vec<> []"
```

### Equality

Checks if two Vecs contain the same elements in the same order. The comparison is done using strict equality (`===`).

```php
$vec1 = Vec::from(1, 2, 3);
$vec2 = Vec::from(1, 2, 3);
$areEqual = $vec1->eq($vec2); // true

$vec3 = Vec::from(3, 2, 1);
$areEqual = $vec1->eq($vec3); // false
```

## Inspection

### Contains

Checks if the collection contains the given element. Uses strict comparison (`===`).

```php
$vec = Vec::from(1, 2, 3);
$hasTwo = $vec->contains(2); // true
$hasFour = $vec->contains(4); // false
```

### All

Checks if all elements in the collection satisfy the given predicate. Returns true if all elements satisfy the predicate, false otherwise.

```php
$vec = Vec::from(1, 2, 3);
$allPositive = $vec->all(fn($n) => $n > 0); // true
$allEven = $vec->all(fn($n) => $n % 2 === 0); // false
```

### Any

Checks if any element in the collection satisfies the given predicate. Returns true if at least one element satisfies the predicate, false otherwise.

```php
$vec = Vec::from(1, 2, 3);
$anyEven = $vec->any(fn($n) => $n % 2 === 0); // true
$anyNegative = $vec->any(fn($n) => $n < 0); // false
```

### Index Of

Gets the index of the first occurrence of the given element. Returns an Option containing the index if found, or None if not found.

```php
$vec = Vec::from("apple", "banana", "cherry");
$index = $vec->indexOf("banana"); // Option::some(1)
$notFound = $vec->indexOf("grape"); // Option::none()
```

## Transformation

### Map

Maps each element in the collection to a new value using the given callback function. Returns a new Vec containing the transformed elements.

```php
$vec = Vec::from(1, 2, 3);
$doubled = $vec->map(fn($n) => $n * 2); // Vec [2, 4, 6]

$vec = Vec::from('a', 'b', 'c');
$upper = $vec->map(fn($s) => strtoupper($s)); // Vec [A, B, C]
```

### Filter

Filters the elements of the collection using the given callback. Returns a new Vec containing only the elements for which the callback returns true.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$evenNumbers = $vec->filter(fn($n) => $n % 2 === 0); // Vec [2, 4]

$vec = Vec::from('apple', 'banana', 'cherry');
$longWords = $vec->filter(fn($s) => strlen($s) > 5); // Vec [banana, cherry]
```

**Note:** Array keys are not preserved in the resulting collection.

### Filter Map

Maps each element to an optional value and filters out the None values. This combines the functionality of `map` and `filter` in a single operation.

```php
$vec = Vec::from(1, -2, 3, -4, 5);
$positiveSquared = $vec->filterMap(function($n) {
    if ($n > 0) {
        return Option::some($n * $n);
    }
    return Option::none();
}); // Vec [1, 9, 25]
```

### Flat Map

Maps elements to iterables and then flattens the result into a single collection. This is equivalent to calling `map()` followed by `flatten()`.

```php
$vec = Vec::from(1, 2, 3);
$result = $vec->flatMap(fn($n) => Vec::from($n, $n * 10)); // Vec [1, 10, 2, 20, 3, 30]
```

### Flatten

Flattens a collection of collections into a single collection. If the collection contains Vec instances or other iterables (like arrays), this method extracts all elements from those nested collections and combines them into a single Vec. Only one level of nesting is flattened.

```php
$nested = Vec::from(
    Vec::from(1, 2),
    Vec::from(3, 4),
    5
);
$flat = $nested->flatten(); // Vec [1, 2, 3, 4, 5]
```

### Reverse

Reverses the order of elements in the Vec.

```php
$vec = Vec::from(1, 2, 3);
$reversed = $vec->reverse(); // Vec [3, 2, 1]
```

### Sort

Sorts the collection using the default sorting algorithm. The sorting type is determined automatically based on the type of the first element:
- Numbers are sorted numerically
- Strings are sorted alphabetically
- Other types use PHP's default comparison

```php
$vec = Vec::from(3, 1, 2);
$sorted = $vec->sort(); // Vec [1, 2, 3]
```

### Sort By

Sorts the collection using a custom comparator. The comparison function must return an integer:
- Less than 0 if the first argument is less than the second
- 0 if they are equal
- Greater than 0 if the first argument is greater than the second

```php
$people = Vec::from(
    ["name" => "Alice", "age" => 30],
    ["name" => "Bob", "age" => 25],
    ["name" => "Charlie", "age" => 35]
);

$sortedByAge = $people->sortBy(fn($person) => $person["age"]);
// Vec [["name" => "Bob", "age" => 25], ["name" => "Alice", "age" => 30], ["name" => "Charlie", "age" => 35]]
```

### Dedup (Remove Duplicates)

Returns a new Vec with duplicate elements removed. For scalar types (integer, float, string, boolean), this uses a hash map approach. For objects, objects are compared by reference, so only identical object instances are considered duplicates.

```php
$vec = Vec::from(1, 2, 2, 3, 3, 3, 4);
$unique = $vec->dedup(); // Vec [1, 2, 3, 4]
```

## Element Access

### Get

Gets an element from the collection at the given index. Returns an Option containing the element if the index is valid, or None if the index is out of bounds.

```php
$vec = Vec::from("apple", "banana", "cherry");
$second = $vec->get(1); // Option::some("banana")
$outOfBounds = $vec->get(10); // Option::none()
```

### First

Returns the first element of the collection. If the collection is empty, returns None.

```php
$vec = Vec::from("apple", "banana", "cherry");
$first = $vec->first(); // Option::some("apple")

$empty = Vec::new();
$noFirst = $empty->first(); // Option::none()
```

### Last

Returns the last element of the collection. If the collection is empty, returns None.

```php
$vec = Vec::from("apple", "banana", "cherry");
$last = $vec->last(); // Option::some("cherry")

$empty = Vec::new();
$noLast = $empty->last(); // Option::none()
```

## Searching and Filtering

### Find

Finds the first element in the collection that satisfies the given predicate. Returns an Option containing the first matching element, or None if no element matches.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$found = $vec->find(fn($n) => $n > 3); // Option::some(4)
$notFound = $vec->find(fn($n) => $n > 10); // Option::none()
```

### Find Map

Maps each element to an optional value and returns the first Some value. This is useful when you want to transform elements while searching for a specific condition. It combines the functionality of `find` and `map` in a single operation.

```php
$vec = Vec::from(1, -2, 3, -4, 5);
$found = $vec->findMap(function($n) {
    if ($n < 0) {
        return Option::some($n * -1);
    }
    return Option::none();
}); // Option::some(2) - finds first negative number and returns its absolute value
```

### Take

Takes elements from the beginning of the Vec up to the specified count and returns a new Vec.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$firstThree = $vec->take(3); // Vec [1, 2, 3]
```

### Take While

Takes elements from the beginning of the Vec while the predicate is true and returns a new Vec.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$lessThanFour = $vec->takeWhile(fn($n) => $n < 4); // Vec [1, 2, 3]

$vec = Vec::from(2, 4, 6, 7, 8);
$evens = $vec->takeWhile(fn($n) => $n % 2 === 0); // Vec [2, 4, 6]
```

### Skip

Skips elements from the beginning of the Vec up to the specified count and returns a new Vec with the remaining elements.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$withoutFirstTwo = $vec->skip(2); // Vec [3, 4, 5]
```

### Skip While

Creates a new Vec by skipping elements from the beginning of the Vec while the predicate is true.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$skipLessThanThree = $vec->skipWhile(fn($n) => $n < 3); // Vec [3, 4, 5]

$vec = Vec::from(2, 4, 6, 7, 8, 10);
$afterFirstOdd = $vec->skipWhile(fn($n) => $n % 2 === 0); // Vec [7, 8, 10]
```

## Iteration

### For Each

Applies the given callback to each element of the collection. This should be used when you want to perform an action that has a side effect, such as printing, logging, or updating a database.

```php
$vec = Vec::from(1, 2, 3);
$vec->forEach(fn($n) => echo "$n, "); // Outputs: 1, 2, 3,
```

**Note:** Unlike most Vec methods, this method doesn't return a new Vec as it's intended for side effects only. This is one of the few non-pure functions in the Vec API.

### Iterator

Returns an iterable for the collection, allowing you to use the Vec in foreach loops.

```php
$vec = Vec::from(1, 2, 3);
foreach ($vec->iter() as $item) {
    echo "$item, "; // Outputs: 1, 2, 3,
}
```

## Aggregation

### Fold

Folds the collection to a single value using the given callback. The callback takes an accumulator and the current element, and returns a new accumulator value.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$sum = $vec->fold(fn($acc, $n) => $acc + $n, 0); // 15
$product = $vec->fold(fn($acc, $n) => $acc * $n, 1); // 120

$vec = Vec::from('a', 'b', 'c');
$concat = $vec->fold(fn($acc, $s) => $acc . $s, ''); // "abc"
```

## Combination

### Append

Appends another collection to the current one, returning a new Vec containing elements from both collections.

```php
$vec1 = Vec::from(1, 2, 3);
$vec2 = Vec::from(4, 5, 6);
$combined = $vec1->append($vec2); // Vec [1, 2, 3, 4, 5, 6]
```

### Zip

Combines two Vecs into a single Vec of pairs. Each pair is represented as a Vec with two elements.

```php
$vec1 = Vec::from(1, 2, 3);
$vec2 = Vec::from(4, 5, 6);
$zipped = $vec1->zip($vec2); // Vec [Vec [1, 4], Vec [2, 5], Vec [3, 6]]
```

**Note:** If the Vecs are of different lengths, the resulting Vec will be as long as the shorter one.

### Push

Appends an element to the end of the collection, returning a new Vec with the added element.

```php
$vec = Vec::from(1, 2, 3);
$withFour = $vec->push(4); // Vec [1, 2, 3, 4]
```

### Insert At

Inserts an element at the given index, returning a new Vec with the inserted element.

```php
$vec = Vec::from(1, 3, 4);
$withTwo = $vec->insertAt(1, 2); // Vec [1, 2, 3, 4]
```

## Advanced Operations

### Windows

Generates sliding windows of the given size from the collection. Creates overlapping views into the original collection, each with the specified size.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$windows = $vec->windows(3);
// Vec [Vec [1, 2, 3], Vec [2, 3, 4], Vec [3, 4, 5]]
```

**Note:** If the collection is smaller than the window size, an empty Vec is returned.

### Resize

Resizes the collection to a given size, filling with a specified value if necessary. If the new size is smaller, elements are truncated. If it's larger, the specified value is used to fill the additional slots.

```php
$vec = Vec::from(1, 2, 3);
$larger = $vec->resize(5, 0); // Vec [1, 2, 3, 0, 0]
$smaller = $vec->resize(2, 0); // Vec [1, 2]
```

### Truncate

Truncates the Vec to the specified size, keeping only the first N elements.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$truncated = $vec->truncate(3); // Vec [1, 2, 3]
```

**Note:** If the specified size is 0, an empty Vec is returned.

### Swap

Swaps two elements in the Vec at the given indices and returns a Result containing the modified Vec or an error if any index is out of bounds.

```php
$vec = Vec::from("a", "b", "c", "d");
$swapped = $vec->swap(0, 3); // Result::ok(Vec ["d", "b", "c", "a"])
```

### Remove At

Removes an element at the specified index, returning a new Vec without that element.

```php
$vec = Vec::from(1, 2, 3, 4, 5);
$withoutThird = $vec->removeAt(2); // Vec [1, 2, 4, 5]
```

**Note:** If the index is out of bounds or the collection is empty, the original Vec is returned unchanged.

### Clear

Clears the collection, removing all elements.

```php
$vec = Vec::from(1, 2, 3);
$empty = $vec->clear(); // Vec []
```

## Type Safety

Type safety in Vec is enforced through static analysis tools like Psalm. When working with Vec, it's recommended to maintain consistent types within a collection to ensure all operations behave as expected.

```php
// This will work at runtime but will fail static analysis
$mixedVec = Vec::from(1, "two", 3.0);

// This is the recommended approach
$intVec = Vec::from(1, 2, 3);
$strVec = Vec::from("one", "two", "three");
```

Remember that Vec is immutable, so every operation returns a new Vec instance rather than modifying the original. This ensures that your data remains consistent and predictable throughout your application.
