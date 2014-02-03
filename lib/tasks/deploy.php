<?php

group('deploy', function () {

    desc("Setup application in environment.");
    task('setup','app', function ($app) {
        info('deploy', "setting up environment");
        run($app->env->method->setup());
    });

    desc("Update code to latest changes.");
    task('update','app', function ($app) {
        info('deploy', "updating code");
        run($app->env->method->deploy());
        $app->can_rollback = true;
    });

    task('finalize', 'deploy:cleanup', function ($app) {
        run($app->env->method->finalize());
        $app->env->finalized = true;
        $name = basename($app->env->release_dir);
        info('complete', "deployed $name");
    });

    task('cleanup', function ($app) {
        run($app->env->method->cleanup());
    });

    desc("First time deployment.");
    task('cold','deploy:setup','deploy:update','composer:install','deploy:finalize');

});
task('deploy','deploy:update','composer:install','deploy:finalize');

//rollback
desc("Rollback to the previous release");
task('rollback', 'app', function ($app) {
    run($app->env->method->rollback());
});
