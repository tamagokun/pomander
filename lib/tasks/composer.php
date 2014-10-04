<?php

group('composer', function () {

    desc("Install dependencies with Composer");
    task('install', function ($app) {
        if($app->env->composer === false) return;
        info("composer", "install");
        run(array(
            "cd {$app->env->release_dir}",
            "([ -e 'composer.json' ] && which composer &>/dev/null)",
            "composer install --prefer-dist --optimize-autoloader --no-interaction || echo '    composer.json not found'"
        ));
    });

    task('update', function ($app) {
        if($app->env->composer === false) return;
        info("composer", "update");
        run(array(
            "cd {$app->env->release_dir}",
            "([ -e 'composer.json' ] && which composer &>/dev/null)",
            "composer update --optimize-autoloader --no-interaction || echo '    composer.json not found'"
        ));
    });

});
