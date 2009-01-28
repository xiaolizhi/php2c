<?php
/*
 * Author: Ondřej Jirman <megous@megous.com>, 2009
 * This codee was released to Public Domain.
 */

/* Yeah, I know my imagination for names is as creative as ever. ;-) */

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

  public function method1($param1 = 100, $param2 = "")
  {
  }

  public function method2($param1, $param2 = null)
  {
  }

  public static function static_method()
  {
  }
}

class test extends base_test implements basics
{
  public function method2($param1)
  {
  }
}

function global_func($arg1)
{
}

?>
