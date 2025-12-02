<?php

$host = "localhost";
$username = "root";
$password = "";
$dbname = "kitchenpantrytest"; // Make sure this is your correct database name
$charset = "utf8mb4";

// This is the "Data Source Name"
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

//NEED THESE TO SEND EMAIL
//If email is changed, you need to generate a new Password 
//(must be gmail)
define("SMTP_USER", "kitchenpantry391@gmail.com");
define("SMTP_PASS", "zcgihkaypvfybdoa");


// IMPORTANT: Do NOT have any "echo" statements in this file.
// An "echo" here will break the header() redirect in your register file.
?>