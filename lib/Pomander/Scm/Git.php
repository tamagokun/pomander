<?php
namespace Pomander\Scm;

class Git extends \Pomander\Scm
{
    public function create($location)
    {
        return "git clone {$this->repository} {$location}";
    }

    public function update()
    {
        $cmd = array("git fetch --all --quiet");
        $branch = isset($this->app["branch"])? $this->app["branch"] : $this->app->env->branch;

        if (!empty($branch)) {
            $cmd[] = "git reset --hard --quiet && git checkout {$branch} --quiet";
        } elseif (!empty($this->app->env->revision)) {
            $cmd[] = "git reset --hard {$this->app->env->revision} --quiet";
        } else {
            $cmd[] = 'git reset --hard HEAD --quiet';
        }

        if ($this->app->env->submodule !== false) {
            $cmd[] = 'git submodule update --init --recursive --quiet';
        }

        $cmd[] = 'git log --date=relative --format=format:"%C(bold blue)(%ar)%C(reset) %an \'%s\' %C(bold green)(%h)%C(reset)" | head -1';

        return implode(' && ', $cmd);
    }

    public function revision()
    {
        return "git rev-parse HEAD";
    }
}
