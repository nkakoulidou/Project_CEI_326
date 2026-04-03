<?php
$pageTitle = 'Διαχείριση Χρηστών';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$action  = $_GET['action'] ?? 'list';
$message = '';
$error   = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) { $error = 'Μη έγκυρο αίτημα.'; goto done; }

    $act = $_POST['action'] ?? '';

    if ($act === 'add' || $act === 'update') {
        $email    = trim($_POST['email'] ?? '');
        $fname    = trim($_POST['first_name'] ?? '');
        $lname    = trim($_POST['last_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $role     = in_array($_POST['role'], ['admin','candidate']) ? $_POST['role'] : 'candidate';
        $password = trim($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Μη έγκυρο email.'; goto done; }

        if ($act === 'add') {
            if (strlen($password) < 6) { $error = 'Ο κωδικός πρέπει να είναι τουλάχιστον 6 χαρακτήρες.'; goto done; }
            $stmt = $db->prepare("INSERT INTO users (email, password_hash, role, first_name, last_name, phone, is_active, email_verified) VALUES (?,?,?,?,?,?,1,1)");
            try {
                $stmt->execute([$email, hashPassword($password), $role, $fname, $lname, $phone]);
                logAudit($_SESSION['user_id'], 'user_create', 'user', $db->lastInsertId());
                $message = 'Ο χρήστης δημιουργήθηκε επιτυχώς.';
            } catch (PDOException $e) {
                $error = 'Το email υπάρχει ήδη.';
            }
        } else {
            $id = (int)($_POST['user_id'] ?? 0);
            $sql = "UPDATE users SET email=?, first_name=?, last_name=?, phone=?, role=? WHERE id=?";
            $params = [$email, $fname, $lname, $phone, $role, $id];
            if ($password) { $sql = "UPDATE users SET email=?, first_name=?, last_name=?, phone=?, role=?, password_hash=? WHERE id=?"; $params = [$email, $fname, $lname, $phone, $role, hashPassword($password), $id]; }
            $db->prepare($sql)->execute($params);
            logAudit($_SESSION['user_id'], 'user_update', 'user', $id);
            $message = 'Ο χρήστης ενημερώθηκε.';
        }
        $action = 'list';
    }

    if ($act === 'delete') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) { $error = 'Δεν μπορείτε να διαγράψετε τον εαυτό σας.'; goto done; }
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        logAudit($_SESSION['user_id'], 'user_delete', 'user', $id);
        $message = 'Ο χρήστης διαγράφηκε.';
        $action = 'list';
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['user_id'] ?? 0);
        $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id=?")->execute([$id]);
        $message = 'Η κατάσταση ενημερώθηκε.';
        $action = 'list';
    }
}
done:

// Fetch user for edit
$editUser = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editUser = $stmt->fetch();
}

// Fetch users list
$page  = max(1, (int)($_GET['p'] ?? 1));
$limit = ITEMS_PER_PAGE;
$offset= ($page - 1) * $limit;
$q     = trim($_GET['q'] ?? '');
$where = $q ? "WHERE (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)" : "";
$params = $q ? ["%$q%","%$q%","%$q%"] : [];
$total = $db->prepare("SELECT COUNT(*) FROM users $where");
$total->execute($params);
$totalRows = $total->fetchColumn();
$totalPages= ceil($totalRows / $limit);
$stmt = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="/pinakes/admin/index.php">Admin</a><span class="breadcrumb-sep">›</span><span>Manage Users</span></div>
        <h1>👥 Διαχείριση Χρηστών</h1>
        <p>Προβολή και διαχείριση εγγεγραμμένων χρηστών.</p>
    </div>
</div>

<section class="section-gap">
    <div class="container">
        <?php if ($message): ?><div class="alert alert-success" data-dismiss="4000"><?= sanitize($message) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-danger"  data-dismiss="5000"><?= sanitize($error)   ?></div><?php endif; ?>

        <!-- Add/Edit Form -->
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="card mb-4">
            <div class="card-header"><h3><?= $action === 'add' ? '➕ Νέος Χρήστης' : '✏️ Επεξεργασία Χρήστη' ?></h3></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'add' ?>">
                    <?php if ($editUser): ?><input type="hidden" name="user_id" value="<?= $editUser['id'] ?>"><?php endif; ?>
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Όνομα</label>
                            <input type="text" name="first_name" class="form-control" value="<?= sanitize($editUser['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Επίθετο</label>
                            <input type="text" name="last_name" class="form-control" value="<?= sanitize($editUser['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= sanitize($editUser['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Τηλέφωνο</label>
                            <input type="text" name="phone" class="form-control" value="<?= sanitize($editUser['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ρόλος</label>
                            <select name="role" class="form-control">
                                <option value="candidate" <?= ($editUser['role'] ?? '') === 'candidate' ? 'selected' : '' ?>>Υποψήφιος</option>
                                <option value="admin"     <?= ($editUser['role'] ?? '') === 'admin'     ? 'selected' : '' ?>>Διαχειριστής</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Κωδικός <?= $action === 'edit' ? '(αφήστε κενό για να μην αλλάξει)' : '' ?></label>
                            <input type="password" name="password" class="form-control" <?= $action === 'add' ? 'required minlength="6"' : '' ?> placeholder="••••••••">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><?= $action === 'edit' ? 'Αποθήκευση' : 'Δημιουργία' ?></button>
                        <a href="/pinakes/admin/users.php" class="btn btn-ghost">Ακύρωση</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <h3>Χρήστες (<?= $totalRows ?>)</h3>
                <a href="/pinakes/admin/users.php?action=add" class="btn btn-primary btn-sm">➕ Νέος</a>
            </div>
            <div class="card-body" style="padding-bottom:0;">
                <form method="GET" class="d-flex gap-2 mb-3">
                    <input type="text" name="q" class="form-control" placeholder="Αναζήτηση χρήστη..." value="<?= sanitize($q) ?>" style="max-width:300px;">
                    <button type="submit" class="btn btn-primary btn-sm">Αναζήτηση</button>
                    <?php if ($q): ?><a href="/pinakes/admin/users.php" class="btn btn-ghost btn-sm">✕</a><?php endif; ?>
                </form>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>#</th><th>Όνομα</th><th>Email</th><th>Ρόλος</th><th>Κατάσταση</th><th>Εγγραφή</th><th>Ενέργειες</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= sanitize($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-blue' : 'badge-gray' ?>"><?= $u['role'] === 'admin' ? 'Admin' : 'Υποψήφιος' ?></span></td>
                        <td><span class="badge <?= $u['is_active'] ? 'badge-green' : 'badge-red' ?>"><?= $u['is_active'] ? 'Ενεργός' : 'Ανενεργός' ?></span></td>
                        <td class="text-small text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="/pinakes/admin/users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-ghost btn-sm">✏️</a>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm" title="<?= $u['is_active'] ? 'Απενεργοποίηση' : 'Ενεργοποίηση' ?>"><?= $u['is_active'] ? '🔒' : '🔓' ?></button>
                                </form>
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Διαγραφή χρήστη;')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?><tr><td colspan="7" class="text-center text-muted" style="padding:2rem">Δεν βρέθηκαν χρήστες</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?p=<?= $i ?><?= $q ? '&q='.urlencode($q) : '' ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
