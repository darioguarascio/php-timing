# PHP-Timing

A simple static class to dissect execution times of a full script or parts of it.

## Getting Started

Include `Timing.php` via `include`, `require` or `composer autoload` and use it as a static class.

## Methods

### Timing:: start('name') / stop('name')

Calculate the execution time between two points.

```
Timing::start('random calc');
for ($i = 0; $i < 10000; $i++) {
    $r = rand();
}
Timing::stop('random calc');
```


### Timing:: break

Saves the time elapsed since the script started to the break point

```
$var = rand();
sleep(2);

Timing::break();

$var = rand();

```

### Timing:: measure('name', callback)

Calculate the execution time of a function

```
$returnVal = 0;
for ($i = 1; $i < 3; $i++) {
    $returnVal += Timing::measure('someFunction('.$i.')', function() use ($i) {
        sleep($i);
        return $i;
    });
}
var_dump($returnVal);
```


### Timing:: addCallback( callback )

Adds a function called every time a new timing has been saved

```
Timing::addCallback(function($key, $time) {
    var_dump("Collected timing for: $key: ". round($time,4));
});

```

### Timing:: getTimings($round = 3, $slowThreshold = -INF, callable $cb )

Returns all saved times under a key=>value array

```
Array
(
    [start] => 0
    [random calc] => 0.0003
    [someFunction(1)] => 1.0001
    [break @ sample.php:45] => 1.0006
    [someFunction(2)] => 2.0001
)
```
