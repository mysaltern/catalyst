<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Resources\Console\UpdatedApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Helpers\ConsoleHelpers;

$options = [
//    'user_create' => [
//        'help' => [
//            'name' => 'help',
//            'shortcut' => null,
//            'mode' => Symfony\Component\Console\Input\InputOption::VALUE_NONE,
//            'description' => 'Help',
//            'default' => null,
//        ],
//    ],
    'user_upload' => [
        'create_table' =>
        [
            'name' => 'create_table',
            'shortcut' => null,
            'mode' => Symfony\Component\Console\Input\InputOption::VALUE_NONE,
            'description' => 'Builds the MySQL users table without further action.',
            'default' => null,
        ],

        'file' => [
            'name' => 'file',
            'shortcut' => null,
            'mode' => Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
            'description' => 'Name of the CSV file to be parsed',
            'default' => './users.csv',
        ],
        'dry_run' => [
            'name' => 'dry_run',
            'shortcut' => null,
            'mode' => Symfony\Component\Console\Input\InputOption::VALUE_NONE,
            'description' => 'Execute without inserting into the DB',
            'default' => null,
        ],
        'u' => [
            'name' => 'u',
            'shortcut' => 'u',
            'mode' => Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
            'description' => 'MySQL username',
            'default' => null,
        ],
        'p' => [
            'name' => 'p',
            'shortcut' => 'p',
            'mode' => Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
            'description' => 'MySQL password',
            'default' => null,
        ],
        'h' => [
            'name' => 'h',
            'shortcut' => '-h',
            'mode' => Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
            'description' => 'Host',
            'default' => null,
        ],
        'help' => [
            'name' => 'help',
            'shortcut' => null,
            'mode' => Symfony\Component\Console\Input\InputOption::VALUE_NONE,
            'description' => 'Help',
            'default' => null,
        ],
    ],
];

$application = new UpdatedApplication('User Upload Script', '1.0.0');

$csvFile=null;
$application
    ->register('user:upload')
    ->setDescription('Uploads users from a CSV file to the database')
    ->addOption($options['user_upload']['create_table']['name'], $options['user_upload']['create_table']['shortcut'], $options['user_upload']['create_table']['mode'], $options['user_upload']['create_table']['description'])
    ->addOption($options['user_upload']['file']['name'], $options['user_upload']['file']['shortcut'], $options['user_upload']['file']['mode'], $options['user_upload']['file']['description'],  $options['user_upload']['file']['default'])
    ->addOption($options['user_upload']['dry_run']['name'], $options['user_upload']['dry_run']['shortcut'], $options['user_upload']['dry_run']['mode'], $options['user_upload']['dry_run']['description'])
    ->addOption($options['user_upload']['u']['name'], $options['user_upload']['u']['shortcut'], $options['user_upload']['u']['mode'], $options['user_upload']['u']['description'])
    ->addOption($options['user_upload']['p']['name'], $options['user_upload']['p']['shortcut'], $options['user_upload']['p']['mode'], $options['user_upload']['p']['description'])
    ->addOption($options['user_upload']['h']['name'], $options['user_upload']['h']['shortcut'], $options['user_upload']['h']['mode'], $options['user_upload']['h']['description'])
    ->addOption($options['user_upload']['help']['name'], $options['user_upload']['help']['shortcut'], $options['user_upload']['help']['mode'], $options['user_upload']['help']['description'])
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($options) {
        ConsoleHelpers::handleUserUpload($input, $output, $options['user_upload']);
    });
$application->run();
