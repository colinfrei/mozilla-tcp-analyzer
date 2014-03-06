<?php

namespace Cilex\Provider;

use Cilex\Application;
use Cilex\ServiceProviderInterface;
use Wunderdata\Google\Client;
use Wunderdata\Google\Worksheet;

class GoogleSpreadsheetServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['spreadsheet'] = $app->share(
            function() use ($app) {
                $client = new Client($app['config']['spreadsheet']['googleAccessKey'], $app['buzz']);
                /** @var Worksheet[] $worksheets */
                $worksheets = $client->loadWorksheetsByUrl($app['config']['spreadsheet']['worksheetUrl']);

                $worksheet = $worksheets[0];

                $cellFeed = $client->loadCellFeed($worksheet);

                return array(
                    'client' => $client,
                    'worksheet' => $worksheet,
                    'cellFeed' => $cellFeed
                );
            }
        );
    }
}