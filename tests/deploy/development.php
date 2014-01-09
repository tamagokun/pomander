<?php

$env->test = "hello";

desc('just a test');
task('testing', function ($app) {
  echo $app->env->test;
});
