<?php
// app/Helpers/ConsoleHelpers.php

namespace App\Helpers;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
class ConsoleHelpers
{
    public static function dynamicWriteln(OutputInterface $output, $name, $description,$default,$shortcut, $optional )
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

        $file = $input->getOption('file');
        $help = $input->getOption('help');
        if ($help) {
            self::showHelpOptions($output, $options);
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
            self::setupDatabaseConnection($connection, $host, $username, $password);
            self::processCSVUpload($file, $dryRun, $connection);
        } catch (\Throwable $e) {
           self::handleUploadError($e, $connection, $file);
        }
    }

    public static function showHelpOptions(OutputInterface $output, array $options): void {
        $output->writeln('<comment>Usage:</comment>');
        $output->writeln('php script.php user:upload [options]');
        $output->writeln('');
        $output->writeln('<comment>Options:</comment>');
        foreach ($options as $option) {
            ConsoleHelpers::dynamicWriteln($output, $option['name'], $option['description'],$option['default'],$option['shortcut'],$option['mode'] );
        }
    }

    public static function setupDatabaseConnection($connection,$host,$username,$password): void

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

    public static function processCSVUpload($file, $dryRun, $connection)
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

    public static function  handleUploadError($e, $connection, $file)
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

}
