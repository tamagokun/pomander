<?php
namespace Pomander\Scm;

class Git extends \Pomander\Scm
{
    public function create($location)
    {
        return "git clone -q {$this->repository} {$location}";
    }

    public function update()
    {
        $remote = isset($this->app->env->remote)? $this->app->env->remote : "origin";
        $cmd = array(
            "git fetch -q {$remote}",
            "git fetch --tags -q {$remote}"
        );
        $branch = isset($this->app["branch"])? $this->app["branch"] : $this->app->env->branch;
        if(empty($branch)) $branch = $this->app->env->revision;
        if(empty($branch)) $branch = "HEAD";

        // if specifying a remote ref, just grab the branch name
        if(strpos($branch, "/") !== false) list($remote, $branch) = explode("/", $branch, 2);

        $commit = $this->get_commit_sha($branch);
        $cmd[] = "git reset -q --hard {$commit}";

        if ($this->app->env->submodule !== false) {
            $cmd[] = 'git submodule update --init --recursive --quiet';
        }

        $cmd[] = 'git clean -q -d -x -f';
        $cmd[] = 'git log --date=relative --format=format:"%C(bold blue)(%ar)%C(reset) %an \'%s\' %C(bold green)(%h)%C(reset)" | head -1';

        return implode(' && ', $cmd);
    }

    public function revision()
    {
        return "git rev-parse HEAD";
    }

    public function get_commit_sha($ref)
    {
      list($status, $commit) = run_local("git ls-remote {$this->app->env->repository} {$ref}");
      if($status > 0 || !$commit) return abort("update", "failed to retrieve commit for {$ref}.");

      $commit = array_shift($commit);
      return substr($commit, 0, strpos($commit, "\t"));
    }
}
