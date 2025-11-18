<?php
session_start(); // Always first

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data from session
$first_name = htmlspecialchars($_SESSION['first_name']);
$user_id = $_SESSION['user_id']; // <-- We need this for the query

// Connect to the database
require __DIR__ . '/../src/config.php'; // Get the $pdo variable

// ===============================================
// === PART 1: GET SEARCH & SORT PARAMETERS
// ===============================================

$search_term = htmlspecialchars(trim($_GET['search'] ?? ''));
$sort_by = htmlspecialchars(trim($_GET['sort'] ?? 'popular'));

// ===============================================
// === PART 2: BUILD THE SQL QUERY (UPGRADED)
// ===============================================

// This array will hold all the parameters for the query
$params = [];

// Start with the base query
$sql = "
    SELECT 
        r.id, r.recipe_name, r.total_time, r.servings, r.rating, r.url, r.img_src,
        -- This CASE statement creates a new 'is_favorite' column (0 or 1)
        -- It's our magic toggle
        CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS is_favorite
    FROM 
        recipes AS r
    LEFT JOIN 
        favorite_recipes AS f 
    ON 
        r.id = f.recipe_id AND f.user_id = ? 
";
// Add the user_id as the FIRST parameter for the JOIN
$params[] = $user_id;

// This will hold our WHERE conditions
$where_clauses = [];

// If the user is searching...
if (!empty($search_term)) {
    // ...add a WHERE clause
    $where_clauses[] = "r.recipe_name LIKE ?";
    // Add the search term to our params
    $params[] = "%$search_term%";
}

// If we have any WHERE clauses, add them to the query
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// Add the ORDER BY (sorting)
if ($sort_by === 'alpha') {
    $sql .= " ORDER BY r.recipe_name ASC";
} else {
    // Default to 'popular'
    $sql .= " ORDER BY r.rating DESC";
}

// Always good to limit our results
$sql .= " LIMIT 50";


// ===============================================
// === PART 3: FETCH THE RECIPES
// ===============================================
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params); // Pass in the params
    $recipes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: Could not fetch recipes. " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recipe Browser</title>
    <link rel="stylesheet" href="style/global.css">
    <link rel="stylesheet" href="style/forms.css">
    <style>
        /* (All the styles from your original index/recipes page...) */
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
        
        /* NEW: Styles for the search/filter bar */
        .recipe-controls {
            padding: 1rem 2rem;
            background: #fdfdfd;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .recipe-controls form {
            display: flex;
            gap: 10px;
        }
        .recipe-controls input[type="text"] {
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 300px;
        }
        .recipe-controls button {
            padding: 0.5rem 1rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .favorite-link {
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .favorite-link.add {
            color: #007bff;
        }
        .favorite-link.add:hover {
            opacity: 0.7;
        }
        .favorite-link.remove {
            color: #ffc107; /* Gold color for "favorited" */
        }
        .favorite-link.remove:hover {
            color: #e6a700;
        }
        .filter-links a {
            text-decoration: none;
            padding: 0.5rem;
            margin-left: 0.5rem;
            color: #007bff;
        }
        .filter-links a.active {
            font-weight: 700;
            border-bottom: 2px solid #007bff;
        }

        /* (Recipe grid styles are the same as before...) */
        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 2rem;
        }
        .recipe-card {
            border: 1px solid #ddd; border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden; display: flex; flex-direction: column; background: #fff;
        }
        .recipe-card-content { padding: 1rem; }
        .recipe-card h3 { margin-top: 0; font-size: 1.25rem; color: #333; }
        .recipe-card-info {
            display: flex; justify-content: space-between;
            font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;
        }
        .recipe-card-actions {
            margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;
        }
        .recipe-card a { text-decoration: none; color: #007bff; }
    </style>
</head>
<body>

    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links">
            <a href="index.php">My Pantry</a>
            <a href="recipes.php" class="active">Recipe Browser</a>
            <a href="my_favorites.php">My Favorites</a>
            <a href="what_can_i_make.php">What Can I Make?</a>
        </div>
        <div class="profile-menu">
            <span>Welcome, <?php echo $first_name; ?>!</span>
            <a href="logout.php" class="button-logout">Log Out</a>
        </div>
    </header>

    <div class="recipe-controls">
        <form method="GET" action="recipes.php">
            <input type="text" name="search" placeholder="Search recipes..." value="<?php echo $search_term; ?>">
            <button type="submit">Search</button>
            <input type="hidden" name="sort" value="<?php echo $sort_by; ?>">
        </form>
        <div class="filter-links">
            <span>Sort by:</span>
            <a href="recipes.php?sort=popular&search=<?php echo $search_term; ?>" 
               class="<?php echo $sort_by === 'popular' ? 'active' : ''; ?>">
               Popular
            </a>
            <a href="recipes.php?sort=alpha&search=<?php echo $search_term; ?>"
               class="<?php echo $sort_by === 'alpha' ? 'active' : ''; ?>">
               A-Z
            </a>
        </div>
    </div>

    <main class="app-content">
        <div class="recipe-grid">
            
            <?php foreach ($recipes as $recipe): ?>
    
                <div class="recipe-card">
        
                    <?php if (!empty($recipe['img_src'])): ?>
                        <img src="<?php echo htmlspecialchars($recipe['img_src']); ?>" alt="<?php echo htmlspecialchars($recipe['recipe_name']); ?>" style="width:100%; height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div style="width:100%; height: 200px; background: #eee; display:flex; align-items:center; justify-content:center; color:#aaa;">No Image</div>
                    <?php endif; ?>

                    <div class="recipe-card-content">
                        <h3><?php echo htmlspecialchars($recipe['recipe_name']); ?></h3>
                        <div class="recipe-card-info">
                            </div>
                        <div class="recipe-card-actions">
                            <?php if ($recipe['is_favorite']): ?>
                                <a href="remove_favorite.php?id=<?php echo $recipe['id']; ?>" class="favorite-link remove">
                                     ★ Favorited
                                </a>
                            <?php else: ?>
                                <a href="add_favorite.php?id=<?php echo $recipe['id']; ?>" class="favorite-link add">
                                    ☆ Add to Favorites
                                </a>
                            <?php endif; ?>
    
                            <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" style="float:right;">View Recipe</a>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
            
            <?php if (empty($recipes)): ?>
                <p style="padding-left: 2rem;">No recipes found. <?php if(!empty($search_term)) echo 'Try a different search?'; ?></p>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>