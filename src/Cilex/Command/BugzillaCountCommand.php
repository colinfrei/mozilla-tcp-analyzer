<?php

namespace Cilex\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wunderdata\Google\Cell;

class BugzillaCountCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('colinfrei:tcp:bugzilla-count')
            ->setDescription('Get the number of bugs a user has participated in by bugzilla email')
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'How many rows should be processed')
            ->addOption('column', null, InputOption::VALUE_REQUIRED, 'Which column contains the bugzilla email address', 'AF');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $spreadSheet = $this->getApplication()->getService('spreadsheet');
        $buzz = $this->getApplication()->getService('buzz');

        $bugzillaUserId = $this->getApplication()->getService('config')['bugzilla']['userId'];
        $bugzillaCookie = $this->getApplication()->getService('config')['bugzilla']['cookie'];

        $count = 0;
        for ($i = 1; $i <= $spreadSheet['worksheet']->getRowCount(); $i++) {
            $cell = $spreadSheet['cellFeed']->findCell($input->getOption('column') . $i);

            $email = '';
            if ($cell instanceof Cell) {
                $email = $this->getEmailAddress($cell->getContent());
            }

            if (!$email) {
                $this->outputBugzillacountLine($output, $email);
                continue;
            }

            try {
                $bugzillaResponse = $buzz->get('https://api-dev.bugzilla.mozilla.org/1.3/count?email1=' . $email . '&email1_type=equals_any&email1_assigned_to=1&email1_qa_contact=1&email1_creator?=1&email1_cc=1&email1_comment_creator=1&userid=' . $bugzillaUserId . '&cookie=' . $bugzillaCookie, array('Content-Type' => 'application/json', 'Accept' => 'application/json'));

                $response = json_decode($bugzillaResponse->getContent(), true);

                $this->outputBugzillacountLine($output, $email, $response['data']);
            } catch (\Buzz\Exception\ClientException $e) {
                $this->outputBugzillacountLine($output, 'ERROR WHEN FETCHING DATA');
            }

            $count++;
            if ($input->getOption('count') && $count >= $input->getOption('count')) {
                break;
            }
        }
    }

    private function getEmailAddress($emailInput)
    {
        if (!$emailInput) {
            return '';
        }

        if (filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
            return $emailInput;
        }

        $bugzillaUserUrlPrefix = 'https://bugzilla.mozilla.org/user_profile?login=';
        if (substr($emailInput, 0, strlen($bugzillaUserUrlPrefix)) == $bugzillaUserUrlPrefix) {
            return urldecode(substr($emailInput, strlen($bugzillaUserUrlPrefix)));
        }

        return '';
    }

    private function outputBugzillacountLine(OutputInterface $output, $email = null, $bugCount = null)
    {
        $output->writeln($email . "\t" . $bugCount);
    }
}
