<?php
// We're downloading 1000+ images. We want NO time limits.
set_time_limit(0);

echo "--- Recipe Image Downloader ---\n";

// --- 1. SETUP ---
try {
    require __DIR__ . '/src/config.php';
} catch (Exception $e) {
    die("FATAL: Could not connect to database. " . $e->getMessage() . "\n");
}
echo "✓ Connected to database.\n";

$image_folder_path = __DIR__ . '/public/images/recipes/';

// Check if the folder we created actually exists
if (!is_dir($image_folder_path)) {
    die("FATAL: Folder not found: $image_folder_path. Please create it!\n");
}
echo "✓ Image save folder found.\n";


// --- 2. GET RECIPES ---
// We only want recipes that still have a hotlink (http)
try {
    $stmt = $pdo->query("SELECT id, img_src FROM recipes WHERE img_src LIKE 'http%'");
    $recipes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("FATAL: Could not fetch recipes. " . $e->getMessage() . "\n");
}

$total = count($recipes);
echo "✓ Found $total images to download. Starting process...\n";

// --- 3. PREPARE THE UPDATE QUERY ---
$sql_update = "UPDATE recipes SET img_src = ? WHERE id = ?";
$stmt_update = $pdo->prepare($sql_update);


// --- 4. THE MAIN LOOP ---
$success_count = 0;
$error_count = 0;

foreach ($recipes as $index => $recipe) {
    $image_url = $recipe['img_src'];
    $recipe_id = $recipe['id'];
    
    // We need to fake a browser, or they'll block us.
    // This is the "magic" part.
    $context = stream_context_create([
        "ssl" => ["verify_peer" => false, "verify_peer_name" => false],
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36\r\n"
        ]
    ]);

    // Use '@' to suppress ugly warnings if a download fails (e.g., 404)
    $image_data = @file_get_contents($image_url, false, $context);

    if ($image_data === false) {
        echo "  -> FAILED to download image for recipe $recipe_id (404?)\n";
        $error_count++;
        continue;
    }
    
    // --- 5. SAVE THE IMAGE ---
    // Create a new local file name, e.g., "123.jpg"
    // We'll just assume they're all JPGs.
    $local_filename = $recipe_id . '.jpg';
    $local_filepath = $image_folder_path . $local_filename;
    
    file_put_contents($local_filepath, $image_data);

    // --- 6. UPDATE THE DATABASE ---
    // This is the new path that will go in the database
    // It's the "public" path, not the server path
    $public_db_path = '/kitchenpantry/public/images/recipes/' . $local_filename;
    
    $stmt_update->execute([$public_db_path, $recipe_id]);
    
    $success_count++;
    echo "  ... Saved image " . ($index + 1) . " / $total (ID: $recipe_id)\n";
}

echo "\n--- DOWNLOAD COMPLETE! ---\n";
echo "✓ Successfully downloaded: $success_count images.\n";
echo "✓ Failed: $error_count images.\n";
?>