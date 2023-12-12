<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Resources\Console\UpdatedApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Helpers\ConsoleHelpers;

$application = new UpdatedApplication('User Upload Script', '1.0.0');
$application
    ->register('user:create_table')
    ->setDescription('Creates the MySQL users table')
    ->addOption('create_table', null, InputOption::VALUE_NONE, 'Build the MySQL users table')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        handleUserCreate($input, $output);
    });

 $options = [
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
];

$csvFile=null;
$application
    ->register('user:upload')
    ->setDescription('Uploads users from a CSV file to the database')
    ->addOption($options['file']['name'], $options['file']['shortcut'], $options['file']['mode'], $options['file']['description'],  $options['file']['default'])
    ->addOption($options['dry_run']['name'], $options['dry_run']['shortcut'], $options['dry_run']['mode'], $options['dry_run']['description'],  $options['dry_run']['default'])
    ->addOption($options['u']['name'], $options['u']['shortcut'], $options['u']['mode'], $options['u']['description'],  $options['u']['default'])
    ->addOption($options['p']['name'], $options['p']['shortcut'], $options['p']['mode'], $options['p']['description'],  $options['p']['default'])
    ->addOption($options['h']['name'], $options['h']['shortcut'], $options['h']['mode'], $options['h']['description'],  $options['h']['default'])
    ->addOption($options['help']['name'], $options['help']['shortcut'], $options['help']['mode'], $options['help']['description'],  $options['help']['default'])
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($options) {
        ConsoleHelpers::handleUserUpload($input, $output, $options);
    });

function handleUserCreate(InputInterface $input, OutputInterface $output): void
{
    $createTable = $input->getOption('create_table');

    if ($createTable) {
        try {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2014_10_12_000000_create_users_table.php',
            ]);

            $output->writeln('<info>Users table migrated successfully!</info>');
        } catch (\Throwable $e) {
            $output->writeln('<error>Error migrating users table: ' . $e->getMessage() . '</error>');
        }
    } else {
        $output->writeln('<comment>No action specified. Use --create_table to migrate the users table.</comment>');
    }
}


$application->run();
