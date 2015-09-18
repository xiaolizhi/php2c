<?php
/*
 * Author: Ondřej Jirman <megous@megous.com>, 2009
 * Revised: Xiao Lizhi <xiaolizhimain@163.com>, 2015
 * This code was released to Public Domain.
 */

class test2 extends base_test implements basics
{
    public $float = 1.999;
    public static $sname = 7897;
    public $newfloat = 1.999;
    public static $newsname = 7897;
    public function __construct($name)
    {
        parent::__construct($name);

        echo "method " . __METHOD__ . " was called\n";
        var_dump($name);
    }
    public function __destruct()
    {
        echo "method " . __METHOD__ . " was called\n";
        
        parent::__destruct();
    }
    
    public function method2($param1)
    {
        echo "method " . __METHOD__ . " was called\n";
        var_dump($param1);
    }
    public function method3($param1, $param2 = "I AM DEF")
    {
        echo "method " . __METHOD__ . " was called\n";
        var_dump($param1);
        var_dump($param2);
    }
}

echo "=============\r\n";
var_dump(global_func("print", "hello"));

echo "=============\r\n";
var_dump(global_func("print", "%s,%s,%s", 12, 23, 45));

echo "=============\r\n";
$t1 = new base_test("xyz");

echo "=============\r\n";
$t2 = new test($t1);

echo "=============\r\n";
$t3 = new test2("abc");

$t1->defaults = "asdasd";
$t2->defaults = "123434";
$t3->defaults = "sfsfsf";

echo "=============\r\n";
var_dump($t1);

echo "=============\r\n";
var_dump($t2);

echo "=============\r\n";
var_dump($t3);

echo "=============\r\n";
$t1->method1(112, NULL);

echo "=============\r\n";
$t1->method1(113);

echo "=============\r\n";
$t1->method2(111);

echo "=============\r\n";
$t1 = NULL;

echo "=============\r\n";
$t2->method1(223);

echo "=============\r\n";
$t2->method2(222);

echo "=============\r\n";
$num = 225;
$t2->method3($num);

echo "=============\r\n";
$t2->method4("%s,%s,%s", 1, 2, 3);

echo "=============\r\n";
$t2 = NULL;

echo "=============\r\n";
$t3->method1(334);

echo "=============\r\n";
$t3->method2(333);

echo "=============\r\n";
$t3->method3(336);

echo "=============\r\n";
$t3 = NULL;

echo "=============\r\n";
base_test::static_method();

echo "=============\r\n";
test::static_method();

echo "=============\r\n";
test2::static_method();

echo "=============\r\n";
$ref = new ReflectionClass('basics');
Reflection::export($ref);

echo "=============\r\n";
$ref = new ReflectionClass('base_test');
Reflection::export($ref);

echo "=============\r\n";
$ref = new ReflectionClass('test');
Reflection::export($ref);

echo "=============\r\n";
$ref = new ReflectionClass('test2');
Reflection::export($ref);
?>
