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

// 2. Add Item Logic
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $qty = (float) ($_POST['quantity'] ?? 1);
    $unit = trim($_POST['unit'] ?? 'pcs');

    // LOOKUP using 'product_name' and 'id'
    $stmt = $pdo->prepare("SELECT id FROM ingredients WHERE product_name LIKE ? LIMIT 1");
    $stmt->execute(["%$item_name%"]);
    $ing = $stmt->fetch();

    if ($ing) {
        $iid = $ing['id'];
        // Insert into pantry_items
        $sql = "INSERT INTO pantry_items (user_id, ingredient_id, quantity, unit) VALUES (?, ?, ?, ?)";
        try {
            $pdo->prepare($sql)->execute([$user_id, $iid, $qty, $unit]);
        } catch (PDOException $e) {
            // Likely duplicate entry, maybe update quantity instead? 
            // For now, we'll just show an error if duplicate
            $error = "Item already in pantry (or database error).";
        }
    } else {
        $error = "Ingredient not found in database. Try a simpler name (e.g., 'Milk').";
    }
}

// 3. Fetch Pantry Items
// JOIN ingredients using ingredients.id and pantry_items.ingredient_id
$sql = "SELECT i.product_name, pi.id, pi.quantity, pi.unit FROM pantry_items pi JOIN ingredients i ON pi.ingredient_id = i.id WHERE pi.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$pantry_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>My Pantry</title>
    <link rel="stylesheet" href="style/global.css">
</head>

<body class="<?php echo ($is_dark) ? 'dark-mode' : ''; ?>">

    <header class="app-header">
        <h1>Kitchen Pantry</h1>
        <div class="nav-links">
            <a href="index.php" class="active">My Pantry</a>
            <a href="recipes.php">Recipe Browser</a>
            <a href="my_favorites.php">Favorites</a>
            <a href="what_can_i_make.php">What Can I Make?</a>
        </div>
        <div class="profile-menu">
            <a href="settings.php">⚙️ Settings</a>
            <span style="margin: 0 10px;">Hi, <?php echo htmlspecialchars($first_name); ?></span>
            <a href="logout.php" class="button-logout">Log Out</a>
        </div>
    </header>

    <main>
        <div class="card-container" style="max-width: 700px;">
            <h2 style="margin-top:0;">Add to Pantry</h2>
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

            <form method="post" style="display:flex; gap:10px;">
                <input type="text" name="item_name" placeholder="Item Name (e.g. Eggs)" required
                    style="flex:2; margin-bottom:0;">
                <input type="number" name="quantity" placeholder="Qty" value="1" step="0.1"
                    style="flex:1; margin-bottom:0;">
                <input type="text" name="unit" placeholder="Unit (e.g. pcs)" style="flex:1; margin-bottom:0;">
                <button type="submit" class="btn-primary" style="width: auto;">Add</button>
            </form>
        </div>

        <div class="card-container" style="max-width: 700px;">
            <h2 style="margin-top:0;">My Ingredients (<?php echo count($pantry_items); ?>)</h2>

            <?php if (empty($pantry_items)): ?>
                <p style="color:var(--text-muted); text-align:center;">Your pantry is empty.</p>
            <?php else: ?>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($pantry_items as $item): ?>
                        <div
                            style="background: var(--bg-color); padding: 8px 15px; border-radius: 20px; border: 1px solid var(--border-color); display:flex; align-items:center; gap: 10px;">
                            <span>
                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                <small
                                    style="color:var(--text-muted)">(<?php echo $item['quantity'] . ' ' . $item['unit']; ?>)</small>
                            </span>
                            <a href="delete_item.php?id=<?php echo $item['id']; ?>"
                                style="color:var(--danger-color); font-weight:bold; font-size:1.1rem; text-decoration:none;">&times;</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>

</html>