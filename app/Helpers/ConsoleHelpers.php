<?php
// app/Helpers/ConsoleHelpers.php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
class ConsoleHelpers
{
    private function dynamicWriteln(OutputInterface $output, $name, $description,$default,$shortcut, $optional )
    {
        $optionInfo = "<info>$description</info>";
        if ($optional==1) {
            $optionInfo .= ' (Optional)';
        }
        if ($optional==2) {
            $optionInfo .= ' (Value Required)';
        }
        if ($default) {
            $optionInfo .= " default file is :$default";
        }
        if($shortcut)
        {
            $output->writeln("-$name=$optionInfo");
        }
        else
        {
            $output->writeln("--$name=$optionInfo");
        }

    }




    public static function handleUserUpload(InputInterface $input, OutputInterface $output, array $options): void {

        $instance = new ConsoleHelpers();
        $file = $input->getOption('file');
        $help = $input->getOption('help');
        $create_table = $input->getOption('create_table');
        if ($help) {
            $instance->showHelpOptions($output, $options);
            exit();
        }
        if ($create_table) {

            $instance->handleUserCreate($output);
            exit();
        }
        $dryRun = $input->getOption('dry_run');
        $username = $input->getOption('u');
        $password = $input->getOption('p');
        $host = $input->getOption('h');
        $connection = 'mysql_' . $username;
        if($password=="null")
        {
            $password=null;
        }
        try {
            $instance->setupDatabaseConnection($connection, $host, $username, $password);
            $instance->processCSVUpload($file, $dryRun, $connection);
        } catch (\Throwable $e) {
            $instance->handleUploadError($e, $connection, $file);
        }
    }

    private function showHelpOptions(OutputInterface $output, array $options): void {
        $instance = new ConsoleHelpers();
        $output->writeln('<comment>Usage:</comment>');
        $output->writeln('php user_upload.php user:upload [options]');
        $output->writeln('');
        $output->writeln('<comment>Options:</comment>');
        foreach ($options as $option) {
            $instance->dynamicWriteln($output, $option['name'], $option['description'],$option['default'],$option['shortcut'],$option['mode'] );
        }
    }

    private function setupDatabaseConnection($connection,$host,$username,$password): void

    {
        config(['database.connections.' . $connection => [
            'driver' => 'mysql',
            'host' => $host,
//                'host' => "127.0.0.1",
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'catalyst'),
            'username' => "$username",
            'password' =>"$password",
        ]]);
    }

    private function processCSVUpload($file, $dryRun, $connection): void
    {
        $csvFile = @fopen($file, 'r');
        if (!$csvFile) {
            fwrite(STDOUT, "Error opening file $file");
        }
        // Begin a transaction
        DB::connection($connection)->beginTransaction();
        fgetcsv($csvFile);
        while (($row = fgetcsv($csvFile)) !== false) {
            $name = ucfirst(strtolower(trim($row[0])));
            $surname = !empty($row[1]) ? ucfirst(strtolower(trim($row[1]))) : null;
            $email = strtolower(trim($row[2]));
            $validator = validator(['email' => $email], ['email' => 'required|email']);

            if ($validator->fails()) {
                fwrite(STDOUT, "Invalid email format for: $name $surname - $email\n");
                continue;
            }

            if (!$dryRun) {

                $user = User::on($connection)->firstOrNew(['email' => $email]);

                if (!$user->exists) {
                    $user->fill([
                        'name' => $name,
                        'surname' => $surname,
                    ])->save();
                } else {
                    fwrite(STDOUT, "User with email $email already exists. Skipped insertion.\n");
                }
            }
        }

        // Commit the transaction if all inserts are successful
        DB::connection($connection)->commit();
        fclose($csvFile);
    }

    private function  handleUploadError($e, $connection, $file): void
    {
        DB::connection($connection)->rollBack();

        // Log the error message or handle the exception
        Log::error('Error during CSV data insertion: ' . $e->getMessage());
        fwrite(STDOUT, $e->getMessage());
        $csvFile = @fopen($file, 'r');
        if ($csvFile && $file)
        {
            fclose($csvFile);
        }
    }
    private function handleUserCreate(OutputInterface $output): void
    {
            try {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/2014_10_12_000000_create_users_table.php',
                ]);
                $output->writeln('<info>Users table migrated successfully!</info>');
            } catch (\Throwable $e) {
                $output->writeln('<error>Error migrating users table: ' . $e->getMessage() . '</error>');
            }
    }

}
