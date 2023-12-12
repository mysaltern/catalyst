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
        'description' => 'Execute without inserting into the DB (Optional)',
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
        handleUserUpload($input, $output, $options);
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

function handleUserUpload(InputInterface $input, OutputInterface $output, array $options): void {

    $file = $input->getOption('file');
        $help = $input->getOption('help');
        if ($help) {
            showHelpOptions($output, $options);
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
            setupDatabaseConnection($connection, $host, $username, $password);
            processCSVUpload($file, $dryRun, $connection);
        } catch (\Throwable $e) {
            handleUploadError($e, $connection, $file);
        }
    }

function showHelpOptions(OutputInterface $output, array $options): void {
    $output->writeln('<comment>Usage:</comment>');
    $output->writeln('php script.php user:upload [options]');
    $output->writeln('');
    $output->writeln('<comment>Options:</comment>');
    foreach ($options as $option) {
        ConsoleHelpers::dynamicWriteln($output, $option['name'], $option['description']);
    }
}

function setupDatabaseConnection($connection,$host,$username,$password): void

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

function processCSVUpload($file, $dryRun, $connection)
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

function  handleUploadError($e, $connection, $file)
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

$application->run();
