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
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many rows should be processed')
            ->addOption('column', null, InputOption::VALUE_REQUIRED, 'Which column contains the github account url', 'AE')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'On what row (not id) to start', 2);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $spreadSheet = $this->getApplication()->getService('spreadsheet');
        $buzz = $this->getApplication()->getService('buzz');
        $config = $this->getApplication()->getService('config')['mozillians'];
        $mozillianApiUrl = 'https://mozillians.org/api/v1/users/?app_name=' . $config['apiAppName'] . '&app_key=' . $config['apiKey'];

        $count = 0;
        for ($i = $input->getOption('offset'); $i <= $spreadSheet['worksheet']->getRowCount(); $i++) {
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
            if ($input->getOption('limit') && $count >= $input->getOption('limit')) {
                break;
            }
        }
    }

    private function getMozillianUsername($input)
    {
        if (!$input) {
            return '';
        }

        $urlParts = parse_url($input);

        if ($urlParts['host'] == 'mozillians.org') {
            $pathParts = explode('/', $urlParts['path']);

            $useNextPart = false;
            foreach ($pathParts as $dir) {
                if ($useNextPart) {
                    $input = $dir;

                    break;
                }

                if ($dir == 'u') {
                    $useNextPart = true;
                }
            }
        }

        return $input;
    }
}
