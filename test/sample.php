<?php
interface basics
{
    public function method1($param1 = 100, $param2 = "");
    public function method2($param1);
}

class base_test
{
    const TEST = 10;
    const CONST2 = "qwe";

    public $name;
    public $defaults = "mydefault";
    public $float = 1.157;
    public static $sname = 1;

    public function __construct($name)
    {
    }
    
    public function __destruct()
    {
    }

    public function method1($param1 = 100, $param2 = "")
    {
    }

    public function method2($param1)
    {
    }

    public static function static_method()
    {
    }
}

class test extends base_test implements basics
{
    public $float = 1.888;
    public static $sname = 4654;
    public $newfloat = 1.888;
    public static $newsname = 4654;
    
    public function __construct(base_test $other)
    {
    }
    
    public function __destruct()
    {
    }

    public function method2($param1, $param2 = null)
    {
    }
    
    public function method3(&$param1, array $param2 = null)
    {
    }
    
    public function method4($fmt, ...$varargs)
    {
    }
}

function global_func(callable $cb, $fmt, ...$varargs)
{
}
?>