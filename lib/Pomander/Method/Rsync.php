<?php
namespace Pomander\Method;

use Pomander\Method;

/**
 * Class Rsync
 * @package Pomander\Method
 */
class Rsync extends Method
{
    /**
     * @return string|array
     */
    public function deploy()
    {
        $env = $this->app->env;
        if ($env->releases === false) {
            $dir = $env->deploy_to;
        } else {
            $env->release_dir = $env->releases_dir.'/'.$env->new_release();
            $dir = $env->release_dir;
        }

        $env->put("./", $dir);
        return "";
    }

    /**
     * @return string|array
     */
    public function rollback()
    {
        if ($this->app->env->releases === false) {
            abort('rollback', "no releases to roll back to");
        }

        return parent::rollback();
    }

    /**
     * @param $location
     * @return string
     */
    public function setup_code($location)
    {
        return "mkdir -p \"$location\"";
    }

    /**
     * @return string
     */
    public function version()
    {
        return warn("version", "rsync method does not support code versions.", false);
    }
}
