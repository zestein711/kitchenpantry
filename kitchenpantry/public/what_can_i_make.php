<?php
// ========== ADD THESE TWO "MAGIC LINES" ==========
// This forces all errors to be displayed
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ===============================================

session_start(); // Always first

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data from session
$first_name = htmlspecialchars($_SESSION['first_name']);
$user_id = $_SESSION['user_id'];

// Connect to the database
require __DIR__ . '/../src/config.php';

$recipes_found = []; // This will hold our final results

try {
    // 1. Get a simple list of ingredient IDs from the user's pantry
    $stmt_pantry = $pdo->prepare("SELECT DISTINCT ingredient_id FROM pantry_items WHERE user_id = ?");
    $stmt_pantry->execute([$user_id]);
    $pantry_ingredient_ids = $stmt_pantry->fetchAll(PDO::FETCH_COLUMN);

    if (empty($pantry_ingredient_ids)) {
        // User's pantry is empty
        $recipes_found = [];
        
    } else {
        
        // ==========================================================
        // === THE "DUMB-BUT-WORKS" QUERY
        // ==========================================================
        
        $placeholders = implode(',', array_fill(0, count($pantry_ingredient_ids), '?'));

        $sql = "
            SELECT
                r.id,
                r.recipe_name,
                r.img_src,
                COUNT(r.id) AS matching_ingredients
            FROM
                recipes AS r
            JOIN
                recipe_ingredients_parsed AS rip ON r.id = rip.recipe_id
            WHERE
                rip.ingredient_id IN ($placeholders)
            GROUP BY
                r.id, r.recipe_name, r.img_src
            ORDER BY
                matching_ingredients DESC,
                r.recipe_name ASC
            LIMIT 50;
        ";

        $stmt_matches = $pdo->prepare($sql);
        $stmt_matches->execute($pantry_ingredient_ids);
        $recipes_found = $stmt_matches->fetchAll();
    }
    
} catch (PDOException $e) {
    die("Error: Could not calculate your recipes. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>What Can I Make?</title>
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="style/forms.css">
    
    <style>
        .app-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 2rem; background-color: #f8f8f8; border-bottom: 1px solid #ddd;
        }
        .profile-menu span { margin-right: 1rem; }
        .button-logout {
            text-decoration: none; background: #d9534f; color: white;
            padding: 0.5rem 1rem; border-radius: 5px;
        }
        .nav-links { flex: 1; text-align: center; }
        .nav-links a { text-decoration: none; color: #007bff; margin: 0 1rem; font-weight: 500; }
        .nav-links a.active { font-weight: 700; border-bottom: 2px solid #007bff; }
        
        .container { padding: 2rem; max-width: 1200px; margin: auto; }
        
        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .recipe-card {
            border: 1px solid #ddd; border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden; display: flex; flex-direction: column; background: #fff;
            position: relative;
        }
        .recipe-card-content { padding: 1rem; }
        .recipe-card img {
            width:100%; height: 200px; object-fit: cover;
        }
        .recipe-card h3 { 
            margin-top: 0; 
            font-size: 1.25rem; 
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .match-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4CAF50;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links">
            <a href="index.php">My Pantry</a>
            <a href="recipes.php">Recipe Browser</a>
            <a href="my_favorites.php">My Favorites</a>
            <a href="what_can_i_make.php" class="active">What Can I Make?</a>
        </div>
        <div class="profile-menu">
            <span>Welcome, <?php echo $first_name; ?>!</span>
            <a href="logout.php" class="button-logout">Log Out</a>
        </div>
    </header>

    <main class="container">
        <h2>What Can I Make?</h2>
        <p style="margin-top:-1rem; margin-bottom: 2rem; color: #555;">Recipes ranked by the number of ingredients you have in your pantry.</p>
        
        <div class="recipe-grid">
            
            <?php if (empty($recipes_found)): ?>
                <p>No matches found. (After clearing your pantry and re-adding, if this still shows, the "Rosetta Stone" is still the problem).</p>
            <?php else: ?>
                <?php foreach ($recipes_found as $recipe): ?>
                    
                    <div class="recipe-card">
                        
                        <div class="match-badge">
                            <?php echo $recipe['matching_ingredients']; ?> Match<?php echo ($recipe['matching_ingredients'] > 1 ? 'es' : ''); ?>
                        </div>

                        <?php if (!empty($recipe['img_src'])): ?>
                            <img src="<?php echo htmlspecialchars($recipe['img_src']); ?>" alt="<?php echo htmlspecialchars($recipe['recipe_name']); ?>">
                        <?php else: ?>
                            <div style="width:100%; height: 200px; background: #eee; display:flex; align-items:center; justify-content:center; color:#aaa;">No Image</div>
                        <?php endif; ?>

                        <div class="recipe-card-content">
                            <h3><?php echo htmlspecialchars($recipe['recipe_name']); ?></h3>
                            
                            <div class="recipe-card-actions" style="margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;">
                                <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" style="float:right;">View Recipe</a>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>
        
    </main>

</body>
</html>