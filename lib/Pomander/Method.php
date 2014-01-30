<?php
namespace Pomander;

abstract class Method
{
    public $repository, $app;

    public function __construct($repository)
    {
        $this->repository = $repository;
        $this->app = builder()->get_application();
    }

    /**
     * @return string|array
     */
    public function setup()
    {
        $env = $this->app->env;
        if ($env->releases === false) {
            $dir = $env->deploy_to;
            $setup = "rm -rf $dir && {$this->setup_code($dir)}";
        } else {
            $dir = $env->current_dir;
            $setup = "";
            if ($env->remote_cache === true) $setup = $this->setup_code($env->cache_dir);
        }

        return array(
            "umask {$env->umask}",
            "mkdir -p {$env->deploy_to}",
            "[ test -d \"$dir\" ] && ( "
            . abort("setup", "application has already been setup.", false)
            . " ) || ( $setup )"
        );
    }

    /**
     * @return string|array
     */
    public function deploy()
    {
        $env = $this->app->env;
        if ($env->releases === false) {
            $dir = $env->deploy_to;
            $deploy = "{$this->version()} > VERSION && {$this->update_code()}";
        } else {
            $env->release_dir = $env->releases_dir.'/'.$env->new_release();
            if ($env->remote_cache === true) {
                $dir = $env->cache_dir;
                $deploy = "{$this->update_code()} && cp -R {$env->cache_dir} {$env->release_dir}";
            } else {
                $dir = $env->release_dir;
                $deploy = $this->setup_code();
            }
        }

        return array(
            "cd \"$dir\"",
            "( $deploy ) || ( "
            . abort("deploy", "deploy folder not accessible. try running deploy:setup or deploy:cold first.", false)
            . " )"
        );
    }

    /**
     * @return string|array
     */
    public function finalize()
    {
        $env = $this->app->env;
        //if ($env->backup === false) $cmd[] = "rm -rf {$env->shared_dir}/backup/{$env->merged}";
        if ($env->releases === false) return;

        return array(
            "cd \"{$env->releases_dir}\"",
            "current=`ls -1t | head -n 1`",
            "ln -nfs \"{$env->releases_dir}/\$current\" \"{$env->current_dir}\""
        );
    }

    /**
     * @return string|array
     */
    public function cleanup()
    {
        $env = $this->app->env;
        if($env->releases === false) return;
        if($env->keep_releases === false) return;
        $keep = max(1, $env->keep_releases);

        info('deploy', "cleaning up old releases");

        return array(
            "cd \"{$env->releases_dir}\"",
            "count=`ls -1t | wc -l`",
            "old=$((count > {$keep} ? count - {$keep} : 0))",
            "ls -1t | tail -n \$old | xargs rm -rf {}"
        );
    }

    /**
     * @return string|array
     */
    public function rollback()
    {
        $env = $this->app->env;
        if ($env->releases === false) {
            $version = run(array(
                "[ -e \"{$env->release_dir}/VERSION\" ]",
                "(cat \"{$env->release_dir}/VERSION\") &>/dev/null"
            ), true);
            if (!count($version) || empty($version[0])) {
                abort('rollback', "no releases to roll back to");
            }

            $env->revision = $version[0];
            return $this->update_code();
        }

        $rollback_to = isset($this->app['releases'])? $this->app['releases'] : 2;
        $failed = "";

        if ($env->release_dir !== $env->current_dir) {
            // remove broken release
            info('rollback', "removing failed release.");
            $failed = info('rollback', "removing failed release.", false) . " && rm -rf \"{$env->release_dir}\"";
        }

        //if($app->env->merged)
        //{
            //info("rollback", "restoring database to before merge.");
            //$backup = "{$app->env->shared_dir}/backup/{$app->env->merged}";
            //$cmd[] = $app->env->adapter->restore($backup);
            //if($app->env->backup === false) $cmd[] = "rm -rf $backup";
        //}

        return array(
            "count=`ls -1t \"{$env->releases_dir}\" | wc -l`",
            "previous=`ls -1t \"{$env->releases_dir}\" | head -n {$rollback_to} | tail -1`",
            "([ -e \"{$env->releases_dir}/\$previous\" ] && \$count >= {$rollback_to} )",
            "( $failed ",
            info('rollback', "pointing to previous release.", false),
            "ln -nfs {$env->releases_dir}/\$previous {$env->current_dir} ) || ("
            . abort('rollback', "", false)
            . " )"
        );
    }

    /**
     * @param $location
     * @return string
     */
    public function setup_code($location)
    {
        return "";
    }

    /**
     * @return string
     */
    public function update_code()
    {
        return "";
    }

    /**
     * @return string
     */
    public function version()
    {
        return "";
    }
}
