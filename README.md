Pomander
=======

A light-weight flexible deployment tool for deploying web applications. This project was inspired by [Capistrano](https://github.com/capistrano/capistrano) and [Vlad the Deployer](http://rubyhitsquad.com/Vlad_the_Deployer.html), as well as being built on top of [Phake](https://github.com/jaz303/phake), a [Rake](http://rake.rubyforge.org/) clone.

This project came out of the need for a way to deploy Wordpress sites to multiple environments easily and without firing up FTP clients, etc. What started as a simple Rakefile, quickly grew into much more, and has been finally abstracted and ported to PHP to be able to fully integrate tasks with your application.

Getting Started
---------------

Make sure you have [composer](http://getcomposer.org/) installed:

```bash
$ curl -s https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```

[_Need help installing composer?_](http://getcomposer.org/doc/00-intro.md#installation-nix)

Installation
------------

I like to install Pomander globally so I can use it in any project.
Unfortunately, Composer does not have a way of doing this by default, 
so here is an easy way to allow global package installtion:

### Setting up Composer for global installation

```bash
$ curl https://raw.github.com/gist/4242494/5d6344d2976e07d051ace18d41fa035113353e90/global_composer.sh | sh
```

### Installing Pomander

If you are using the global installation method from above, you can easily do:

```bash
$ cd ~/.composer && composer require pomander/pomander:dev-master
```

Otherwise, you need to add `pomander/pomander` to your project's composer.json:

```json
{
	"minimum-stability": "dev",
	"require": {
		"pomander/pomander": "dev-master"
	}	
}
```

You can also do this using Composer:

```bash
$ composer require pomander/pomander:dev-master
```

Setting up a project
--------------------

#### Step 1. Create a `deploy/development.php`

```bash
$ pom init
```

Once the file has been created, you will want to fill in the appropriate values.
You can also check out the [options reference](#options-reference) for help.

_Pomander also supports YAML deploy environments, but recommends using php scripts for extra customization._

#### Step 2. Set up environment for deployment

```bash
$ pom staging deploy:setup  # staging is the environment name and uses deploy/staging.php
```

### Step 3. Deploy, and profit.

```bash
$ pom staging deploy
```

Use `pom -T` to see your available tasks.

Options Reference
-----------------------

<dl>
<dt>url</dt>
<dd>Application URL. Used primarily for database migration and isn't always needed.</dd>
<dt>user</dt>
<dd>SSH user for performing remote tasks.</dd>
<dt>repository</dt>
<dd>Repository url.</dd>
<dt>revision</dt>
<dd>Revision/branch to deploy. <em>Default: origin/master, trunk</em></dd>
<dt>scm</dt>
<dd>SCM to use. Currently supports svn and git. <em>Default: git</em></dd>
<dt>releases</dt>
<dd>Use current/releases/shared structure. (true|false|number of releases to keep) <em>Default: false</em></dd>
<dt>adapter</dt>
<dd>Data adapter to use for databases. Currently supports MySQL. <em>Default: mysql</em></dd>
<dt>remote_cache</dt>
<dd>Cache repository for faster deploys. (true|false) <em>Default: true when releases are set</em></dd>
<dt>deploy_to</dt>
<dd>Application is deployed here. <em>Default: cwd</em></dd>
<dt>backup</dt>
<dd>Perform backup (database) on deployments. (true|false). <em>Default: false</em></dd>
<dt>umask</dt>
<dd>Umask to use for remote tasks. <em>Default: 002</em></dd>
<dt>rsync_cmd</dt>
<dd>Command to use for file syncing. <em>Default: rsync</em></dd>
<dt>rsync_flags</dt>
<dd>Extra flags to use for file syncing. <em>Default: -avuzPO --quiet</em></dd>
<dt>app</dt>
<dd>String or Array of application hosts to deploy to.</dd>
<dt>db</dt>
<dd>String or Array of database hosts to deploy to.</dd>
</dl>

### Example standard deployment script:

```php
<?php
  $env->user('deploy')
      ->repository('git@github.com:github/teach.github.com.git')
      ->deploy_to('/var/www/html')
			->releases(true)
			->app(array(
				'node-1.rackspace.com',
				'node-2.rackspace.com'
			))
	;
```

Tasks
-----

```
deploy:setup      # Creates deploy_to folder, and checks out code.
deploy:cold       # Alias for deploy:setup
delpoy:update     # Updates code to current revision/branch.
config            # Attempts to create a default `development.yml` file.
```

### Custom Tasks

Feel free to modify these existing tasks, as well as create your own!

e.g.

```php
<?php

task('my custom task',function($app) {
  info("my task","Hello, World!");
});

after('deploy:update', function($app) {
  warn("pomander","You can use after() / before() to customize tasks");
});
```

Plugins
-------

* [pomander-wordpress](https://github.com/tamagokun/pomander-wordpress)
