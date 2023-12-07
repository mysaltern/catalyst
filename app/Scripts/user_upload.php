<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$application = new Application('User Upload Script', '1.0.0');
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



$application
    ->register('user:upload')
    ->setDescription('Uploads users from a CSV file to the database')
    ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Name of the CSV file to be parsed', './users.csv')->setCode(function (InputInterface $input, OutputInterface $output) {
        $file = $input->getOption('file');

        try {
            $csvFile = @fopen($file, 'r');
            if (!$csvFile) {
                fwrite(STDOUT, "Error opening file $file");
            }
            // Begin a transaction
            DB::beginTransaction();

            while (($row = fgetcsv($csvFile)) !== false) {
                $name = ucfirst(strtolower(trim($row[0])));
                $surname = !empty($row[1]) ? ucfirst(strtolower(trim($row[1]))) : null;
                $email = strtolower(trim($row[2]));
                $validator = validator(['email' => $email], ['email' => 'required|email']);

                if ($validator->fails()) {
                    fwrite(STDOUT, "Invalid email format for: $name $surname - $email\n");
                    continue;
                }

                $user = User::firstOrNew(['email' => $email]);

                if (!$user->exists) {
                    $user->fill([
                        'name' => $name,
                        'surname' => $surname,
                    ])->save();
                } else {
                    fwrite(STDOUT, "User with email $email already exists. Skipped insertion.\n");
                }
            }

            // Commit the transaction if all inserts are successful
            DB::commit();
            fclose($csvFile);
        } catch (\Throwable $e) {
            // Rollback the transaction on any error
            DB::rollBack();

            // Log the error message or handle the exception
            Log::error('Error during CSV data insertion: ' . $e->getMessage());

            if ($csvFile && $file)
            {
                fclose($csvFile);
            }
        }
    });

$application->run();
