<?php
// app/Helpers/ConsoleHelpers.php

namespace App\Helpers;

use Symfony\Component\Console\Output\OutputInterface;

class ConsoleHelpers
{
    public static function dynamicWriteln(OutputInterface $output, $name, $description, $optional = false)
    {

        $optionInfo = "<info>$description</info>";
        if ($optional) {
            $optionInfo .= ' (Optional)';
        }
        $output->writeln("-$name=$optionInfo");
    }
}
