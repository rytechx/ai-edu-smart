<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$uploadDir = __DIR__ . '/../uploads/hardware/';
$uploadWebPrefix = 'uploads/hardware/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$flash = '';
$error = '';

function saveUploadedImage(array $file, string $uploadDir, string $uploadWebPrefix): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        throw new RuntimeException('Image must be 3 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, or WEBP images are allowed.');
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $target = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Unable to save the uploaded image.');
    }

    return $uploadWebPrefix . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if ($id) {
                $stmt = $pdo->prepare("SELECT image_path FROM hardware_components WHERE id = ?");
                $stmt->execute([$id]);
                $old = $stmt->fetch();

                $pdo->prepare("DELETE FROM hardware_components WHERE id = ?")->execute([$id]);

                if ($old && str_starts_with((string)$old['image_path'], 'uploads/hardware/')) {
                    $oldPath = __DIR__ . '/../' . $old['image_path'];
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $flash = 'Hardware component deleted.';
            }
        } else {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $functionText = trim($_POST['function_text'] ?? '');
            $existingImage = trim($_POST['existing_image'] ?? '');

            if ($name === '') {
                throw new RuntimeException('Component name is required.');
            }

            $newImage = saveUploadedImage($_FILES['image'] ?? [], $uploadDir, $uploadWebPrefix);
            $imagePath = $newImage ?: $existingImage;

            if ($id) {
                $stmt = $pdo->prepare("
                    UPDATE hardware_components
                    SET name = ?, category = ?, function_text = ?, image_path = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $category, $functionText, $imagePath, $id]);
                $flash = 'Hardware component updated.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO hardware_components (name, category, function_text, image_path)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$name, $category, $functionText, $imagePath]);
                $flash = 'Hardware component added.';
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM hardware_components WHERE id = ? LIMIT 1");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

$components = $pdo->query("
    SELECT id, name, category, function_text, image_path
    FROM hardware_components
    ORDER BY name ASC
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Hardware | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> AI Edu Smart Admin
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">Dashboard</a>
    </div>
</nav>

<main class="container py-5">
    <section class="hero-card mb-4">
        <div>
            <span class="eyebrow">Activity Content</span>
            <h1 class="mt-2">Manage Hardware</h1>
            <p class="mb-0">Add component images, names, categories, and learning explanations.</p>
        </div>
        <div class="progress-badge">
            <span>Components</span>
            <strong><?= count($components) ?></strong>
        </div>
    </section>

    <?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <section class="content-card">
                <span class="eyebrow"><?= $editing ? 'Edit Component' : 'New Component' ?></span>
                <h2 class="h4 mt-2 mb-4"><?= $editing ? 'Update hardware' : 'Add hardware' ?></h2>

                <form method="post" enctype="multipart/form-data" class="vstack gap-3">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editing['image_path'] ?? '') ?>">

                    <div>
                        <label class="form-label">Component Name</label>
                        <input class="form-control" type="text" name="name" required
                               value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="form-label">Category</label>
                        <input class="form-control" type="text" name="category"
                               placeholder="Example: Memory, Storage, Processing"
                               value="<?= htmlspecialchars($editing['category'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="form-label">Explanation / Function</label>
                        <textarea class="form-control" rows="5" name="function_text"
                                  placeholder="Explain what this component does."><?= htmlspecialchars($editing['function_text'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="form-label">Component Image</label>
                        <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <div class="form-text">JPG, PNG, or WEBP. Maximum 3 MB.</div>
                    </div>

                    <?php if (!empty($editing['image_path'])): ?>
                        <div class="admin-image-preview">
                            <img src="../<?= htmlspecialchars($editing['image_path']) ?>" alt="Current hardware image">
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <?= $editing ? 'Save Changes' : 'Add Component' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a class="btn btn-outline-secondary" href="hardware.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
        </div>

        <div class="col-lg-7">
            <section class="content-card">
                <span class="eyebrow">Hardware Library</span>
                <h2 class="h4 mt-2 mb-4">Current components</h2>

                <?php if (!$components): ?>
                    <p class="text-secondary">No hardware components yet.</p>
                <?php else: ?>
                    <div class="hardware-admin-grid">
                        <?php foreach ($components as $component): ?>
                            <article class="hardware-admin-item">
                                <div class="hardware-admin-thumb">
                                    <?php if ($component['image_path']): ?>
                                        <img src="../<?= htmlspecialchars($component['image_path']) ?>" alt="<?= htmlspecialchars($component['name']) ?>">
                                    <?php else: ?>
                                        <span>?</span>
                                    <?php endif; ?>
                                </div>

                                <div class="hardware-admin-info">
                                    <strong><?= htmlspecialchars($component['name']) ?></strong>
                                    <small><?= htmlspecialchars($component['category'] ?: 'Uncategorized') ?></small>
                                </div>

                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$component['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this component?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$component['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
</body>
</html>
