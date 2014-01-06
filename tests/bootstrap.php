<?php

require dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/lib/Pomander.php';

abstract class TestCase extends PHPUnit_Framework_TestCase
{
  protected function getFixture($name)
  {
    return __DIR__ . '/fixtures/' . $name;
  }
}
