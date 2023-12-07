<?php

// Load Laravel's autoloader to access its functionalities
require_once __DIR__ . '/../../vendor/autoload.php';

// Boot up Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User; // Adjust the namespace according to your model location

// Read the CSV file
$csvFile = fopen('./users.csv', 'r');

// Skip the header row
$header = fgetcsv($csvFile);

// Begin a transaction
DB::beginTransaction();

try {
    while (($row = fgetcsv($csvFile)) !== false) {
        $name = ucfirst(strtolower(trim($row[0])));
        $surname = !empty($row[1]) ? ucfirst(strtolower(trim($row[1]))) : null;
        $email = strtolower(trim($row[2]));
        $validator = validator(['email' => $email], ['email' => 'required|email']);

        // If email is invalid, skip insertion and log an error
        if ($validator->fails()) {
            fwrite(STDOUT, "Invalid email format for: $name $surname - $email\n");
            continue;
        }

        // Check if the user exists based on the email
        $user = User::firstOrNew(['email' => $email]);

        // Insert validated data into the database
        // If the user doesn't exist, create a new user
        if (!$user->exists) {
            $user->fill([
                'name' => $name,
                'surname' => $surname,
            ])->save();
        } else {
            fwrite(STDOUT, "User with email $email already exists. Skipped insertion.\n");        }
    }
    // Commit the transaction if all inserts are successful
    DB::commit();
} catch (\Throwable $e) {
    // Rollback the transaction on any error
    DB::rollBack();

    // Log the error message or handle the exception
    Log::error('Error during CSV data insertion: ' . $e->getMessage());
} finally {
    // Close the CSV file
    fclose($csvFile);
}
