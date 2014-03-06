Mozilla tablet contribution program submission analyzer
=======================================================

This is a bunch of command line tools to analyze the data from the submissions to mozilla's tablet contribution program.
This is based on [Cilex](https://github.com/Cilex/Cilex). Most of it is very quick and dirty, since it'll only be used a few times.

Installation
------------

* Install dependencies using composer: `composer install` (see [https://getcomposer.org/download/](https://getcomposer.org/download/) if you don't have composer yet)
* Copy the config.yml.dist file to config.yml and adjust the values

Running
-------

* To get a list of commands, run `php run.php`.
* To get help for a command run `php run.php command-name --help`.
* To run a command, run `php run.php command-name`

