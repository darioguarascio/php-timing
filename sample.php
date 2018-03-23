<?php
include 'Timing.php';

Timing::addCallback(function($key, $time) {
    var_dump("Collected timing for: $key: ". round($time,4));
});


register_shutdown_function(function() {

    // printing microtimes of everything collected
    print_r(Timing::getTimings());


    // Normalizing and averaging similar data
    $timings = Timing::getTimings($round = 3, $slowThreshold = -INF, function ($t) {
        $return = array();
        foreach ($t as $key => $v) {
            $key = str_replace(array(':','\\'), '_', $key);
            $key = preg_replace('/\(.*?\)/', '', $key);
            $return[$key] = isset($return[$key]) ? round(($return[$key] + $v) / 2, 3) : $v;
        }
        return $return;
    });

    print_r($timings);
});

Timing::break('start');


Timing::start('random calc');
for ($i = 0; $i < 10000; $i++) {
    $r = rand();
}
$elapsed = Timing::stop('random calc');

var_dump($elapsed);

$returnVal = Timing::measure('someFunction(1)', function() {
    sleep(1);
    return 10;
});

Timing::break();

$returnVal = Timing::measure('someFunction(2)', function() {
    sleep(2);
    return 100;
});

$returnVal = 0;
for ($i = 1; $i < 3; $i++) {
    $returnVal += Timing::measure('someFunction('.$i.')', function() use ($i) {
        sleep($i);
        return $i;
    });
}
var_dump($returnVal);
