

# User Upload Script in Laravel

This Laravel project includes a PHP script named `user_upload.php` designed to process a CSV file and insert its data into a MySQL database.

## Installation and Setup

1. **Download:**
    - Clone or download this repository.

2. **Dependencies:**
    - Navigate to the project root directory and run `composer update` to install required dependencies.

3. **Database Setup:**
    - Create a database named `catalyst` for the project.

4. **Configuration:**
    - Configure the database details in the `.env` file.

## Usage

Execute the following commands in the terminal or command prompt:

```bash

php user_upload.php user:upload          # Executes script functions to parse and insert data.

example :
php user_upload.php user:upload --file=users.csv --u root -p null -h 127.0.0.1
```

## Script Command Line Directives

The script supports the following command line options (directives):

- `--file [csv file name]`: Specifies the CSV file to be parsed.
- `--create_table`: Builds the MySQL users table without further action.
- `--dry_run`: Executes script functions but avoids altering the database.
- `-u`: Specifies the MySQL username.
- `-p`: Specifies the MySQL password.
- `-h`: Specifies the MySQL host.
- `--help`: Displays a list of available directives with details.

---

Furthermore, the `user_upload.php` script is located in `app/Scripts/user_upload.php` within this project.
