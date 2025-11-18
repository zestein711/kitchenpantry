<?php
// ========== ADD THESE TWO "MAGIC LINES" ==========
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ===============================================

session_start(); // Always first

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$first_name = htmlspecialchars($_SESSION['first_name']);

require __DIR__ . '/../src/config.php';

$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $item_name = trim($_POST['item_name'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $unit = trim($_POST['unit'] ?? '');

    if (empty($item_name)) { $errors[] = "Item name is required."; }
    if (empty($quantity)) { $errors[] = "Quantity is required."; }
    if (!is_numeric($quantity)) { $errors[] = "Quantity must be a number."; }

    if (!$errors) {
        try {
            // ================================================================
            // === THIS IS THE FIX. WE ARE CHANGING THE QUERY
            // ================================================================
            //
            // OLD, "DUMB" QUERY:
            // $stmt = $pdo->prepare("SELECT id FROM ingredients WHERE LOWER(product_name) = ?");
            // $stmt->execute([strtolower($item_name)]);
            //
            // NEW, "SMART" QUERY:
            // This finds all ingredients that *contain* the word "flour"
            // and picks the *shortest* one (e.g., "Flour" instead of "Organic All-Purpose Flour")
            //
            $stmt = $pdo->prepare(
                "SELECT id FROM ingredients 
                 WHERE LOWER(product_name) LIKE ? 
                 ORDER BY LENGTH(product_name) ASC 
                 LIMIT 1"
            );
            $stmt->execute(['%' . strtolower($item_name) . '%']);
            $ingredient = $stmt->fetch();
            
            $ingredient_id = null;

            if ($ingredient) {
                // It exists! We found the best match.
                $ingredient_id = $ingredient['id'];
                $messages[] = "DEBUG: Found existing item (ID: $ingredient_id). Adding to pantry.";
                
                // ================================================================
                // === WE NEED TO RUN THE PARSER HERE, TOO!
                // === We found an existing item, but it might not have been
                // === parsed by our *recipe* parser. We must run this in
                // === BOTH cases (new and found).
                // ================================================================
                
                $item_name_lower = strtolower($item_name);
                $messages[] = "DEBUG: Syncing 'Rosetta Stone' for item '$item_name_lower' (ID: $ingredient_id)...";
                
                $sql_link = "INSERT IGNORE INTO recipe_ingredients_parsed (recipe_id, ingredient_id) VALUES (?, ?)";
                $stmt_link = $pdo->prepare($sql_link);

                $stmt_recipes = $pdo->query("SELECT id, ingredients FROM recipes");
                
                $matches_found_this_run = 0;
                while ($recipe = $stmt_recipes->fetch(PDO::FETCH_ASSOC)) {
                    $recipe_id = $recipe['id'];
                    $messy_text = strtolower($recipe['ingredients']);
                    
                    if (strpos($messy_text, $item_name_lower) !== false) {
                        $stmt_link->execute([$recipe_id, $ingredient_id]);
                        $matches_found_this_run++;
                    }
                }
                $messages[] = "DEBUG: Sync complete. Found and linked $matches_found_this_run recipe matches.";
                
            } else {
                // It's a truly new ingredient.
                $pdo->beginTransaction(); 
                
                $stmt = $pdo->prepare("INSERT INTO ingredients (product_name, generic_name) VALUES (?, ?)");
                $stmt->execute([$item_name, $item_name]);
                $ingredient_id = $pdo->lastInsertId();

                $new_name_lower = strtolower($item_name);
                $messages[] = "DEBUG: New item created (ID: $ingredient_id). Now scanning 1,090 recipes for the word: '$new_name_lower'...";
                
                $sql_link = "INSERT IGNORE INTO recipe_ingredients_parsed (recipe_id, ingredient_id) VALUES (?, ?)";
                $stmt_link = $pdo->prepare($sql_link);
                $stmt_recipes = $pdo->query("SELECT id, ingredients FROM recipes");
                
                $matches_found_this_run = 0;
                while ($recipe = $stmt_recipes->fetch(PDO::FETCH_ASSOC)) {
                    $recipe_id = $recipe['id'];
                    $messy_text = strtolower($recipe['ingredients']);
                    
                    if (strpos($messy_text, $new_name_lower) !== false) {
                        $stmt_link->execute([$recipe_id, $ingredient_id]);
                        $matches_found_this_run++;
                    }
                }
                
                $messages[] = "DEBUG: Scan complete. Found $matches_found_this_run recipe matches for '$new_name_lower'.";
                $pdo->commit();
            }

            // 3. Now, add this item to the user's personal 'pantry_items' table
            $stmt = $pdo->prepare("INSERT INTO pantry_items (user_id, ingredient_id, quantity, unit) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $ingredient_id, $quantity, $unit]);

            $messages[] = "<strong>" . htmlspecialchars($item_name) . "</strong> added to your pantry!";

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "FATAL DATABASE ERROR: " . $e->getMessage();
        }
    }
}

// =Setting pantry_items table to empty
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id, 
            p.quantity, 
            p.unit, 
            i.product_name 
        FROM pantry_items AS p
        JOIN ingredients AS i ON p.ingredient_id = i.id
        WHERE p.user_id = ?
        ORDER BY i.product_name
    ");
    $stmt->execute([$user_id]);
    $pantry_items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: Could not fetch pantry items. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Kitchen Pantry</title>
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="style/forms.css">
    <style>
        /* ... All your existing styles are perfect ... */
        .app-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 2rem; background-color: #f8f8f8; border-bottom: 1px solid #ddd;
        }
        .profile-menu span { margin-right: 1rem; }
        .button-logout {
            text-decoration: none; background: #d9534f; color: white;
            padding: 0.5rem 1rem; border-radius: 5px;
        }
        .nav-links {
            flex: 1;
            text-align: center;
        }
        .nav-links a { 
            text-decoration: none; 
            color: #007bff; 
            margin: 0 1rem; 
            font-weight: 500;
        }
        .nav-links a.active {
            font-weight: 700;
            border-bottom: 2px solid #007bff;
        }
        
        .container { padding: 2rem; max-width: 1200px; margin: auto; }
        
        .add-item-form {
            background: #fdfdfd; border: 1px solid #eee; border-radius: 8px;
            padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .add-item-form h3 { margin-top: 0; }
        .add-item-form form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-size: 0.9rem; color: #555; }
        .form-group input {
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group.name { flex: 3 1 250px; }
        .form-group.qty { flex: 1 1 80px; }
        .form-group.unit { flex: 1 1 80px; }
        .form-group button {
            padding: 0.5rem 1rem;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            height: 38px;
            font-size: 1rem;
            margin-top: 1.5rem;
        }
        .form-group button:hover { background-color: #218838; }

        .pantry-shelf {
            background: #fff; border: 1px solid #ddd; border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .pantry-shelf h2 {
            padding: 1rem 1.5rem;
            margin: 0;
            border-bottom: 1px solid #eee;
        }
        .pantry-list { list-style: none; padding: 0; margin: 0; }
        .pantry-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .pantry-list-item:last-child { border-bottom: none; }
        .item-name { font-weight: 600; color: #333; }
        .item-qty { color: #555; }
        .item-actions a {
            color: #d9534f;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        .message.error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .message.success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links">
            <a href="index.php" class="active">My Pantry</a>
            <a href="recipes.php">Recipe Browser</a>
            <a href="my_favorites.php">My Favorites</a>
            <a href="what_can_i_make.php">What Can I Make?</a>
        </div>
        <div class="profile-menu">
            <span>Welcome, <?php echo $first_name; ?>!</span>
            <a href="logout.php" class="button-logout">Log Out</a>
        </div>
    </header>

    <main class="container">
    
        <?php foreach($errors as $err): ?>
            <div class="message error"><?php echo $err; ?></div>
        <?php endforeach; ?>
        <?php foreach($messages as $msg): ?>
            <div class="message success"><?php echo $msg; ?></div>
        <?php endforeach; ?>

        <div class="add-item-form">
            <h3>Add to Your Pantry</h3>
            <form method="POST" action="index.php">
                <div class="form-group name">
                    <label for="item_name">Ingredient Name</label>
                    <input type="text" id="item_name" name="item_name" placeholder="e.g., Flour, Salt, Chicken Breast">
                </div>
                <div class="form-group qty">
                    <label for="quantity">Quantity</label>
                    <input type="text" id="quantity" name="quantity" placeholder="e.g., 100">
                </div>
                <div class="form-group unit">
                    <label for="unit">Unit</label>
                    <input type="text" id="unit" name="unit" placeholder="e.g., g, ml, lbs, pcs">
                </div>
                <div class="form-group">
                    <button type="submit">Add Item</button>
                </div>
            </form>
        </div>

        <div class="pantry-shelf">
            <h2>My Virtual Kitchen</h2>
            
            <ul class="pantry-list">
                <?php if (empty($pantry_items)): ?>
                    <li class="pantry-list-item" style="justify-content: center;">Your pantry is empty! Add an item using the form above.</li>
                <?php else: ?>
                    <?php foreach ($pantry_items as $item): ?>
                        <li class="pantry-list-item">
                            <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                            <span class="item-qty">
                                <?php echo htmlspecialchars($item['quantity']) . ' ' . htmlspecialchars($item['unit']); ?>
                            </span>
                            <span class="item-actions">
                                <a href="delete_item.php?id=<?php echo $item['id']; ?>">Delete</a>
                            </span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
    </main>

</body>
</html>