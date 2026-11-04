<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $stmt = $pdo->prepare("DELETE FROM canciones WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

header('Location: index.php#biblioteca');
exit;
?>