<?php
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

// ===============================================
// === FETCH THIS USER'S FAVORITE RECIPES
// ===============================================

// This query JOINs three tables:
// 1. favorite_recipes (to get the link)
// 2. recipes (to get the recipe details)
// 3. users (to filter by the current user)
try {
    $sql = "
        SELECT 
            r.id, r.recipe_name, r.total_time, r.servings, r.rating, r.url, r.img_src
        FROM 
            favorite_recipes AS f
        JOIN 
            recipes AS r ON f.recipe_id = r.id
        WHERE 
            f.user_id = ?
        ORDER BY 
            r.recipe_name ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $favorite_recipes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: Could not fetch your favorite recipes. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Favorite Recipes</title>
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
        }
        .recipe-card-content { padding: 1rem; }
        .recipe-card img {
            width:100%; height: 200px; object-fit: cover;
        }
        .recipe-card h3 { margin-top: 0; font-size: 1.25rem; color: #333; }
        .recipe-card-info {
            display: flex; justify-content: space-between;
            font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;
        }
        .recipe-card-actions {
            margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;
        }
        /* Style for the "Remove" link */
        .favorite-link.remove {
            font-weight: 500;
            text-decoration: none;
            color: #d9534f; /* Red for remove */
        }
        .favorite-link.remove:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links">
            <a href="index.php">My Pantry</a>
            <a href="recipes.php">Recipe Browser</a>
            <a href="my_favorites.php" class="active">My Favorites</a>
            <a href="what_can_i_make.php">What Can I Make?</a>
        </div>
        <div class="profile-menu">
            <span>Welcome, <?php echo $first_name; ?>!</span>
            <a href="logout.php" class="button-logout">Log Out</a>
        </div>
    </header>

    <main class="container">
        <h2>My Favorite Recipes</h2>
        
        <div class="recipe-grid">
            
            <?php if (empty($favorite_recipes)): ?>
                <p>You haven't favorited any recipes yet. Go browse the <a href="recipes.php">Recipe Browser</a> to find some!</p>
            <?php else: ?>
                <?php foreach ($favorite_recipes as $recipe): ?>
                    
                    <div class="recipe-card">
                        <?php if (!empty($recipe['img_src'])): ?>
                            <img src="<?php echo htmlspecialchars($recipe['img_src']); ?>" alt="<?php echo htmlspecialchars($recipe['recipe_name']); ?>">
                        <?php else: ?>
                            <div style="width:100%; height: 200px; background: #eee; display:flex; align-items:center; justify-content:center; color:#aaa;">No Image</div>
                        <?php endif; ?>

                        <div class="recipe-card-content">
                            <h3><?php echo htmlspecialchars($recipe['recipe_name']); ?></h3>
                            
                            <div class="recipe-card-info">
                                <span>
                                    <strong>Time:</strong> 
                                    <?php echo htmlspecialchars($recipe['total_time'] ?: 'N/A'); ?>
                                </span>
                                <span>
                                    <strong>Rating:</strong> 
                                    <?php echo htmlspecialchars($recipe['rating']); ?> / 5
                                </span>
                            </div>
                            
                            <div class="recipe-card-actions">
                                <a href="remove_favorite.php?id=<?php echo $recipe['id']; ?>" class="favorite-link remove">
                                    Remove from Favorites
                                </a>
                                
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