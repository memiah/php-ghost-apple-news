# PHP Ghost To Apple News

This project is intended to move items from a Ghost API to Apple News.

## Usage

You must first initialise the database by executing:
`php src/init_sqlite_db.php`

This will create the tables and system parameter that is used by the sync process.

You will then need to update the Config.php file to include your Ghost and Apple News Key and Secrets. View the file for more info.

To run the sync process, just execute:
`php src/sync.php`

sync.php contains all the code that does the following:
1. Retrieve all records from the specified Ghost API
2. For each record check if it already exists in our SQLite database
- If it is still updated, move on to the next item
3. Determine if we need to Post this article as a new one or update an existing article in Apple News and do the appropriate action.
4. Create/update our database record for the article ID and revision ID

## Log Files
Log files are placed under &lt;dir&gt;/logs with the format: <br /> synclog-&lt;sync_dt&gt;-&lt;last_sync_dt&gt;.log

Where:
- sync_dt = The current date (the sync date)
- last_sync_dt = The value stored in the database for when there was a successful sync

Only errors are logged at the moment.
