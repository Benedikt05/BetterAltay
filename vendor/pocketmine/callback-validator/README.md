Callback Validator
==================

Validates callback signatures against a prototype.

This is a fork of [daverandom/callback-validator](https://github.com/DaveRandom/CallbackValidator) used by PocketMine-MP. There are no significant changes from the upstream repository apart from more test versions, updated dependencies, and tagged releases for packages to use.

Since the upstream version has no release, it affects the composer stability of packages that use it. This caused problems for packages depending on [`pocketmine/pocketmine-mp`](https://github.com/pmmp/PocketMine-MP) because they could not receive its latest versions.

## Status

![CI](https://github.com/pmmp/CallbackValidator/workflows/CI/badge.svg)

## Usage

```php
// Create a prototype function (can be any callable)
$prototype = function (A $a, B $b, $c): ?string {};

// Validate that callables match the prototype
$tests = [
    $prototype, // true
    function (A $a, B $b, $c) {}, // false - return type does not match
    function ($a, $b, $c): ?string {}, // true - arguments are contravariant
    function (A $a, B $b): ?string {}, // true - extra args don't cause errors
    function (A $a, B $b, $c, $d): ?string {}, // false - Insufficient args cause an error
    function (C $a, B $b, $c): ?string {}, // true if C is a supertype of A, false otherwise
    function (SuperTypeOfA $a, B $b, $c): ?string {}, // true
    function (A $a, B $b, $c): string {}, // true - return types are covariant
];

// Create a type from a prototype
$type = CallbackType::createFromCallable($prototype);

run_tests($type, $tests);

// ...or create a type by hand for more granular control over variance rules
$type = new CallbackType(
    new ReturnType(BuiltInTypes::STRING, ReturnType::NULLABLE | ReturnType::COVARIANT),
    new ParameterType('a', A::class),
    new ParameterType('b', B::class),
    new ParameterType('c')
);

run_tests($type, $tests);

function run_tests(CallbackType $type, array $tests)
{
    foreach ($tests as $test) {
        if ($type->isSatisfiedBy($test)) {
            echo "pass\n";
        } else {
            // CallbackType implements __toString() for easy inspections
            echo CallbackType::createFromCallable($test) . " does not satisfy {$type}\n";
        }
    }
}
```

## TODO

- Lots more tests
- Explain (text explanation of why callback does not validate)
