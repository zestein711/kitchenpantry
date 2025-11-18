<?php
// ========== ADD THESE TWO LINES ==========
// This forces all errors to be displayed
ini_set('display_errors', 1);
error_reporting(E_ALL);
// =========================================

// We need to set a longer execution time
set_time_limit(300); // 5 minutes

echo "<h1>Recipe Importer</h1>";

// 1. Connect to the Database
try {
    // We are trying to load this file:
    // echo "Trying to load: " . __DIR__ . '/src/config.php';
    require __DIR__ . '/src/config.php'; 
    
} catch (Exception $e) {
    die("<p style='color:red;'>FATAL ERROR: Could not connect to database. Check src/config.php. Error: " . $e.getMessage() . "</p>");
}

echo "<p style='color:green;'>Connected to database successfully.</p>";

// 2. Define the file to import
$csv_file_path = 'recipes.csv';

if (!file_exists($csv_file_path)) {
    die("<p style='color:red;'>FATAL ERROR: recipes.csv not found. Make sure it's in the same folder as this import_recipes.php file.</p>");
}

echo "<p style='color:green;'>Found recipes.csv file.</p>";

// 3. Prepare the SQL statement
// (The rest of the script is the same...)
$sql = "INSERT INTO recipes (recipe_name, total_time, servings, ingredients, directions, rating, url) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

try {
    $stmt = $pdo->prepare($sql);
} catch (PDOException $e) {
    die("<p style='color:red;'>DATABASE PREPARE FAILED: " . $e->getMessage() . "</p>");
}

echo "<p>Database statement prepared. Opening CSV file...</p>";

// 4. Open and read the CSV file
$handle = fopen($csv_file_path, 'r');
if ($handle === FALSE) {
    die("<p style='color:red;'>Could not open CSV file for reading.</p>");
}

$line_number = 0;
$success_count = 0;
$error_count = 0;

// 5. Loop through each line of the CSV
while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
    
    if ($line_number == 0) {
        $line_number++;
        continue;
    }
    
    try {
        $recipe_name = $data[1];
        $total_time  = $data[4];
        $servings    = $data[5];
        $ingredients = $data[7];
        $directions  = $data[8];
        $rating      = $data[9];
        $url         = $data[10];

        $stmt->execute([
            $recipe_name,
            $total_time,
            $servings,
            $ingredients,
            $directions,
            $rating,
            $url
        ]);
        
        $success_count++;

    } catch (Exception $e) {
        echo "<p style='color:orange;'>Skipped line $line_number due to error: " . $e->getMessage() . "</p>";
        $error_count++;
    }

    $line_number++;
}

fclose($handle);

echo "<h2>Import Complete!</h2>";
echo "<p style='color:green;'>Successfully imported $success_count recipes.</p>";
echo "<p style='color:orange;'>Skipped $error_count rows due to errors.</p>";

?>