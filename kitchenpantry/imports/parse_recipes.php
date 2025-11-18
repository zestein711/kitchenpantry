<?php
// We're doing millions of operations. Unlimited time.
set_time_limit(0);
// Use more memory just in case
ini_set('memory_limit', '1024M');

echo "--- Recipe Parser (The Rosetta Stone Carver) ---\n";

try {
    require __DIR__ . '/src/config.php';
} catch (Exception $e) {
    die("FATAL: Could not connect to database. " . $e->getMessage() . "\n");
}
echo "✓ Connected to database.\n";

// ===================================
// STEP 1: LOAD ALL CLEAN INGREDIENTS
// ===================================
echo "Loading all clean ingredients from memory... (This may take a moment)\n";
$ingredient_lookup = [];
try {
    $stmt = $pdo->query("SELECT id, product_name FROM ingredients");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // We'll create a few variations to improve matching
        $name = strtolower($row['product_name']);
        
        // Only add if the name is useful
        if (strlen($name) > 2) {
            $ingredient_lookup[$name] = $row['id'];
            
            // Add a singular version (basic, but helps)
            // "apples" -> "apple"
            $singular = rtrim($name, 's');
            if ($singular !== $name) {
                $ingredient_lookup[$singular] = $row['id'];
            }
        }
    }
} catch (PDOException $e) {
    die("FATAL: Could not load ingredients. " . $e->getMessage() . "\n");
}

$total_lookups = count($ingredient_lookup);
echo "✓ Loaded $total_lookups ingredient variations into lookup map.\n";

if ($total_lookups === 0) {
    die("FATAL: Your 'ingredients' table is empty. The parser has nothing to match against.\n");
}

// ===================================
// STEP 2: LOAD ALL MESSY RECIPES
// ===================================
echo "Loading all recipes...\n";
try {
    $stmt_recipes = $pdo->query("SELECT id, ingredients FROM recipes");
    $recipes = $stmt_recipes->fetchAll();
} catch (PDOException $e) {
    die("FATAL: Could not load recipes. " . $e->getMessage() . "\n");
}
echo "✓ Loaded " . count($recipes) . " recipes. Starting parse...\n";

// ===================================
// STEP 3: PREPARE OUR INSERTION SQL
// ===================================
$sql_insert = "
    INSERT INTO recipe_ingredients_parsed (recipe_id, ingredient_id) 
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE recipe_id=recipe_id -- (This just ignores errors)
";
$stmt_insert = $pdo->prepare($sql_insert);

// ===================================
// STEP 4: THE GREAT PARSING LOOP
// ===================================
$total_matches_found = 0;
$recipe_count = 0;

$pdo->beginTransaction(); // Makes inserts MUCH faster

foreach ($recipes as $recipe) {
    $recipe_id = $recipe['id'];
    $messy_text = strtolower($recipe['ingredients']);
    
    // This holds all ingredients we've found *for this recipe*
    // to prevent inserting (recipe 1, flour) 5 times
    $found_in_this_recipe = [];

    // Now, we loop through our ENTIRE clean ingredient map
    // and check if it exists in the messy text.
    // This is the brute-force part.
    foreach ($ingredient_lookup as $clean_name => $ingredient_id) {
        
        // Check if the clean name (e.g., "flour") is in the messy text
        if (strpos($messy_text, $clean_name) !== false) {
            
            // It's a match!
            // Check if we already found this one
            if (!isset($found_in_this_recipe[$ingredient_id])) {
                
                // We haven't. This is a new link.
                $stmt_insert->execute([$recipe_id, $ingredient_id]);
                $found_in_this_recipe[$ingredient_id] = true;
                $total_matches_found++;
            }
        }
    }
    
    $recipe_count++;
    if ($recipe_count % 100 == 0) {
        echo "  ... parsed $recipe_count / " . count($recipes) . " recipes ...\n";
    }
}

$pdo->commit(); // Save all the changes

echo "\n--- PARSING COMPLETE! ---\n";
echo "✓ Scanned " . count($recipes) . " recipes.\n";
echo "✓ Found and linked $total_matches_found total ingredients.\n";
echo "✓ Your 'recipe_ingredients_parsed' table is now built.\n";
?>