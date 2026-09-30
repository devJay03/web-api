<?php

define('root', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('app', root . 'App' . DIRECTORY_SEPARATOR);
define('view', app . 'Views' . DIRECTORY_SEPARATOR);


spl_autoload_register(function ($class) {
  $file = root . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
  if (file_exists($file)) {
    require_once $file;
  }
});
