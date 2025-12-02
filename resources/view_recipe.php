<?php
session_start(); // Always first

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$first_name = htmlspecialchars($_SESSION['first_name']);

// Connect to the database
require __DIR__ . '/../src/config.php';

// 1. Check if an ID was passed in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: recipes.php');
    exit;
}

$recipe_id = (int)$_GET['id'];

// 2. Fetch this one specific recipe from the database
//    *** THIS QUERY IS NOW UPGRADED ***
try {
    $sql = "
        SELECT 
            r.*,
            CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS is_favorite
        FROM 
            recipes AS r
        LEFT JOIN 
            favorite_recipes AS f 
        ON 
            r.id = f.recipe_id AND f.user_id = ?
        WHERE 
            r.id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $recipe_id]); // Pass both IDs
    $recipe = $stmt->fetch();
    
    // If no recipe was found, go back to the list
    if (!$recipe) {
        header('Location: recipes.php');
        exit;
    }
    
} catch (PDOException $e) {
    die("Error: Could not fetch recipe. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($recipe['recipe_name']); ?></title>
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="style/forms.css">
    <style>
        /* (Copied styles from other pages...) */
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
        .container { padding: 2rem; max-width: 900px; margin: auto; }
        
        /* New styles for the recipe layout */
        .recipe-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .recipe-header img {
            max-width: 100%;
            height: auto;
            max-height: 400px;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .recipe-meta {
            display: flex;
            justify-content: center;
            gap: 2rem;
            color: #555;
            margin-bottom: 2rem;
        }
        .recipe-content {
            display: flex;
            gap: 2rem;
        }
        .recipe-ingredients {
            flex: 1;
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
        }
        .recipe-directions {
            flex: 2;
        }
        .recipe-ingredients h3, .recipe-directions h3 {
            margin-top: 0;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }
        /* nl2br() will add <br> tags, so this makes them look like a list */
        .recipe-directions p {
            line-height: 1.6;
        }
        .favorite-toggle-large {
            text-align: center;
            margin-bottom: 2rem;
        }
        .favorite-toggle-large a {
            text-decoration: none;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .favorite-toggle-large a.add {
            color: #007bff;
            border: 1px solid #007bff;
        }
        .favorite-toggle-large a.add:hover {
            background: #007bff;
            color: #fff;
        }
        .favorite-toggle-large a.remove {
            color: #ffc107;
            border: 1px solid #ffc107;
        }
        .favorite-toggle-large a.remove:hover {
            background: #ffc107;
            color: #333;
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
            <a href="what_can_i_make.php">What Can I Make?</a>
        </div>
        <div class="profile-menu">
            <span>Welcome, <?php echo $first_name; ?>!</span>
            <a href="logout.php" class="button-logout">Log Out</a>
        </div>
    </header>

    <main class="container">
        
        <div class="recipe-header">
            <h2><?php echo htmlspecialchars($recipe['recipe_name']); ?></h2>
            
            <?php if (!empty($recipe['img_src'])): ?>
                <img src="<?php echo htmlspecialchars($recipe['img_src']); ?>" alt="<?php echo htmlspecialchars($recipe['recipe_name']); ?>">
            <?php endif; ?>

            <div class="recipe-meta">
                <span><strong>Time:</strong> <?php echo htmlspecialchars($recipe['total_time'] ?: 'N/A'); ?></span>
                <span><strong>Serves:</strong> <?php echo htmlspecialchars($recipe['servings']); ?></span>
                <span><strong>Rating:</strong> <?php echo htmlspecialchars($recipe['rating']); ?> / 5</span>
            </div>
            <div class="favorite-toggle-large">
                <?php if ($recipe['is_favorite']): ?>
                    <a href="remove_favorite.php?id=<?php echo $recipe['id']; ?>" class="remove">
                        ★ Favorited
                    </a>
                <?php else: ?>
                    <a href="add_favorite.php?id=<?php echo $recipe['id']; ?>" class="add">
                         ☆ Add to Favorites
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="recipe-content">
            <div class="recipe-ingredients">
                <h3>Ingredients</h3>
                <p><?php echo nl2br(htmlspecialchars($recipe['ingredients'])); ?></p>
            </div>
            <div class="recipe-directions">
                <h3>Directions</h3>
                <p><?php echo nl2br(htmlspecialchars($recipe['directions'])); ?></p>
            </div>
        </div>
        
    </main>

</body>
</html>