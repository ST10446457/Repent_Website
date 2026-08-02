<?php
/**
 * ============================================================
 * REPENT† — Database Configuration
 * File: includes/db.php
 * 
 * PURPOSE: Establishes the MySQL database connection.
 * This file is included at the top of every page that 
 * needs to interact with the database.
 *
 * NOTE FOR TEAM: Unarine (Data Administrator) should update
 * the DB_HOST, DB_USER, DB_PASS, and DB_NAME constants below
 * with the actual database credentials once the DB is ready.
 * ============================================================
 */

// ----- Database connection credentials -----
define('DB_HOST', 'localhost');       // Database server (usually localhost)
define('DB_USER', 'root');            // Database username
define('DB_PASS', '');                // Database password
define('DB_NAME', 'repent_db');       // Database name

// ----- Create the connection using MySQLi -----
// The @ suppresses the warning if the database doesn't exist yet
// $conn will be null and the site will run in demo mode instead
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ----- Check if connection failed -----
if ($conn->connect_error) {
    // In production you would log this error instead of displaying it
    // For the presentation we set $conn to null and use demo/session data
    $conn = null;
}
