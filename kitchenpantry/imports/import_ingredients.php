<?php
// We are in a command line, so time limits are off!
// But just in case, set it to "unlimited"
set_time_limit(0); 

echo "--- Ingredient Importer ---\n";

// 1. Connect to the Database
// We're in the same folder, so this path is simpler
try {
    require __DIR__ . '/src/config.php'; 
} catch (Exception $e) {
    die("FATAL: Could not connect to database. " . $e->getMessage() . "\n");
}
echo "✓ Connected to database.\n";

// 2. Define the file to import
// Make sure this file is in the SAME folder as this script
$tsv_file_path = __DIR__ . '/ingredients.tsv';

if (!file_exists($tsv_file_path)) {
    die("FATAL: ingredients.tsv not found. Put it in the same folder as this script.\n");
}
echo "✓ Found ingredients.tsv file.\n";

// 3. Prepare the SQL statement
// We're matching CSV columns to our table columns
$sql = "INSERT INTO ingredients (product_name, generic_name, brands, image_url, code) 
        VALUES (?, ?, ?, ?, ?)";
try {
    $stmt = $pdo->prepare($sql);
} catch (PDOException $e) {
    die("FATAL: DATABASE PREPARE FAILED: " . $e->getMessage() . "\n");
}
echo "✓ SQL statement prepared. Opening file...\n";

// 4. Open and read the TSV file
// We use 'r' for "read"
$handle = fopen($tsv_file_path, 'r');
if ($handle === FALSE) {
    die("FATAL: Could not open TSV file.\n");
}

$line_number = 0;
$success_count = 0;
$error_count = 0;

// Tell $pdo to start a "transaction"
// This makes bulk inserts MUCH faster
$pdo->beginTransaction();

// 5. Loop through each line
// We set the separator to a TAB character "\t"
while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
    
    // Skip the header row
    if ($line_number == 0) {
        $line_number++;
        continue;
    }

    // This is the mapping from your TSV
    // (Arrays start at 0)
    $code = $data[0] ?? null;
    $product_name = $data[7] ?? null;
    $generic_name = $data[8] ?? null;
    $brands = $data[12] ?? null;
    $image_url = $data[69] ?? null;

    // We only insert if the product has a name
    if (empty($product_name)) {
        $error_count++;
        continue;
    }

    try {
        $stmt->execute([
            $product_name,
            $generic_name,
            $brands,
            $image_url,
            $code
        ]);
        $success_count++;
    } catch (Exception $e) {
        // Probably a duplicate 'code'
        $error_count++;
    }

    // Print a progress update every 1000 rows
    if ($success_count % 1000 == 0) {
        echo "  ... processed $success_count rows ...\n";
    }

    $line_number++;
}

// 6. Commit all the changes to the database
$pdo->commit();
fclose($handle);

echo "\n--- IMPORT COMPLETE! ---\n";
echo "✓ Successfully imported: $success_count ingredients.\n";
echo "✓ Skipped: $error_count rows (missing name or error).\n";
?>