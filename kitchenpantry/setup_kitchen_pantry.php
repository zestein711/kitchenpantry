<?php
// ==========================================
// KITCHEN PANTRY - MASTER SETUP SCRIPT
// ==========================================
// This script automates the entire database setup, import, and processing.
// RUN THIS FROM THE TERMINAL ONLY.

// Ensure we have unlimited time and memory
set_time_limit(0);
ini_set('memory_limit', '2048M');

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("ERROR: This script must be run from the command line (Terminal).\n");
}

echo "\n=============================================\n";
echo "   KITCHEN PANTRY - FULL SYSTEM SETUP\n";
echo "=============================================\n";

// --- 1. CONFIGURATION & CONNECTION ---
echo "\n[1/6] Connecting to database...\n";
$config_path = __DIR__ . '/src/config.php';
if (!file_exists($config_path)) {
    die("FATAL: src/config.php not found.\n");
}
try {
    require $config_path;
    // Enable exception mode explicitly
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("FATAL: Connection failed. " . $e->getMessage() . "\n");
}
echo "✓ Connected successfully.\n";

// --- 2. DATABASE SCHEMA RESET ---
echo "\n[2/6] Resetting Database Schema...\n";

// Disable FK checks to allow dropping tables
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

$tables = [
    'users' => "
        CREATE TABLE users (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            username VARCHAR(255) UNIQUE,
            email VARCHAR(255) UNIQUE,
            password VARCHAR(255)
        )",
    'ingredients' => "
        CREATE TABLE ingredients (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_name VARCHAR(255) NOT NULL,
            generic_name VARCHAR(255),
            brands VARCHAR(255),
            image_url VARCHAR(1024),
            code VARCHAR(100) UNIQUE
        )",
    'recipes' => "
        CREATE TABLE recipes (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recipe_name VARCHAR(255) NOT NULL,
            total_time VARCHAR(50),
            servings VARCHAR(50),
            ingredients TEXT,
            directions TEXT,
            rating FLOAT,
            url VARCHAR(1024),
            img_src VARCHAR(1024)
        )",
    'pantry_items' => "
        CREATE TABLE pantry_items (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) UNSIGNED NOT NULL,
            ingredient_id INT(11) UNSIGNED NOT NULL,
            quantity FLOAT NOT NULL,
            unit VARCHAR(50),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
        )",
    'favorite_recipes' => "
        CREATE TABLE favorite_recipes (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) UNSIGNED NOT NULL,
            recipe_id INT(11) UNSIGNED NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
        )",
    'recipe_ingredients_parsed' => "
        CREATE TABLE recipe_ingredients_parsed (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recipe_id INT(11) UNSIGNED NOT NULL,
            ingredient_id INT(11) UNSIGNED NOT NULL,
            UNIQUE KEY unique_recipe_ingredient (recipe_id, ingredient_id),
            FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
            FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
        )"
];

foreach ($tables as $name => $sql) {
    $pdo->exec("DROP TABLE IF EXISTS $name");
    echo "  - Dropped table: $name\n";
    $pdo->exec($sql);
    echo "  - Created table: $name\n";
}
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
echo "✓ Schema setup complete.\n";


// --- 3. IMPORT RECIPES ---
echo "\n[3/6] Importing Recipes (recipes.csv)...\n";
$csv_file = __DIR__ . '/recipes.csv';
if (!file_exists($csv_file)) die("FATAL: recipes.csv not found.\n");

$handle = fopen($csv_file, 'r');
$sql = "INSERT INTO recipes (recipe_name, total_time, servings, ingredients, directions, rating, url, img_src) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

$count = 0;
$line = 0;
while (($data = fgetcsv($handle, 2000, ',')) !== FALSE) {
    if ($line++ == 0) continue; // Skip header

    // Map columns based on your CSV structure
    // 1:name, 4:time, 5:servings, 7:ingredients, 8:directions, 9:rating, 10:url, 14:img_src
    $stmt->execute([
        $data[1] ?? '', $data[4] ?? '', $data[5] ?? '', $data[7] ?? '', 
        $data[8] ?? '', $data[9] ?? 0,  $data[10] ?? '', $data[14] ?? ''
    ]);
    $count++;
}
fclose($handle);
echo "✓ Imported $count recipes.\n";


// --- 4. IMPORT INGREDIENTS ---
echo "\n[4/6] Importing Ingredients (ingredients.tsv)...\n";
echo "      (This involves 1GB of data. Please wait...)\n";
$tsv_file = __DIR__ . '/ingredients.tsv';
if (!file_exists($tsv_file)) die("FATAL: ingredients.tsv not found.\n");

$handle = fopen($tsv_file, 'r');
$sql = "INSERT INTO ingredients (product_name, generic_name, brands, image_url, code) VALUES (?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

$pdo->beginTransaction();
$count = 0;
$line = 0;
while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
    if ($line++ == 0) continue; // Skip header

    $code = $data[0] ?? null;
    $name = $data[7] ?? null;
    if (empty($name)) continue; // Skip empty names

    try {
        $stmt->execute([$name, $data[8]??'', $data[12]??'', $data[69]??'', $code]);
        $count++;
    } catch (Exception $e) { /* Ignore duplicates */ }

    if ($count % 5000 == 0) echo "      ... imported $count ingredients...\r";
}
$pdo->commit();
fclose($handle);
echo "\n✓ Imported $count ingredients.\n";


// --- 5. PARSE RECIPES (ROSETTA STONE) ---
echo "\n[5/6] Parsing Recipes (Building the 'Rosetta Stone')...\n";
// Load ingredients into memory for fast matching
echo "      Loading ingredient map...\n";
$map = [];
$stmt = $pdo->query("SELECT id, product_name FROM ingredients");
while ($row = $stmt->fetch()) {
    $n = strtolower($row['product_name']);
    if (strlen($n) > 2) $map[$n] = $row['id'];
}

echo "      Scanning recipes against " . count($map) . " ingredients...\n";
$recipes = $pdo->query("SELECT id, ingredients FROM recipes")->fetchAll();
$sql = "INSERT IGNORE INTO recipe_ingredients_parsed (recipe_id, ingredient_id) VALUES (?, ?)";
$stmt = $pdo->prepare($sql);

$pdo->beginTransaction();
$links = 0;
foreach ($recipes as $r) {
    $text = strtolower($r['ingredients']);
    foreach ($map as $name => $iid) {
        if (strpos($text, $name) !== false) {
            $stmt->execute([$r['id'], $iid]);
            $links++;
        }
    }
}
$pdo->commit();
echo "✓ Created $links links between recipes and ingredients.\n";


// --- 6. DOWNLOAD IMAGES ---
echo "\n[6/6] Downloading Recipe Images...\n";
$img_dir = __DIR__ . '/public/images/recipes/';
if (!is_dir($img_dir)) mkdir($img_dir, 0777, true);

$recipes = $pdo->query("SELECT id, img_src FROM recipes WHERE img_src LIKE 'http%'")->fetchAll();
$stmt = $pdo->prepare("UPDATE recipes SET img_src = ? WHERE id = ?");

$dl_count = 0;
$context = stream_context_create(["http" => ["header" => "User-Agent: Mozilla/5.0\r\n"]]);

foreach ($recipes as $r) {
    $url = $r['img_src'];
    $id = $r['id'];
    $local_name = $id . '.jpg';
    $local_path = $img_dir . $local_name;
    $db_path = '/kitchenpantry/public/images/recipes/' . $local_name;

    $data = @file_get_contents($url, false, $context);
    if ($data) {
        file_put_contents($local_path, $data);
        $stmt->execute([$db_path, $id]);
        $dl_count++;
        echo "      Saved image for recipe #$id\r";
    }
}
echo "\n✓ Downloaded $dl_count images.\n";

echo "\n=============================================\n";
echo "   SETUP COMPLETE!\n";
echo "=============================================\n";
?>