<?php
namespace Pomander\Method;

use Pomander\Method;

/**
 * Class Svn
 * @package Pomander\Method
 */
class Svn extends Method
{
    /**
     * @param $location
     * @return string
     */
    public function setup_code($location)
    {
        return "svn co {$this->repository} {$location} --quiet";
    }

    /**
     * @return string
     */
    public function update_code()
    {
        return "svn update";
    }

    /**
     * @return string
     */
    public function version()
    {
        return "svn info | grep 'Revision:' | cut -f2 -d\\";
    }
}
