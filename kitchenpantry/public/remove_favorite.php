<?php
session_start();

// 1. Check user & ID
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'recipes.php');
    exit;
}

require __DIR__ . '/../src/config.php';

$recipe_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 3. Remove from favorites
try {
    // This is safe: it only deletes if both user AND recipe match
    $sql = "DELETE FROM favorite_recipes WHERE user_id = ? AND recipe_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $recipe_id]);

} catch (PDOException $e) {
    // Handle errors
}

// 4. Send the user back
header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'recipes.php');
exit;
?>