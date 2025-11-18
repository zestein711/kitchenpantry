<?php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Check if a recipe_id was sent in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // No ID? Send them back to where they came from
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'recipes.php');
    exit;
}

require __DIR__ . '/../src/config.php';

$recipe_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 3. Add to favorites
try {
    // First, check if it's already a favorite (prevents errors)
    $sql_check = "SELECT id FROM favorite_recipes WHERE user_id = ? AND recipe_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$user_id, $recipe_id]);

    if ($stmt_check->fetch() === false) {
        // Not a favorite yet, so INSERT it
        $sql_insert = "INSERT INTO favorite_recipes (user_id, recipe_id) VALUES (?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$user_id, $recipe_id]);
    }
    // If it *is* already a favorite, we just do nothing.

} catch (PDOException $e) {
    // Handle database errors if necessary
    // For now, just redirect
}

// 4. Send the user right back to the page they were just on
header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'recipes.php');
exit;
?>