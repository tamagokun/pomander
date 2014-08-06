<?php

$env->app(array(
    "host1",
    "host2",
    "host3"
));

$env->role_count = 0;

desc('test multirole support');
task('check', 'app', function ($app) {
    info("CHECK", $app->env->role_count);
    $app->env->role_count++;
});
