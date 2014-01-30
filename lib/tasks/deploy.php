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

    $cmd = array();

    if ($app->env->releases) {
        $releases = run("ls -1t {$app->env->releases_dir}", true);
        if(count($releases) < 2) abort("rollback", "no releases to roll back to.");

        if ($app->env->release_dir == $app->env->current_dir) {
            $count = isset($app['releases'])? $app['releases'] : 1;
            if(count($releases) < $count + 1) abort("rollback", "can't rollback that far.");
            if($count > 1) info("rollback", "rolling back to {$releases[$count]}.");
            info("rollback", "pointing application to previous release.");
            $cmd[] = "ln -nfs {$app->env->releases_dir}/{$releases[$count]} {$app->env->current_dir}";
        } else {
            info("rollback", "removing failed release.");
            $cmd[] = "rm -rf {$app->env->releases_dir}/{$releases[0]}";
            if ($app->env->finalized) {
                info("rollback", "pointing application to last good release.");
                $cmd[] = "ln -nfs {$app->env->releases_dir}/{$releases[1]} {$app->env->current_dir}";
            }
        }
    } else {
        $frozen = run("if test -f {$app->env->release_dir}/REVISION; then echo \"ok\"; fi", true);
        if(empty($frozen)) abort("rollback", "no releases to roll back to.");

        $revision = run("cat {$app->env->release_dir}/REVISION", true);
        if(!count($revision)) abort("rollback", "no releases to roll back to.");

        $app->env->revision = $revision[0];
        $cmd[] = $app->scm->update();
    }

    //if($app->env->merged)
    //{
        //info("rollback", "restoring database to before merge.");
        //$backup = "{$app->env->shared_dir}/backup/{$app->env->merged}";
        //$cmd[] = $app->env->adapter->restore($backup);
        //if($app->env->backup === false) $cmd[] = "rm -rf $backup";
    //}

    run($cmd);

});
