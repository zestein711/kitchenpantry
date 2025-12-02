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

// 2. Allergen Helper Function
function getAllergens($text)
{
    $text = strtolower($text ?? '');
    $found = [];
    $keywords = [
        'Dairy' => ['milk', 'cheese', 'butter', 'cream', 'yogurt', 'whey'],
        'Eggs' => ['egg', 'mayonnaise', 'meringue'],
        'Peanuts' => ['peanut', 'nut butter'],
        'Tree Nuts' => ['almond', 'cashew', 'walnut', 'pecan', 'pistachio', 'hazelnut'],
        'Soy' => ['soy', 'tofu', 'edamame', 'miso', 'tempeh'],
        'Wheat' => ['wheat', 'flour', 'bread', 'pasta', 'barley', 'rye', 'gluten']
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

// 3. Get Pantry Ingredient IDs
$stmt = $pdo->prepare("SELECT ingredient_id FROM pantry_items WHERE user_id = ?");
$stmt->execute([$user_id]);
$pantry_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

$recipes_found = [];
if (!empty($pantry_ids)) {
    $in = implode(',', array_fill(0, count($pantry_ids), '?'));

    // Note: We select r.* so we get the 'ingredients' column needed for the allergen check
    $sql = "SELECT r.*, COUNT(rip.ingredient_id) as matches 
            FROM recipes r 
            JOIN recipe_ingredients_parsed rip ON r.id = rip.recipe_id 
            WHERE rip.ingredient_id IN ($in) 
            GROUP BY r.id 
            ORDER BY matches DESC LIMIT 20";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($pantry_ids);
    $recipes_found = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>What Can I Make?</title>
    <link rel="stylesheet" href="style/global.css">
</head>

<body class="<?php echo ($is_dark) ? 'dark-mode' : ''; ?>">
    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links">
            <a href="index.php">My Pantry</a>
            <a href="recipes.php">Recipe Browser</a>
            <a href="my_favorites.php">Favorites</a>
            <a href="what_can_i_make.php" class="active">What Can I Make?</a>
        </div>
        <div class="profile-menu">
            <a href="settings.php">⚙️ Settings</a>
            <span>Hi, <?php echo htmlspecialchars($first_name); ?></span>
            <a href="logout.php" class="button-logout">Log Out</a>
        </div>
    </header>

    <div style="padding: 2rem;">
        <h2>What Can I Make?</h2>
        <p style="color:var(--text-muted); margin-bottom: 20px;">Based on the ingredients currently in your pantry.</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($recipes_found as $recipe): ?>
                <?php $allergens = getAllergens($recipe['ingredients']); ?>

                <div class="card-container"
                    style="margin:0; padding:0; overflow:hidden; display:flex; flex-direction:column; position:relative;">
                    <div
                        style="position:absolute; top:10px; right:10px; background:var(--primary-color); color:white; padding:5px 10px; border-radius:5px; font-weight:bold; z-index:10;">
                        <?php echo $recipe['matches']; ?> Matches
                    </div>

                    <?php if ($recipe['img_src']): ?>
                        <img src="<?php echo htmlspecialchars($recipe['img_src']); ?>"
                            style="width:100%; height:180px; object-fit:cover;">
                    <?php endif; ?>

                    <div style="padding:15px; flex:1; display:flex; flex-direction:column;">
                        <h3 style="margin-top:0; margin-bottom: 10px;">
                            <?php echo htmlspecialchars($recipe['recipe_name']); ?></h3>

                        <?php if (!empty($allergens)): ?>
                            <div
                                style="color: var(--danger-color); font-size: 0.8em; margin-bottom: 10px; background: rgba(217, 83, 79, 0.1); padding: 5px; border-radius: 4px;">
                                <strong>⚠️ Contains:</strong> <?php echo implode(', ', $allergens); ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top:auto; padding-top:10px; text-align:right;">
                            <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn-primary"
                                style="width:auto; padding:5px 10px; font-size:0.9rem;">View Recipe</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($recipes_found)): ?>
                <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted);">
                    <p>No matches found yet. Add more ingredients to your pantry!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>