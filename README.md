User Upload Script in Laravel

This Laravel project contains a PHP script named user_upload.php to process a CSV file and insert its data into a MySQL database.
Installation and Setup

    Download:
        Clone or download this repository.

    Dependencies:
        Navigate to the project root directory and run composer update to install dependencies.

    Database Setup:
        Create a database for the project (db name : catalyst).

    Configuration:
        Configure the database details in the .env file.

Usage

Run the following commands in the terminal or command prompt:

bash

php user_upload.php user:create_table    # Builds the MySQL users table without further action.
php user_upload.php user:upload          # Executes script functions to parse and insert data.

Script Command Line Directives

The script supports the following command line options (directives):

    --file [csv file name]: Specifies the CSV file to be parsed.
    --create_table: Builds the MySQL users table without further action.
    --dry_run: Executes script functions but avoids altering the database.
    -u: Specifies the MySQL username.
    -p: Specifies the MySQL password.
    -h: Specifies the MySQL host.
    --help: Displays a list of available directives with details.
