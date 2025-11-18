<?php
// Force errors to show
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutes

echo "<h1>Recipe Image Updater</h1>";

// 1. Connect to the Database
try {
    require __DIR__ . '/src/config.php';
} catch (Exception $e) {
    die("<p style='color:red;'>FATAL: Could not connect to database. " . $e->getMessage() . "</p>");
}
echo "<p style'color:green;'>✓ Connected to database.</p>";

// 2. File path
$csv_file_path = __DIR__ . '/recipes.csv'; 
if (!file_exists($csv_file_path)) {
    die("<p style='color:red;'>FATAL: recipes.csv not found.</p>");
}
echo "<p>✓ Found recipes.csv file.</p>";

// 3. Prepare the SQL statement
$sql = "UPDATE recipes SET img_src = ? WHERE url = ?";
try {
    $stmt = $pdo->prepare($sql);
} catch (PDOException $e) {
    die("<p style='color:red;'>FATAL: DATABASE PREPARE FAILED: " . $e->getMessage() . "</p>");
}

echo "<p>✓ SQL statement prepared. Opening file... (This may take a minute)</p>";

// 4. Open and read the CSV
$handle = fopen($csv_file_path, 'r');
$line_number = 0;
$success_count = 0;

// 5. Loop through the file
while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
    if ($line_number == 0) { // Skip header
        $line_number++;
        continue;
    }

    // From the original CSV:
    $url = $data[10];     // The URL is our unique key
    $img_src = $data[14]; // The image source
    
    if (!empty($url) && !empty($img_src)) {
        try {
            $stmt->execute([$img_src, $url]);
            $success_count++;
        } catch (Exception $e) {
            // Ignore errors
        }
    }
    $line_number++;
}

fclose($handle);

echo "<h2>Update Complete!</h2>";
echo "<p style='color:green;'>✓ Updated $success_count recipes with new images.</p>";
?>