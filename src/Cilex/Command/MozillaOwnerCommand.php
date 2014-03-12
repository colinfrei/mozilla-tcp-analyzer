<?php

namespace Cilex\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Wunderdata\Google\Cell;

class MozillaOwnerCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('colinfrei:tcp:mozilla-owner')
            ->setDescription('Gets mozilla leaders/module owners/peers from https://wiki.mozilla.org/Modules/All')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many rows should be processed')
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'On what row (not id) to start', 2)
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new \Goutte\Client();
        $crawler = $client->request('GET', 'https://wiki.mozilla.org/Modules/All');
        $owners = array();
        $unusualCases = array();
        $crawler->filter('th')->each(function (Crawler $node) use (&$owners, &$unusualCases) {
            if (in_array($node->text(), array('Owner:', 'Peer(s):'))) {
                $node->siblings()->children()->filter('a')->each(function (Crawler $aNode) use (&$owners, &$unusualCases) {
                    $ownerName = $aNode->text();
                    $ownerEmail = parse_url($aNode->attr('href'),  PHP_URL_PATH);

                    if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
                        $unusualCases[$ownerEmail] = $ownerName;

                        return;
                    }

                    if (false !== strpbrk($ownerName, '()\'\"')) {
                        $unusualCases[$ownerEmail] = $ownerName;

                        return;
                    }

                    $owners[$ownerEmail] = $ownerName;
                });
            }
        });


        $spreadSheet = $this->getApplication()->getService('spreadsheet');

        $count = 0;
        for ($i = $input->getOption('offset'); $i <= $spreadSheet['worksheet']->getRowCount(); $i++) {
            $emailCell = $spreadSheet['cellFeed']->findCell('AO' . $i);
            if ($emailCell instanceof Cell) {
                if (array_key_exists($emailCell->getContent(), $owners)) {
                    if (array_key_exists($emailCell->getContent(), $unusualCases)) {
                        unset($unusualCases[$emailCell->getContent()]);
                    }
                    $output->writeln("email\tYES");

                    continue;
                }
            }

            $bugzillaEmailCell = $spreadSheet['cellFeed']->findCell('AF' . $i);
            if ($bugzillaEmailCell instanceof Cell) {
                if (array_key_exists($bugzillaEmailCell->getContent(), $owners)) {
                    if (array_key_exists($bugzillaEmailCell->getContent(), $unusualCases)) {
                        unset($unusualCases[$bugzillaEmailCell->getContent()]);
                    }
                    $output->writeln("bugzilla email\tYES");

                    continue;
                }
            }

            $firstNameCell = $spreadSheet['cellFeed']->findCell('AL' . $i);
            $lastNameCell = $spreadSheet['cellFeed']->findCell('AM' . $i);
            if ($firstNameCell instanceof Cell && $lastNameCell instanceof Cell) {
                $name = $firstNameCell->getContent() . ' ' . $lastNameCell->getContent();
                if (in_array($name, $owners)) {
                    if ($emailCell instanceof Cell && array_key_exists($emailCell->getContent(), $unusualCases)) {
                        unset($unusualCases[$emailCell->getContent()]);
                    }
                    if ($bugzillaEmailCell instanceof Cell && array_key_exists($bugzillaEmailCell->getContent(), $unusualCases)) {
                        unset($unusualCases[$bugzillaEmailCell->getContent()]);
                    }

                    $output->writeln("name\tYES");

                    continue;
                }

                $namesSwitched = $lastNameCell->getContent() . ' ' . $firstNameCell->getContent();
                if (in_array($namesSwitched, $owners)) {
                    if ($emailCell instanceof Cell && array_key_exists($emailCell->getContent(), $unusualCases)) {
                        unset($unusualCases[$emailCell->getContent()]);
                    }
                    if ($bugzillaEmailCell instanceof Cell && array_key_exists($bugzillaEmailCell->getContent(), $unusualCases)) {
                        unset($unusualCases[$bugzillaEmailCell->getContent()]);
                    }

                    $output->writeln("namesSwitched\tYES");

                    continue;
                }
            }

            $output->writeln("\tNO");

            $count++;
            if ($input->getOption('limit') && $count >= $input->getOption('limit')) {
                break;
            }
        }

        $output->writeln('');
        $output->writeln('');
        $output->writeln('');
        $output->writeln('Special cases:');
        foreach (array_unique($unusualCases) as $unusualCase) {
            $output->writeln($unusualCase);
        }
    }
}
