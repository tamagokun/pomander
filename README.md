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
so here is an easy way to allow global package installation:

### Setting up Composer for global installation

```bash
$ curl https://gist.github.com/raw/4242494/35dde077b9d614d537b322c191fecf25ec74d1a5/global_composer.sh | sh
```

If you haven't added composer's bin folder to your `$PATH`, better do that now:

```bash
$ echo 'export PATH="$HOME/.composer/bin:$PATH"' >> ~/.bashrc
```

_Substitute .bashrc with whatever you use._

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

### Step 1. Create a `deploy/development.php`

```bash
$ pom init
```

Once the file has been created, you will want to fill in the appropriate values.
You can also check out the [options reference](#options-reference) for help.

_Pomander also supports YAML deploy environments, but recommends using php scripts for extra customization._

### Step 2. Set up environment for deployment

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

| *Option* | *Description* |
|----------|---------------|
| url | Application URL. Used primarily for database migration and isn't always needed. |
| user | SSH user for performing remote tasks. |
| repository | Repository url. |
| revision | Revision/branch to deploy. _Default: origin/master, trunk_ |
| branch | Alias of revision. |
| scm | SCM to use. Currently support svn and git. _Default: git_ |
| releases | Use current/releases/shared structure. (true/false/number of releases to keep) _Default: false_ |
| adapter | Data adapter to use for databases. Currently support MySQL _Defauly: mysql_ |
| remote\_cache | Cache repository for faster deploys. (true/false) _Default: true when releases are set_ |
| deploy\_to | Path to deploy application to. _Default: cwd_ |
| backup | Perform database backup on deployments. (true/false). _Default: false_ |
| umask | User's umask for remote tasks. _Default: 002_ |
| rsync\_cmd | Command to use for file syncing. _Default: rsync_ |
| rsync\_flags | Extra flags used for file syncing. _Default: -avuzPO --quiet_ |
| app | String or Array of application hosts to deploy to. |
| db | String or Array of database hosts to deploy to. |

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
config            # Create development environment configuration
db:backup         # Perform a backup suited for merging.
db:create         # Create database.
db:full           # Perform a full database backup.
db:merge          # Merge a backup into environment.
deploy:cold       # First time deployment.
deploy:setup      # Setup application in environment.
delpoy:update     # Update code to latest changes.
init              # Set it up
rollback          # Rollback to previous release
```

### Adding Tasks

Adding tasks is easy, you can drop them right into your environment configurations.

All of the tasks in Pomander are built using [Phake](https://github.com/jaz303/phake). A typical task looks something like this:

```php
<?php
task('task_name', function($app) {
	//task actions
});
```

There are a lot of great things you can do with tasks, so please refer to [Phake's README](https://github.com/jaz303/phake) or the [Pomander Wiki](https://github.com/tamagokun/pomander/wiki).


Plugins
-------

* [pomander-wordpress](https://github.com/tamagokun/pomander-wordpress)
