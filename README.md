[Pomander](http://ripeworks.com/pomander)
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
$ curl https://gist.github.com/tamagokun/4242494/raw/35dde077b9d614d537b322c191fecf25ec74d1a5/global_composer.sh | sh
```

If you haven't added composer's bin folder to your `$PATH`, better do that now:

```bash
$ echo 'export PATH="$HOME/.composer/bin:$PATH"' >> ~/.bashrc
```

_Substitute .bashrc with whatever you use._

### Installing Pomander

If you are using the global installation method from above, you can easily do:

```bash
$ cd ~/.composer && composer require pomander/pomander
```

Otherwise, you need to add `pomander/pomander` to your project's composer.json:

```json
{
	"require": {
		"pomander/pomander": "*"
	}
}
```

You can also do this using Composer:

```bash
$ composer require pomander/pomander:@stable
```


Setting up a project
--------------------

```bash
$ pom init
```

Refer to the [documentation](http://ripeworks.com/pomander) for a full list of commands and references for configuring environments.


Plugins
-------

* [pomander-wordpress](https://github.com/tamagokun/pomander-wordpress)
