
<?php
session_start();
require_once 'config/pdo_config.php';

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
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<?php
switch($page) {
    case 'login':
        include 'pages/login.php';
        break;
    case 'register':
        include 'pages/register.php';
        break;
    case 'dashboard':
        include 'pages/dashboard.php';
        break;
    default:
        include 'pages/login.php';
}
?>
</body>
</html>

