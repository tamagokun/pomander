<?php

namespace Pomander\Scm;

use Pomander\Scm;

/**
 * Class Git
 * @package Pomander\Scm
 */
class Git extends Scm
{
    /**
     * @param $location
     * @return string
     */
    public function create($location)
    {
        return "git clone -q {$this->repository} {$location}";
    }

    /**
     * @return string
     */
    public function update()
    {
        $cmd = array();

        // Fetch remote
        $remote = isset($this->app->env->remote)? $this->app->env->remote : "origin";
        $cmd[] = "git fetch -q {$remote}";
        $cmd[] = "git fetch --tags -q {$remote}";

        // Search revision
        if(!empty($this->app->env->revision)) {
            $commit = $this->app->env->revision;
        } else {
            if(!empty($this->app["branch"])) {
                $commit = $this->get_commit_sha($this->app["branch"]);
            } elseif(!empty($this->app->env->branch)) {
                $commit = $this->get_commit_sha($this->app->env->branch);
            } else {
                $commit = 'HEAD';
            }
        }

        // Reset HARD commit
        $cmd[] = "git reset -q --hard {$commit}";

        if ($this->app->env->submodule !== false) {
            $cmd[] = 'git submodule update --init --recursive --quiet';
        }

        $cmd[] = 'git log --date=relative --format=format:"%C(bold blue)(%ar)%C(reset) %an \'%s\' %C(bold green)(%h)%C(reset)" | head -1';

        return implode(' && ', $cmd);
    }

    /**
     * @return string
     */
    public function revision()
    {
        return "git rev-parse HEAD";
    }

    /**
     * @param $ref
     * @return string|void
     */
    public function get_commit_sha($ref)
    {
        // if specifying a remote ref, just grab the branch name
        if(strpos($ref, "/") !== false) {
            $ref = explode("/", $ref);
            $ref = end($ref);
        }

        list($status, $commit) = run_local("git ls-remote {$this->app->env->repository} {$ref}");
        if($status > 0 || !$commit) abort("update", "failed to retrieve commit for {$ref}.");

        $commit = array_shift($commit);
        $commit = substr($commit, 0, strpos($commit, "\t"));

        return $commit;
    }

}
