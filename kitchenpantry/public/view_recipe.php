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
} else {
    die("Config not found.");
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$recipe_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Dark Mode Query
$stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$is_dark = $stmt->fetchColumn();

// Fetch Recipe
$sql = "SELECT r.*, u.username as author FROM recipes r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$recipe_id]);
$recipe = $stmt->fetch();

if (!$recipe)
    die("Recipe not found.");

// HANDLE REVIEWS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = (int) $_POST['rating'];
    $comment = trim($_POST['comment']);
    $pdo->prepare("INSERT INTO reviews (recipe_id, user_id, rating, comment) VALUES (?, ?, ?, ?)")->execute([$recipe_id, $user_id, $rating, $comment]);

    // Update Weighted Rating
    $pdo->prepare("UPDATE recipes SET vote_count = vote_count + 1, rating = ((rating * vote_count) + ?) / (vote_count + 1) WHERE id = ?")->execute([$rating, $recipe_id]);
    header("Location: view_recipe.php?id=$recipe_id");
    exit;
}

// Fetch Reviews
$reviews = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.recipe_id = ? ORDER BY r.created_at DESC");
$reviews->execute([$recipe_id]);
$review_list = $reviews->fetchAll();

// --- SMART PARSING FUNCTIONS ---
function parseIngredients($text)
{
    // If it contains newlines, split by newline (User created)
    if (strpos($text, "\n") !== false) {
        return array_filter(array_map('trim', explode("\n", $text)));
    }
    // Otherwise, split by comma (Imported CSV)
    return array_filter(array_map('trim', explode(",", $text)));
}

function parseDirections($text)
{
    // If it contains newlines, split by newline (User created)
    if (strpos($text, "\n") !== false) {
        return array_filter(array_map('trim', explode("\n", $text)));
    }
    // Otherwise, split by ". " to separate sentences (Imported CSV)
    // We append the "." back to the end of the sentence for correctness
    $steps = explode(".", $text);
    $clean = [];
    foreach ($steps as $s) {
        $s = trim($s);
        if (!empty($s))
            $clean[] = $s . ".";
    }
    return $clean;
}

$ing_list = parseIngredients($recipe['ingredients']);
$dir_list = parseDirections($recipe['directions']);
?>

<!DOCTYPE html>
<html>

<head>
    <title><?php echo htmlspecialchars($recipe['recipe_name']); ?></title>
    <link rel="stylesheet" href="style/global.css">
    <style>
        /* Custom list styles for this page */
        ul.ing-list {
            padding-left: 20px;
            line-height: 1.6;
        }

        ul.ing-list li {
            margin-bottom: 5px;
        }

        ol.dir-list {
            padding-left: 20px;
            line-height: 1.6;
        }

        ol.dir-list li {
            margin-bottom: 12px;
        }
    </style>
</head>

<body class="<?php echo ($is_dark) ? 'dark-mode' : ''; ?>">

    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links">
            <a href="recipes.php">Back to Browser</a>
        </div>
    </header>

    <div class="card-container" style="max-width: 900px;">
        <h1 style="margin-top:0;"><?php echo htmlspecialchars($recipe['recipe_name']); ?></h1>
        <div style="color:var(--text-muted); margin-bottom: 20px;">
            By <?php echo htmlspecialchars($recipe['author'] ?? 'Kitchen Pantry'); ?>
            | ⭐ <?php echo number_format($recipe['rating'], 1); ?> / 5
            | ⏱️ <?php echo htmlspecialchars($recipe['total_time']); ?>
            | 🍽️ <?php echo htmlspecialchars($recipe['servings']); ?> Servings
        </div>

        <?php if ($recipe['img_src']): ?>
            <img src="<?php echo htmlspecialchars($recipe['img_src']); ?>"
                style="width:100%; max-height:400px; object-fit:cover; border-radius:8px; margin-bottom:20px;">
        <?php endif; ?>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div>
                <h3 style="border-bottom:2px solid var(--primary-color); padding-bottom:5px;">Ingredients</h3>
                <ul class="ing-list">
                    <?php foreach ($ing_list as $ing): ?>
                        <li><?php echo htmlspecialchars($ing); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h3 style="border-bottom:2px solid var(--primary-color); padding-bottom:5px;">Directions</h3>
                <ol class="dir-list">
                    <?php foreach ($dir_list as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>

        <hr style="margin: 3rem 0; border: 0; border-top: 1px solid var(--border-color);">

        <h3>Reviews</h3>
        <form method="post" style="background: var(--bg-color); padding: 20px; border-radius: 8px;">
            <select name="rating" required style="width:auto;">
                <option value="5">⭐⭐⭐⭐⭐</option>
                <option value="4">⭐⭐⭐⭐</option>
                <option value="3">⭐⭐⭐</option>
            </select>
            <textarea name="comment" rows="3" placeholder="Write a review..." required></textarea>
            <button type="submit" name="submit_review" class="btn-primary" style="width:auto;">Submit Review</button>
        </form>

        <div style="margin-top: 20px;">
            <?php foreach ($review_list as $rev): ?>
                <div style="border-bottom: 1px solid var(--border-color); padding: 15px 0;">
                    <strong><?php echo htmlspecialchars($rev['username']); ?></strong>
                    <span style="color:gold;"><?php echo str_repeat('★', $rev['rating']); ?></span>
                    <p style="margin:5px 0;"><?php echo htmlspecialchars($rev['comment']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>