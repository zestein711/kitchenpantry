<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$path_subfolder = __DIR__ . '/../src/config.php';
$path_root = __DIR__ . '/src/config.php';
if (file_exists($path_subfolder)) {
    require $path_subfolder;
} elseif (file_exists($path_root)) {
    require $path_root;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'User';

// 1. Check Dark Mode
$stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$is_dark = $stmt->fetchColumn();

// 2. Filters
$search_term = htmlspecialchars(trim($_GET['search'] ?? ''));
$sort_by = htmlspecialchars(trim($_GET['sort'] ?? 'popular'));
$excluded_allergens = $_GET['exclude'] ?? [];
$user_recipes_only = isset($_GET['user_recipes_only']);

$params = [$user_id];
$where_clauses = [];

// Base Query
$sql = "
    SELECT 
        r.id, r.recipe_name, r.total_time, r.servings, r.rating, r.url, r.img_src, r.ingredients, r.user_id,
        CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS is_favorite
    FROM recipes AS r
    LEFT JOIN favorite_recipes AS f ON r.id = f.recipe_id AND f.user_id = ? 
";

if (!empty($search_term)) {
    $where_clauses[] = "r.recipe_name LIKE ?";
    $params[] = "%$search_term%";
}

if ($user_recipes_only) {
    $where_clauses[] = "r.user_id IS NOT NULL";
}

// Allergen Logic
if (!empty($excluded_allergens)) {
    $map = [
        'Dairy' => ['milk', 'cheese', 'butter', 'cream', 'yogurt', 'whey'],
        'Eggs' => ['egg', 'mayonnaise', 'meringue'],
        'Peanuts' => ['peanut', 'nut butter'],
        'Tree Nuts' => ['almond', 'cashew', 'walnut', 'pecan', 'pistachio', 'hazelnut'],
        'Soy' => ['soy', 'tofu', 'edamame', 'miso', 'tempeh'],
        'Wheat' => ['wheat', 'flour', 'bread', 'pasta', 'barley', 'rye', 'gluten']
    ];
    foreach ($excluded_allergens as $allergen) {
        if (isset($map[$allergen])) {
            foreach ($map[$allergen] as $bad_word) {
                $where_clauses[] = "r.ingredients NOT LIKE ?";
                $params[] = "%$bad_word%";
            }
        }
    }
}

if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// Sorting
if ($sort_by === 'alpha') {
    $sql .= " ORDER BY r.recipe_name ASC";
} else {
    $sql .= " ORDER BY r.rating DESC";
}
$sql .= " LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// Allergen Helper
function getAllergens($text)
{
    $text = strtolower($text ?? '');
    $found = [];
    $keywords = [
        'Dairy' => ['milk', 'cheese', 'butter', 'cream', 'yogurt'],
        'Eggs' => ['egg', 'mayonnaise'],
        'Peanuts' => ['peanut'],
        'Tree Nuts' => ['almond', 'cashew', 'walnut'],
        'Soy' => ['soy', 'tofu'],
        'Wheat' => ['wheat', 'flour', 'bread', 'pasta']
    ];
    foreach ($keywords as $group => $words) {
        foreach ($words as $w) {
            if (preg_match("/\\b$w(s?)\\b/i", $text)) {
                $found[] = $group;
                break;
            }
        }
    }
    return array_unique($found);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Recipe Browser</title>
    <link rel="stylesheet" href="style/global.css">
    <style>
        .filter-section {
            background: var(--card-bg);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .filter-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .recipe-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 0 2rem 4rem 2rem;
        }

        .recipe-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
            position: relative;
        }

        .recipe-card:hover {
            transform: translateY(-3px);
        }

        .user-badge {
            background-color: #17a2b8;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-bottom: 5px;
            display: inline-block;
        }
    </style>
</head>

<body class="<?php echo ($is_dark) ? 'dark-mode' : ''; ?>">

    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links">
            <a href="index.php">My Pantry</a>
            <a href="recipes.php" class="active">Recipe Browser</a>
            <a href="my_favorites.php">Favorites</a>
            <a href="what_can_i_make.php">What Can I Make?</a>
        </div>
        <div class="profile-menu">
            <a href="add_recipe.php" class="btn-primary" style="width:auto; padding: 5px 15px; font-size: 0.9rem;">+ Add
                Recipe</a>
            <a href="settings.php">⚙️ Settings</a>
            <span>Hi, <?php echo htmlspecialchars($first_name); ?></span>
            <a href="logout.php" class="button-logout">Log Out</a>
        </div>
    </header>

    <div class="filter-section">
        <form method="GET">
            <div class="filter-row">
                <div style="display:flex; gap:10px; flex:1;">
                    <input type="text" name="search" placeholder="Search recipes..." value="<?php echo $search_term; ?>"
                        style="margin:0; max-width:300px;">
                    <button type="submit" class="btn-primary" style="width:auto;">Search</button>
                </div>
                <div>
                    Sort:
                    <select name="sort" onchange="this.form.submit()"
                        style="width:auto; display:inline-block; margin:0;">
                        <option value="popular" <?php if ($sort_by == 'popular')
                            echo 'selected'; ?>>Highest Rated</option>
                        <option value="alpha" <?php if ($sort_by == 'alpha')
                            echo 'selected'; ?>>A-Z</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap: 15px; flex-wrap:wrap;">
                <label
                    style="background:var(--bg-color); padding:5px 10px; border-radius:15px; border:1px solid var(--border-color); cursor:pointer;">
                    <input type="checkbox" name="user_recipes_only" value="1" <?php echo $user_recipes_only ? 'checked' : ''; ?> onchange="this.form.submit()">
                    👤 User Created Only
                </label>
                <span style="color:var(--text-muted);">| Exclude:</span>
                <?php foreach (['Dairy', 'Eggs', 'Peanuts', 'Tree Nuts', 'Soy', 'Wheat'] as $a): ?>
                    <label style="font-size:0.9rem; cursor:pointer;">
                        <input type="checkbox" name="exclude[]" value="<?php echo $a; ?>" <?php echo in_array($a, $excluded_allergens) ? 'checked' : ''; ?>> <?php echo $a; ?>
                    </label>
                <?php endforeach; ?>
                <button type="submit"
                    style="background:none; border:none; color:var(--accent-color); cursor:pointer; text-decoration:underline;">Apply</button>
            </div>
        </form>
    </div>

    <div class="recipe-grid">
        <?php foreach ($recipes as $recipe): ?>
            <?php $allergens = getAllergens($recipe['ingredients']); ?>
            <div class="recipe-card">
                <?php if ($recipe['img_src']): ?>
                    <img src="<?php echo htmlspecialchars($recipe['img_src']); ?>"
                        style="width:100%; height: 200px; object-fit: cover;">
                <?php else: ?>
                    <div
                        style="width:100%; height: 200px; background:var(--bg-color); display:flex; align-items:center; justify-content:center; color:var(--text-muted);">
                        No Image</div>
                <?php endif; ?>

                <div style="padding: 1rem; flex: 1; display:flex; flex-direction:column;">
                    <?php if (!empty($recipe['user_id'])): ?>
                        <div><span class="user-badge">👤 User Recipe</span></div>
                    <?php endif; ?>

                    <h3 style="margin:0 0 10px 0;"><?php echo htmlspecialchars($recipe['recipe_name']); ?></h3>

                    <?php if (!empty($allergens)): ?>
                        <div style="color: var(--danger-color); font-size: 0.8em; margin-bottom: 5px;">
                            <strong>⚠️ Contains:</strong> <?php echo implode(', ', $allergens); ?>
                        </div>
                    <?php endif; ?>

                    <div
                        style="margin-top:auto; padding-top:10px; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                        <?php if ($recipe['is_favorite']): ?>
                            <a href="remove_favorite.php?id=<?php echo $recipe['id']; ?>" style="color:gold;">★ Saved</a>
                        <?php else: ?>
                            <a href="add_favorite.php?id=<?php echo $recipe['id']; ?>" style="color:var(--accent-color);">☆
                                Save</a>
                        <?php endif; ?>
                        <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn-primary"
                            style="width:auto; padding:5px 10px; font-size:0.8rem;">View</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>