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
$first_name = $_SESSION['first_name'] ?? 'User'; // FIX: Correct greeting

$stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$is_dark = $stmt->fetchColumn();

// Fetch Favorites
$sql = "SELECT r.* FROM favorite_recipes f JOIN recipes r ON f.recipe_id = r.id WHERE f.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$recipes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Favorites</title>
    <link rel="stylesheet" href="style/global.css">
</head>

<body class="<?php echo ($is_dark) ? 'dark-mode' : ''; ?>">
    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links">
            <a href="index.php">My Pantry</a>
            <a href="recipes.php">Recipe Browser</a>
            <a href="my_favorites.php" class="active">Favorites</a>
            <a href="what_can_i_make.php">What Can I Make?</a>
        </div>
        <div class="profile-menu">
            <a href="settings.php">⚙️ Settings</a>
            <span>Hi, <?php echo htmlspecialchars($first_name); ?></span>
            <a href="logout.php" class="button-logout">Log Out</a>
        </div>
    </header>

    <div style="padding: 2rem;">
        <h2>My Favorites</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($recipes as $recipe): ?>
                <div class="card-container"
                    style="margin:0; padding:0; overflow:hidden; display:flex; flex-direction:column;">
                    <?php if ($recipe['img_src']): ?>
                        <img src="<?php echo htmlspecialchars($recipe['img_src']); ?>"
                            style="width:100%; height:180px; object-fit:cover;">
                    <?php endif; ?>
                    <div style="padding:15px; flex:1; display:flex; flex-direction:column;">
                        <h3 style="margin-top:0;"><?php echo htmlspecialchars($recipe['recipe_name']); ?></h3>
                        <div
                            style="margin-top:auto; padding-top:10px; display:flex; justify-content:space-between; align-items:center;">
                            <a href="remove_favorite.php?id=<?php echo $recipe['id']; ?>"
                                style="color:gold; font-size:1.2rem;">★</a>
                            <a href="view_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn-primary"
                                style="width:auto; padding:5px 10px; font-size:0.9rem;">View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($recipes))
                echo "<p>No favorites yet.</p>"; ?>
        </div>
    </div>
</body>

</html>