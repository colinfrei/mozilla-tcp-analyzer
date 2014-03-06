<?php

namespace Cilex\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wunderdata\Google\Cell;

class VouchedMozilliansCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('colinfrei:tcp:vouched-mozillians')
            ->setDescription('Check what users are vouched mozillians')
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'How many rows should be processed')
            ->addOption('column', null, InputOption::VALUE_REQUIRED, 'Which column contains the github account url', 'AE');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $spreadSheet = $this->getApplication()->getService('spreadsheet');
        $buzz = $this->getApplication()->getService('buzz');
        $config = $this->getApplication()->getService('config')['mozillians'];
        $mozillianApiUrl = 'https://mozillians.org/api/v1/users/?app_name=' . $config['apiAppName'] . '&app_key=' . $config['apiKey'];

        $count = 0;
        for ($i = 1; $i <= $spreadSheet['worksheet']->getRowCount(); $i++) {
            $cell = $spreadSheet['cellFeed']->findCell($input->getOption('column') . $i);

            $userName = '';
            if ($cell instanceof Cell) {
                $userName = $this->getMozillianUsername($cell->getContent());
            }

            if (!$userName) {
                $output->writeln($userName . "\t" . '');

                continue;
            }

            $response = $buzz->get($mozillianApiUrl . '&username=' . $userName);
            $data = json_decode($response->getContent(), true);

            if (count($data['objects']) < 1) {
                $output->writeln($userName . "\t" . 'User not found via API');

                continue;
            }

            $isVouched = $data['objects'][0]['is_vouched'];

            // Output line
            $output->writeln($userName . "\t" . ($isVouched ? 'YES' : 'NO'));

            $count++;
            if ($input->getOption('count') && $count >= $input->getOption('count')) {
                break;
            }
        }
    }

    private function getMozillianUsername($input)
    {
        if (!$input) {
            return '';
        }

        $mozillianUserPagePath = 'https://mozillians.org/u/';
        if (substr($input, 0, strlen($mozillianUserPagePath)) == $mozillianUserPagePath) {
            $input = substr($input, strlen($mozillianUserPagePath));
            $input = rtrim($input, '/');
        }

        return $input;
    }
}
