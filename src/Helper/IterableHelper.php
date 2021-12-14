<?php

namespace App\Helper;

abstract class IterableHelper
{
    /**
     * @return mixed|null
     */
    public static function findFn(iterable $iter, callable $callable)
    {
        foreach ($iter as $el) {
            if (call_user_func($callable, $el)) {
                return $el;
            }
        }

        return null;
    }

    /**
     * @return false|int|string
     */
    public static function findKeyFn(iterable $iter, callable $callable)
    {
        foreach ($iter as $k => $el) {
            if (call_user_func($callable, $el)) {
                return $k;
            }
        }

        return false;
    }
}
