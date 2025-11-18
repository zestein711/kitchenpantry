<?php
session_start(); // Always start the session

// 1. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in? Get out.
    header('Location: login.php');
    exit;
}

// 2. Check if an ID was actually sent
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // No ID? Go back to the pantry.
    header('Location: index.php');
    exit;
}

// 3. We have a user and an ID. Get the database.
require __DIR__ . '/../src/config.php';

$item_to_delete_id = $_GET['id'];
$current_user_id = $_SESSION['user_id'];

// 4. The Security Query
// This is the most important part. We delete the item
// *ONLY IF* the item ID matches AND the user_id matches.
// This stops a user from deleting other users' items by guessing IDs.
try {
    $sql = "DELETE FROM pantry_items WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    
    // Execute the query with both IDs
    $stmt->execute([$item_to_delete_id, $current_user_id]);

} catch (PDOException $e) {
    // If it fails, just go back to the pantry.
    // We could add an error message here, but it's cleaner to just... fail.
}

// 5. Job done. Go back to the pantry page.
header('Location: index.php');
exit;
?>