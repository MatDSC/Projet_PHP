
<?php
session_start();
require_once 'pdo_config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'login';

if (!isset($_SESSION['user_id']) && !in_array($page, ['login', 'register'])) {
    header('Location: index.php?page=login');
    exit();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Watchlist</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
<?php
switch($page) {
    case 'login':
        include 'login.php';
        break;
    case 'register':
        include 'register.php';
        break;
    case 'dashboard':
        include 'dashboard.php';
        break;
    default:
        include 'login.php';
}
?>
</body>
</html>

