<?php
if (!$loader = include __DIR__.'/vendor/autoload.php') {
    die('You must set up the project dependencies.');
}
$app = new \Cilex\Application('Cilex');

$app['config.path'] = __DIR__ . '/config.yml';
$app->register(new \Cilex\Provider\ConfigServiceProvider());

// Setup services
$app['buzz'] = new \Buzz\Browser();
$app->register(new \Cilex\Provider\GoogleSpreadsheetServiceProvider());

// Add commands
$app->command(new \Cilex\Command\BugzillaCountCommand());
$app->command(new \Cilex\Command\GithubScoreCommand());
$app->command(new \Cilex\Command\VouchedMozilliansCommand());
$app->command(new \Cilex\Command\MozillaOwnerCommand());

$app->run();
