<?php
/*
 * Author: Ondřej Jirman <megous@megous.com>, 2009
 * This codee was released to Public Domain.
 */

class test2 extends base_test implements basics
{
  public function method2($param1)
  {
  }
}

$t1 = new base_test("xyz");
$t2 = new test("zyx");
$t1->defaults = "asdasd";
$t1->method2(12);
$t1->method1(212, 564);
$t2->method2(456);
$t2->method1(213, 231);

base_test::static_method();

var_dump($t1);
var_dump($t2);

$ref = new ReflectionClass('test');
Reflection::export($ref);
$ref = new ReflectionClass('base_test');
Reflection::export($ref);
$ref = new ReflectionClass('basics');
Reflection::export($ref);

?>
