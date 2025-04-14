
<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php?page=login');
    exit();
}

// Récupération des catégories pour les ajouter dans la liste d'ajout de film (on pourra en rajouter)
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Gestion des actions d'ajout, de modification et suppression des films
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $title = trim($_POST['title']);
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        $movie_id = isset($_POST['movie_id']) ? $_POST['movie_id'] : null;
        $image_path = null;

        // Gestion des image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $uploadDir = 'uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $newFilename = uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
                    $image_path = $uploadDir . $newFilename;
                }
            }
        }

        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO movies (user_id, title, image_path, category_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $image_path, $category_id]);
        } else {

            if ($image_path) {
                $stmt = $pdo->prepare("UPDATE movies SET title = ?, image_path = ?, category_id = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $image_path, $category_id, $movie_id, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE movies SET title = ?, category_id = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $category_id, $movie_id, $_SESSION['user_id']]);
            }
        }
        header('Location: index.php?page=dashboard');
        exit();
    } elseif ($_POST['action'] === 'delete' && isset($_POST['movie_id'])) {
        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['movie_id'], $_SESSION['user_id']]);
        header('Location: index.php?page=dashboard');
        exit();
    }
}

$editMovie = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['edit'], $_SESSION['user_id']]);
    $editMovie = $stmt->fetch();
}

$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalMovies = $stmt->fetchColumn();
$totalPages = ceil($totalMovies / $perPage);

$stmt = $pdo->prepare("
    SELECT m.*, c.name as category_name 
    FROM movies m 
    LEFT JOIN categories c ON m.category_id = c.id 
    WHERE m.user_id = :user_id 
    ORDER BY m.created_at DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$movies = $stmt->fetchAll();
?>

<div class="dashboard">
    <div class="dashboard-header">
        <div>
            <h1>Ma Watchlist</h1>
            <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
        <div class="header-actions">
            <a href="?page=dashboard&show=add" class="btn btn-primary">Ajouter un film</a>
            <a href="logout.php" class="btn btn-outline">Déconnexion</a>
        </div>
    </div>

    <?php if (isset($_GET['show']) && $_GET['show'] === 'add'): ?>
        <div class="form-container">
            <h2>Ajouter un film</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label for="title">Titre du film</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="category">Catégorie</label>
                    <select name="category_id" id="category" class="form-select">
                        <option value="">Sélectionner une catégorie</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Image du film</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <div class="form-actions">
                    <a href="?page=dashboard" class="btn btn-outline">Annuler</a>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($editMovie): ?>
        <div class="form-container">
            <h2>Modifier le film</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="movie_id" value="<?php echo $editMovie['id']; ?>">

                <div class="form-group">
                    <label for="edit_title">Titre du film</label>
                    <input type="text" id="edit_title" name="title" value="<?php echo htmlspecialchars($editMovie['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="edit_category">Catégorie</label>
                    <select name="category_id" id="edit_category" class="form-select">
                        <option value="">Sélectionner une catégorie</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $editMovie['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_image">Nouvelle image (optionnel)</label>
                    <input type="file" id="edit_image" name="image" accept="image/*">
                    <?php if ($editMovie['image_path']): ?>
                        <div class="current-image">
                            <img src="<?php echo htmlspecialchars($editMovie['image_path']); ?>" alt="Image actuelle">
                            <p>Image actuelle</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <a href="?page=dashboard" class="btn btn-outline">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="movies-grid">
        <?php foreach ($movies as $movie): ?>
            <div class="movie-card">
                <?php if ($movie['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($movie['image_path']); ?>"
                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                         class="movie-image">
                <?php else: ?>
                    <div class="movie-image-placeholder">
                        <span>Pas d'image</span>
                    </div>
                <?php endif; ?>

                <div class="movie-info">
                    <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                    <?php if ($movie['category_name']): ?>
                        <span class="category-badge">
                            <?php echo htmlspecialchars($movie['category_name']); ?>
                        </span>
                    <?php endif; ?>
                    <div class="movie-actions">
                        <a href="?page=dashboard&edit=<?php echo $movie['id']; ?>" class="btn btn-outline btn-sm">
                            Modifier
                        </a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce film ?')">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=dashboard&p=<?php echo $i; ?>"
                   class="btn <?php echo $page === $i ? 'btn-primary' : 'btn-outline'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
