<?php

namespace Cilex\Command;

use Github\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wunderdata\Google\Cell;

class GithubScoreCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('colinfrei:tcp:github-score')
            ->setDescription('Get the number of participations a user made in mozilla github repos')
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'How many rows should be processed')
            ->addOption('column', null, InputOption::VALUE_REQUIRED, 'Which column contains the github account url', 'AG')
            ->addOption('organisation', 'o', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'What github organisations should be searched for contributions?', array('mozilla', 'mozilla-b2g'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');

        $githubToken = $this->getApplication()->getService('config')['githubAuthToken'];
        if (!$githubToken) {
            $githubToken = $dialog->askHiddenResponse(
                $output,
                'Please enter your github token to get around the rate limiting: '
            );
        }

        $client = new \Github\Client();
        if ($githubToken) {
            $client->authenticate($githubToken, null, Client::AUTH_HTTP_TOKEN);
        }

        $spreadSheet = $this->getApplication()->getService('spreadsheet');

        $count = 0;
        $githubData = array();
        for ($i = 1; $i <= $spreadSheet['worksheet']->getRowCount(); $i++) {
            $cell = $spreadSheet['cellFeed']->findCell($input->getOption('column') . $i);

            $userName = '';
            if ($cell instanceof Cell) {
                $userName = $this->getGithubUsername($cell->getContent());
            }

            if ($userName) {
                $githubData[$userName] = array('contributionCount' => 0, 'repositories' => array(), 'username' => $userName);
            } else {
                $githubData[] = array('contributionCount' => 0, 'repositories' => array(), 'username' => '');
            }

            $count++;
            if ($input->getOption('count') && $count >= $input->getOption('count')) {
                break;
            }
        }

        foreach ($input->getOption('organisation') as $githubOrg) {
            $repositories = $client->api('organization')->repositories($githubOrg);

            foreach ($repositories as $repository) {
                $contributors = $client->api('repo')->contributors($githubOrg, $repository['name']);

                foreach ($contributors as $contributor) {
                    $contributorUsername = $contributor['login'];
                    if (array_key_exists($contributorUsername, $githubData)) {
                        $githubData[$contributorUsername]['contributionCount'] += $contributor['contributions'];
                        $githubData[$contributorUsername]['repositories'][] = $githubOrg . '/' . $repository['name'];
                    }
                }
            }
        }

        foreach ($githubData as $data) {
            $output->writeln($data['username'] . "\t" . ($data['username'] ? $data['contributionCount'] : '') . "\t" . implode(', ', $data['repositories']));
        }
    }

    private function getGithubUsername($input)
    {
        if (!$input) {
            return '';
        }

        $githubPosition = strpos($input, 'github.com');
        if (false !== $githubPosition) {
            $input = substr($input, $githubPosition+11);
            $input = rtrim($input, '/');
        }

        return $input;
    }
}
