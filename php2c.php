<?php
/*
 * Author: Ondřej Jirman <megous@megous.com>, 2009
 * Revised: Xiao Lizhi <xiaolizhimain@163.com>, 2015
 * This code was released to Public Domain.
 */
 
class PHP2C
{
  public $extname = "";

  public function __construct($extname, $source)
  {
    $this->extname = $extname;
    $this->load($source);
  }

  public function load($source)
  {
    $classes = get_declared_classes();
    $interfaces = get_declared_interfaces();
    $functions = get_defined_functions()['user'];
    $constants = get_defined_constants();

    require_once $source;

    $this->classes = array_diff(get_declared_classes(), $classes);
    $this->interfaces = array_diff(get_declared_interfaces(), $interfaces);
    $this->functions = array_diff(get_defined_functions()['user'], $functions);
    $this->constants = array_diff(get_defined_constants(), $constants);
  }

  public function get_classes()
  {
    $list = array();
    foreach ($this->interfaces as $cl)
      $list[] = new ReflectionClass($cl);
    foreach ($this->classes as $cl)
      $list[] = new ReflectionClass($cl);
    return $list;
  }

  public function get_functions()
  {
    $list = array();
    foreach ($this->functions as $fn)
      $list[] = new ReflectionFunction($fn);
    return $list;
  }

  public function dasherize($string)
  {
    return $string;
  }

  public function get_class_cname(ReflectionClass $class)
  {
    return $this->extname . "_" . $this->dasherize($class->getName());
  }

  public function generate($hfile, $cfile)
  {
    $extname_upper = strtoupper($this->extname);
    $h[] = "/* This code was generated by php2c */";
    $h[] = "";
    $h[] = "#ifndef PHP_{$extname_upper}_H";
    $h[] = "#define PHP_{$extname_upper}_H";
    $h[] = "";
    $h[] = "extern zend_module_entry {$this->extname}_module_entry;";
    $h[] = "#define phpext_{$this->extname}_ptr &{$this->extname}_module_entry";
    $h[] = "";
    $h[] = "#define PHP_{$extname_upper}_VERSION \"0.1.0\"";
    $h[] = "";
    $h[] = "#ifdef PHP_WIN32";
    $h[] = "#	define PHP_{$extname_upper}_API __declspec(dllexport)";
    $h[] = "#elif defined(__GNUC__) && __GNUC__ >= 4";
    $h[] = "#	define PHP_{$extname_upper}_API __attribute__ ((visibility(\"default\")))";
    $h[] = "#else";
    $h[] = "#	define PHP_{$extname_upper}_API";
    $h[] = "#endif";
    $h[] = "";
    $h[] = "#endif";

    $c[] = "/* This code was generated by php2c */";
    $c[] = "";
    $c[] = "/* {{{ Includes */";
    $c[] = "#ifdef HAVE_CONFIG_H";
    $c[] = "#include \"config.h\"";
    $c[] = "#endif";
    $c[] = "";
    $c[] = "#include \"php.h\"";
    $c[] = "#include \"php_ini.h\"";
    $c[] = "#include \"ext/standard/info.h\"";
    $c[] = "#include \"ext/standard/php_var.h\"";
    $c[] = "#include \"{$hfile}\"";
    $c[] = "/* }}} */";
    $c[] = "";
    $c[] = "/* {{{ Class entry pointers */";
    $c[] = "#define _S_L(str) str, sizeof(str) - 1";
    $c[] = "";
    foreach ($this->get_classes() as $class)
      $c[] = "static zend_class_entry *".$this->get_class_cname($class)."_ptr;";
    $c[] = "";
    $c[] = "static zend_class_entry* _find_class(char* name TSRMLS_DC)";
    $c[] = "{";
    $c[] = "  zend_class_entry **pce = NULL;";
    $c[] = "  zend_lookup_class(name, strlen(name), &pce TSRMLS_CC);";
    $c[] = "  return pce ? *pce : NULL;";
    $c[] = "}";
    $c[] = "/* }}} */";
    foreach ($this->get_classes() as $class)
    {
      if ($class->isInterface())
        continue;

      $ce_ptr = $this->get_class_cname($class)."_ptr";
      $c[] = "";
      $c[] = "/* {{{ Methods for class ".$class->getName()." */";      
      
      foreach ($class->getMethods() as $method)
      {
        $params = array();
        foreach ($method->getParameters() as $param)
        {
          $pstr = "";
          if ($param->getClass())
            $pstr = $param->getClass()->getName()." ";
          elseif ($param->isArray())
            $pstr = "array ";
          elseif ($param->isVariadic())
            $pstr = "...";
          elseif ($param->isCallable())
            $pstr = "callable ";
          $pstr.= '$'.$param->getName();
          $params[] = $pstr;
        }
        $c[] = "// " . implode(" ", Reflection::getModifierNames($method->getModifiers())) . " function " . $method->getName() . "(" . implode(', ', $params) . ")";

        $c[] = "ZEND_METHOD(".$class->getName().", ".$method->getName().")";
        $c[] = "{";
        $zp_requied_arg_cnt = $method->getNumberOfRequiredParameters();
        $zp_fmt = array();
        $zp_args = array();
        $zp_defs = array();
        foreach ($method->getParameters() as $param)
        {
          $pname = "a_".$param->getName();
          if ($param->getClass())
          {
            if (count($zp_fmt) == $zp_requied_arg_cnt)
                $zp_fmt[] = "|";
            $zp_fmt[] = "O";
            $zp_args[] = "&".$pname;
            $zp_args[] = "_find_class(\"" . $param->getClass()->getName() . "\" TSRMLS_CC)";
            $zp_defs[] = "zval* ".$pname."=NULL";
          }
          elseif ($param->isArray())
          {
            if (count($zp_fmt) == $zp_requied_arg_cnt)
                $zp_fmt[] = "|";
            $zp_fmt[] = "a";
            $zp_args[] = "&".$pname;
            $zp_defs[] = "zval* ".$pname."=NULL";
          }
          elseif ($param->isVariadic())
          {
            if (count($zp_fmt) == $zp_requied_arg_cnt)
                $zp_fmt[] = "|";
            $zp_fmt[] = "*";
            $zp_args[] = "&".$pname;
            $zp_args[] = "&".$pname."_cnt";
            $zp_defs[] = "zval*** ".$pname."=NULL";
            $zp_defs[] = "int ".$pname."_cnt=0";
            $zp_defs[] = "int ".$pname."_i=0";
          }
          else
          {
            if (count($zp_fmt) == $zp_requied_arg_cnt)
                $zp_fmt[] = "|";
            $zp_fmt[] = "z";
            $zp_args[] = "&".$pname;
            $zp_defs[] = "zval* ".$pname."=NULL";
            /*
            $zp_fmt[] = "s";
            $zp_args[] = "&".$pname."=NULL";
            $zp_args[] = "&". $pname."_len=0";
            $zp_defs[] = "char* ".$pname."=NULL";
            $zp_defs[] = "int ".$pname."_len=0";
             */
          }
        }
        foreach ($zp_defs as $d)
          $c[] = "  $d;";
        if (!empty($zp_defs))
          $c[] = "";

        if (!$method->isStatic())
        {
          $c[] = "  if (!this_ptr || !instanceof_function(Z_OBJCE_P(this_ptr), $ce_ptr TSRMLS_CC)) {";
          $c[] = "    zend_error(E_ERROR, \"%s() cannot be called statically\", get_active_function_name(TSRMLS_C));";
          $c[] = "    return;";
          $c[] = "  }";
          $c[] = "";
        }
        if (!empty($zp_fmt))
        {
          $c[] = "  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, \"".implode("", $zp_fmt)."\", ".implode(", ", $zp_args).") == FAILURE)";
          $c[] = "    return;";
          $c[] = "";
        } else {

            $c[] = "  if (zend_parse_parameters_none() == FAILURE) {";
            $c[] = "    return;";
            $c[] = "  }";
            $c[] = "";
        }

        $c[] = "  const char* space=NULL;";
        $c[] = "  php_printf(\"method %s::%s was called\\n\", get_active_class_name(&space TSRMLS_CC), get_active_function_name(TSRMLS_C));";
        foreach ($method->getParameters() as $index => $param)
        {
          $pname = "a_".$param->getName();
          if (!$param->isVariadic())
          {
            if ($index >= $zp_requied_arg_cnt)
            {
                $c[] = "  if ($pname)";
                $c[] = "    php_var_dump(&$pname, 1 TSRMLS_CC);";
            }
            else
            {
                $c[] = "  php_var_dump(&$pname, 1 TSRMLS_CC);";
            }
          }
          else
          {
            $pname_cnt = $pname."_cnt";
            $pname_i = $pname."_i";
            $c[] = "  if ($pname && $pname_cnt > 0){";
            $c[] = "    for($pname_i=0;$pname_i<$pname_cnt;++$pname_i)";
            $c[] = "      php_var_dump({$pname}[$pname_i], 1 TSRMLS_CC);";
            $c[] = "    efree($pname);";
            $c[] = "  }";
          }
        }
        $c[] = "  RETURN_FALSE;";
        $c[] = "}";
        $c[] = "";
      }
      $c[] = "/* }}} */";
    }

    $c[] = "";
    $c[] = "/* {{{ Global functions */";    
    foreach ($this->get_functions() as $func)
    {
        $params = array();
        foreach ($func->getParameters() as $param)
        {
          $pstr = "";
          if ($param->getClass())
            $pstr = $param->getClass()->getName()." ";
          elseif ($param->isArray())
            $pstr = "array ";
          elseif ($param->isVariadic())
            $pstr = "...";
          elseif ($param->isCallable())
            $pstr = "callable ";
          $pstr.= '$'.$param->getName();
          $params[] = $pstr;
        }

        $c[] = "// function " . $func->getName() . "(" . implode(', ', $params) . ")";        
        $c[] = "ZEND_FUNCTION(".$func->getName().")";
        $c[] = "{";
        $zp_requied_arg_cnt = $func->getNumberOfRequiredParameters();
        $zp_fmt = array();
        $zp_args = array();
        $zp_defs = array();
        foreach ($func->getParameters() as $param)
        {
          $pname = "a_".$param->getName();
          if ($param->getClass())
          {
            if (count($zp_fmt) == $zp_requied_arg_cnt)
                $zp_fmt[] = "|";
            $zp_fmt[] = "O";
            $zp_args[] = "&".$pname;
            $zp_args[] = "_find_class(\"" . $param->getClass()->getName() . "\" TSRMLS_CC)";
            $zp_defs[] = "zval* ".$pname."=NULL";
          }
          elseif ($param->isArray())
          {
            if (count($zp_fmt) == $zp_requied_arg_cnt)
                $zp_fmt[] = "|";
            $zp_fmt[] = "a";
            $zp_args[] = "&".$pname;
            $zp_defs[] = "zval* ".$pname."=NULL";
          }
          elseif ($param->isVariadic())
          {
            if (count($zp_fmt) == $zp_requied_arg_cnt)
                $zp_fmt[] = "|";
            $zp_fmt[] = "*";
            $zp_args[] = "&".$pname;
            $zp_args[] = "&".$pname."_cnt";
            $zp_defs[] = "zval*** ".$pname."=NULL";
            $zp_defs[] = "int ".$pname."_cnt=0";
            $zp_defs[] = "int ".$pname."_i=0";
          }
          else
          {
            if (count($zp_fmt) == $zp_requied_arg_cnt)
                $zp_fmt[] = "|";
            $zp_fmt[] = "z";
            $zp_args[] = "&".$pname;
            $zp_defs[] = "zval* ".$pname."=NULL";
            /*
            $zp_fmt[] = "s";
            $zp_args[] = "&".$pname."=NULL";
            $zp_args[] = "&". $pname."_len=0";
            $zp_defs[] = "char* ".$pname."=NULL";
            $zp_defs[] = "int ".$pname."_len=0";
             */
          }
        }
        foreach ($zp_defs as $d)
          $c[] = "  $d;";
        if (!empty($zp_defs))
          $c[] = "";

        if (!empty($zp_fmt))
        {
            $c[] = "  if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, \"".implode("", $zp_fmt)."\", ".implode(", ", $zp_args).") == FAILURE)";
            $c[] = "    return;";
            $c[] = "";
        } else {
            $c[] = "  if (zend_parse_parameters_none() == FAILURE) {";
            $c[] = "    return;";
            $c[] = "  }";
            $c[] = "";
        }

        $c[] = "  php_printf(\"function %s was called\\n\", get_active_function_name(TSRMLS_C));";
        foreach ($func->getParameters() as $index => $param)
        {
          $pname = "a_".$param->getName();
          if (!$param->isVariadic())
          {
            if ($index >= $zp_requied_arg_cnt)
            {
                $c[] = "  if ($pname)";
                $c[] = "    php_var_dump(&$pname, 1 TSRMLS_CC);";
            }
            else
            {
                $c[] = "  php_var_dump(&$pname, 1 TSRMLS_CC);";
            }
          }
          else
          {
            $pname_cnt = $pname."_cnt";
            $pname_i = $pname."_i";
            $c[] = "  if ($pname && $pname_cnt > 0){";
            $c[] = "    for($pname_i=0;$pname_i<$pname_cnt;++$pname_i)";
            $c[] = "      php_var_dump({$pname}[$pname_i], 1 TSRMLS_CC);";
            $c[] = "    efree($pname);";
            $c[] = "  }";
          }
        }
        $c[] = "  RETURN_FALSE;";
        $c[] = "}";
        $c[] = "";
    }
    $c[] = "/* }}} */";

    $c[] = "";
    $c[] = "/* {{{ Method tables for classes */";    
    foreach ($this->get_classes() as $class)
    {
      /* generate arginfos */
      foreach ($class->getMethods() as $method)
      {
        $ainame = "ai_".$this->get_class_cname($class)."_".$method->getName();
        if (version_compare( PHP_VERSION, '5.3.0', '<'))
            $c[] = "static";

        $c[] = "ZEND_BEGIN_ARG_INFO_EX({$ainame}, 0, ".($method->returnsReference() ? 1 : 0).", ".($method->getNumberOfRequiredParameters()).")";
        foreach ($method->getParameters() as $param)
        {
          $name = $param->getName();
          $ref = $param->isPassedByReference() ? 1 : 0;
          $null = $param->allowsNull() ? 1 : 0;
          if ($param->getClass())
            $c[] = "  ZEND_ARG_OBJ_INFO({$ref}, {$name}, ".$param->getClass()->getName().", {$null})";
          elseif ($param->isArray())
            $c[] = "  ZEND_ARG_ARRAY_INFO({$ref}, {$name}, {$null})";
          elseif ($param->isVariadic())
            $c[] = "  ZEND_ARG_VARIADIC_INFO({$ref}, {$name})";
          else
            $c[] = "  ZEND_ARG_INFO({$ref}, {$name})";
        }
        $c[] = "ZEND_END_ARG_INFO()";
        $c[] = "";
      }

      /* generate method table */
      $c[] = "static zend_function_entry ".$this->get_class_cname($class)."_functions[] = {";
      foreach ($class->getMethods() as $method)
      {
        $ainame = "ai_".$this->get_class_cname($class)."_".$method->getName();
        $flags = array();
        if ($method->isStatic())
          $flags[] = "ZEND_ACC_STATIC";
        if ($method->isAbstract())
          $flags[] = "ZEND_ACC_ABSTRACT";
        if ($method->isFinal())
          $flags[] = "ZEND_ACC_FINAL";
        if ($method->isPublic())
          $flags[] = "ZEND_ACC_PUBLIC";
        if ($method->isPrivate())
          $flags[] = "ZEND_ACC_PRIVATE";
        if ($method->isProtected())
          $flags[] = "ZEND_ACC_PROTECTED";
        $flags = empty($flags) ? "0" : implode("|", $flags);

        if ($class->isInterface())
          $c[] = sprintf("  ZEND_ABSTRACT_ME(%s %s %s)", $class->getName().",", $method->getName().",", $ainame);
        else
          $c[] = sprintf("  ZEND_ME(%s %s %s %s)", $class->getName().",", $method->getName().",", $ainame.",", $flags);
      }
      $c[] = "  ZEND_FE_END";
      $c[] = "};";
      $c[] = "";
    }
    $c[] = "/* }}} */";

    $c[] = "";
    $c[] = "/* {{{ Function table */";    
    /* generate arginfos */
    foreach ($this->get_functions() as $func)
    {
      $ainame = "ai_".$func->getName();
      if (version_compare( PHP_VERSION, '5.3.0', '<'))
          $c[] = "static";
      $c[] = "ZEND_BEGIN_ARG_INFO_EX({$ainame}, 0, ".($func->returnsReference() ? 1 : 0).", ".($func->getNumberOfRequiredParameters()).")";
      foreach ($func->getParameters() as $param)
      {
        $name = $param->getName();
        $ref = $param->isPassedByReference() ? 1 : 0;
        $null = $param->allowsNull() ? 1 : 0;
        if ($param->getClass())
          $c[] = "  ZEND_ARG_OBJ_INFO({$ref}, {$name}, ".$param->getClass()->getName().", {$null})";
        else if ($param->isArray())
          $c[] = "  ZEND_ARG_ARRAY_INFO({$ref}, {$name}, {$null})";
        elseif ($param->isVariadic())
          $c[] = "  ZEND_ARG_VARIADIC_INFO({$ref}, {$name})";
        else
          $c[] = "  ZEND_ARG_INFO({$ref}, {$name})";
      }
      $c[] = "ZEND_END_ARG_INFO()";
      $c[] = "";
    }

    /* generate method table */
    $c[] = "static zend_function_entry {$this->extname}_functions[] = {";
    foreach ($this->get_functions() as $func)
    {
      $ainame = "ai_".$func->getName();
      $c[] = sprintf("  ZEND_FE(%s %s)", $func->getName().",", $ainame);
    }
    $c[] = "  ZEND_FE_END";
    $c[] = "};";
    $c[] = "";
    $c[] = "/* }}} */";
    $c[] = "";
    $c[] = "/* {{{ Init */";    
    $c[] = "PHP_MINIT_FUNCTION({$this->extname})";
    $c[] = "{";
    $c[] = "  zend_class_entry _ce;";
    $c[] = "  ";
    foreach ($this->get_classes() as $class)
    {
      $class_ptr = $this->get_class_cname($class)."_ptr";
      $c[] = "  INIT_CLASS_ENTRY(_ce, \"".$class->getName()."\", ".$this->get_class_cname($class)."_functions);";
      if (!$class->isInterface())
      {
        if ($parent = $class->getParentClass())
          $c[] = "  $class_ptr = zend_register_internal_class_ex(&_ce, NULL, \"".$parent->getName()."\" TSRMLS_CC);";
        else
          $c[] = "  $class_ptr = zend_register_internal_class(&_ce TSRMLS_CC);";
      }
      else
      {
        $c[] = "  $class_ptr = zend_register_internal_interface(&_ce TSRMLS_CC);";
      }
      foreach ($class->getInterfaces() as $iface)
      {
        // do we implement this interface?
        if (in_array($iface->getName(), $this->interfaces))
        {
          $iface_ptr = $this->get_class_cname($iface)."_ptr";
          $c[] = "  zend_do_implement_interface($class_ptr, $iface_ptr TSRMLS_CC);";
        }
        else
        {
          $c[] = "  if ((_if = _find_class(\"".$iface->getName()."\" TSRMLS_CC)))";
          $c[] = "    zend_do_implement_interface($class_ptr, _if TSRMLS_CC);";
        }
      }

      $defaults = $class->getDefaultProperties();
      foreach ($class->getProperties() as $prop)
      {
        $name = $prop->getName();
        $flags = array();
        if ($prop->isStatic())
          $flags[] = "ZEND_ACC_STATIC";
        if ($prop->isPublic())
          $flags[] = "ZEND_ACC_PUBLIC";
        if ($prop->isPrivate())
          $flags[] = "ZEND_ACC_PRIVATE";
        if ($prop->isProtected())
          $flags[] = "ZEND_ACC_PROTECTED";
        $flags = empty($flags) ? "0" : implode("|", $flags);

        if ($prop->isDefault())
        {
          $value = $defaults[$name];

          if (is_int($value))
            $c[] = "  zend_declare_property_long($class_ptr, _S_L(\"$name\"), $value, $flags TSRMLS_CC);";
          else if (is_null($value))
            $c[] = "  zend_declare_property_null($class_ptr, _S_L(\"$name\"), $flags TSRMLS_CC);";
          else if (is_bool($value))
            $c[] = "  zend_declare_property_bool($class_ptr, _S_L(\"$name\"), ".($value ? 1 : 0).", $flags TSRMLS_CC);";
          else if (is_float($value))
            $c[] = "  zend_declare_property_double($class_ptr, _S_L(\"$name\"), $value, $flags TSRMLS_CC);";
          else if (is_string($value))
            $c[] = "  zend_declare_property_string($class_ptr, _S_L(\"$name\"), \"".addcslashes($value, "\0..\37!@\177..\377")."\", $flags TSRMLS_CC);";
          /*
          else if (is_array($value))
          {
            if (!empty($value))
              echo "unsupported non-empty array property type: $name\n";
            $c[] = "  MAKE_STD_ZVAL(_val); array_init(_val);";
            $c[] = "  zend_declare_property($class_ptr, _S_L(\"$name\"), _val, $flags TSRMLS_CC);";
          } */
          else
            echo "unsupported property type: $name\n";
        }
      }
      foreach ($class->getConstants() as $name => $value)
      {
        if (is_int($value))
          $c[] = "  zend_declare_class_constant_long($class_ptr, _S_L(\"$name\"), $value TSRMLS_CC);";
        else if (is_null($value))
          $c[] = "  zend_declare_class_constant_null($class_ptr, _S_L(\"$name\") TSRMLS_CC);";
        else if (is_bool($value))
          $c[] = "  zend_declare_class_constant_bool($class_ptr, _S_L(\"$name\"), ".($value ? 1 : 0)." TSRMLS_CC);";
        else if (is_float($value))
          $c[] = "  zend_declare_class_constant_double($class_ptr, _S_L(\"$name\"), $value TSRMLS_CC);";
        else if (is_string($value))
          $c[] = "  zend_declare_class_constant_string($class_ptr, _S_L(\"$name\"), \"".addcslashes($value, "\0..\37!@\177..\377")."\" TSRMLS_CC);";
        else
          echo "unsupported constant type: $name\n";
      }
      $c[] = "";
    }
    $c[] = "  return SUCCESS;";
    $c[] = "}";
    $c[] = "/* }}} */";
    $c[] = "";
    $c[] = "/* {{{ Shutdown */";
    $c[] = "PHP_MSHUTDOWN_FUNCTION({$this->extname})";
    $c[] = "{";
    $c[] = "  return SUCCESS;";
    $c[] = "}";
    $c[] = "/* }}} */";
    $c[] = "";
    $c[] = "/* {{{ Request Init */";
    $c[] = "PHP_RINIT_FUNCTION({$this->extname})";
    $c[] = "{";
    $c[] = "  return SUCCESS;";
    $c[] = "}";
    $c[] = "/* }}} */";
    $c[] = "";
    $c[] = "/* {{{ Request Shutdown */";
    $c[] = "PHP_RSHUTDOWN_FUNCTION({$this->extname})";
    $c[] = "{";
    $c[] = "  return SUCCESS;";
    $c[] = "}";
    $c[] = "/* }}} */";
    $c[] = "";
    $c[] = "/* {{{ Info */";
    $c[] = "PHP_MINFO_FUNCTION({$this->extname})";
    $c[] = "{";
    $c[] = "  php_info_print_table_start();";
    $c[] = "  php_info_print_table_header(2, \"{$this->extname}\", \"enabled\");";
    $c[] = "  php_info_print_table_row(2, \"Version\", PHP_{$extname_upper}_VERSION);";
    $c[] = "  php_info_print_table_end();";
    $c[] = "}";
    $c[] = "/* }}} */";
    $c[] = "";
    $c[] = "/* {{{ Module entry */";
    $c[] = "zend_module_entry {$this->extname}_module_entry = {";
    $c[] = "  STANDARD_MODULE_HEADER,";
    $c[] = "  \"{$this->extname}\",";
    $c[] = "  {$this->extname}_functions,";
    $c[] = "  PHP_MINIT({$this->extname}),";
    $c[] = "  PHP_MSHUTDOWN({$this->extname}),";
    $c[] = "  NULL, //PHP_RINIT(sample),";
    $c[] = "  NULL, //PHP_RSHUTDOWN(sample),";
    $c[] = "  PHP_MINFO({$this->extname}),";
    $c[] = "  PHP_{$extname_upper}_VERSION,";
    $c[] = "  STANDARD_MODULE_PROPERTIES";
    $c[] = "};";
    $c[] = "";
    $c[] = "#ifdef COMPILE_DL_{$extname_upper}";
    $c[] = "ZEND_GET_MODULE({$this->extname})";
    $c[] = "#endif";
    $c[] = "/* }}} */";
    
    if (!is_dir("project"))
        mkdir("project");
    file_put_contents("project/$hfile", implode("\n", $h)."\n");
    file_put_contents("project/$cfile", implode("\n", $c)."\n");

    $len = $this->extname;
    $uen = $extname_upper;
    file_put_contents("project/config.m4", "PHP_ARG_ENABLE($len, [whether to enable $len support],
[  --enable-$len          Enable $len support])

if test \"\$PHP_$uen\" = \"yes\"; then
  PHP_NEW_EXTENSION($len, $cfile,\$ext_shared,,\$P2C_CFLAGS)
fi
");
    file_put_contents("project/config.w32", "ARG_ENABLE(\"$len\", \"enable $len support\", \"no\");
if (PHP_$uen != \"no\") {
    EXTENSION(\"$len\", \"$cfile\");
}");
  }
}

$extname = "";
$phpfile = "";
foreach ($argv as $arg)
{
    if (false !== stripos($arg, "--extname="))
        $extname = trim(substr($arg, strlen("--extname=")));
    elseif (false !== stripos($arg, "--phpfile="))
        $phpfile = trim(substr($arg, strlen("--phpfile=")));
}
if (!isset($extname[0]) || !isset($phpfile[0]))
{
    echo "Usage:\nphp php2c.php --extname=extname --phpfile=phpfile";
    exit(1);
}

$compiler = new PHP2C($extname, $phpfile);
$compiler->generate("php_$extname.h", "php_$extname.c");
?>