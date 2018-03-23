<?php
if (!defined('START_MICROTIME')) { define('START_MICROTIME', microtime(true)); }

class Timing {

    private static $lastKey;
    private static $keys    = array();
    private static $timings = array(), $result = array();
    private static $cbList  = array();

    /**
     * @return void
     */
    public static function addCallback(callable $fn) {
        self::$cbList[] = $fn;
    }

    /**
     * Add a point since the start
     *
     * @param $key
     *
     * @return vod
     */
    public static function break($key = null) {
        $key = self::getKey($key, __FUNCTION__);
        self::stop($key);
    }

    /**
     * Start a measure
     *
     * @param $key
     *
     * @return vod
     */
    public static function start($key = null) {
        $key = self::getKey($key, __FUNCTION__);
        self::$lastKey = $key;
        self::$timings[ $key ]['start'] = microtime(true);
    }



    /**
     * Stop a measure
     *
     * @param $key
     *
     * @return float
     */
    public static function stop($key = null) {
        if (is_null($key)) {
            $key = self::$lastKey;
            self::$lastKey = null;
        }
        if (!isset(self::$timings[$key])) {
            self::$timings[$key] = array();
        }
        $start   = isset(self::$timings[ $key ]['start']) && is_numeric(self::$timings[ $key ]['start']) ? self::$timings[ $key ]['start'] : START_MICROTIME;
        $stop    = microtime(true);
        $elapsed = $stop - $start;
        self::$result[] = array( $key, $elapsed );

        unset(self::$timings[$key]);
        foreach (self::$cbList as $fn) {
            call_user_func($fn, $key, $elapsed);
        }
        return $elapsed;
    }

    /**
     * Add a measure of a callable
     *
     * @param $key
     * @param callback
     *
     * @return mixed
     */
    public static function measure($key = null, callable $fn) {
        if (is_null($key)) {
            $r = new ReflectionFunction($fn);
            $key = sprintf("%s closure(%d->%d)", self::getFilename($r->getFilename()), $r->getStartline(), $r->getEndline());
        }

        self::start($key);
        $return = $fn();
        self::stop($key);

        return $return;
    }

    /**
     * Get a normalized array of timings, removing (parameters)
     *
     * @return array
     */
    public static function getTimings($round = 4, $slowThreshold = -INF, callable $cb = null) {
        $return = array();
        foreach (self::$result as $result) {
            list ($key, $elapsed) = $result;
            if ($elapsed > $slowThreshold) {
                $return[$key] = !is_infinite($round) && !is_null($round) ? round($elapsed, $round) : $elapsed;
            }
        }
        if ($cb) { 
            return $cb($return);
        } else {
            return $return;
        }
    }

    /**
     * @return array
     */
    public static function niceTimings(callable $cb = null) {
        return self::getTimings(+INF, -INF ,function($t) {
            foreach ($t as $k => $v) {
                $t[$k] = self::formatDuration($v);
            }
            return $t;
        });
    }
    /**
     * @param float $seconds
     * @return string
     */
    private static function formatDuration($seconds) {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        } else if ($seconds < 60) {
            return round($seconds, 2) . 's';
        } else {
            return date("H:i.s", $seconds);
        }
    }


    /**
     * @return string
     */
    private static function getKey($key = null, $type) {
        if (is_null($key)) {
            $key = self::generateKey($type);
        }
        return $key;
    }

    /**
     * @return string
     */
    private static function getFilename($str) {
        return 1 ? basename($str) : $str;
    }

    /**
     * @return string
     */
    private static function generateKey( $type ) {
        $bt = array_slice(debug_backtrace(),2);

        if (count($bt) == 1) {
            $caller = array_shift($bt);
            return sprintf("%s @ %s:%s", $type, self::getFilename($caller['file']), $caller['line']);

        }
        array_shift($bt);
        $caller = array_shift($bt);
        $line = $caller['line'];
        $where = self::getFilename($caller['file']).':'.$line;

        if ('{closure}' == $caller['function']) {
            $fn = 'function@'.$line;
        } else if (!isset($caller['type'])) {
            $fn = $caller['function'];
        } else {
            $fn = (isset($caller['object']) ? get_class($caller['object']) : $caller['class']) .$caller['type'].$caller['function'];
        }
        $params = array();
        if (isset($caller['args'])) {
            foreach ($caller['args'] as $arg) {
                $params[] = gettype($arg) == 'object' ? get_class($arg) : $arg;
            }
        }
        return sprintf("%s %s(%s)", $where, $fn, implode(', ',$params));
    }

}
