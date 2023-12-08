<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$application = new \App\Resources\Console\TestApplication('User Upload Script', '1.0.0');
$application
    ->register('user:create_table')
    ->setDescription('Creates the MySQL users table')
    ->addOption('create_table', null, InputOption::VALUE_NONE, 'Build the MySQL users table')
    ->setCode(function (InputInterface $input, OutputInterface $output) {
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




    });

$application->run();
