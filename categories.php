<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$conn = getConnection();
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES(?,?)");
            $stmt->bind_param("ss", $name, $desc);
            $stmt->execute() ? ($msg='Category added!') && ($msgType='success') : ($msg=$conn->error) && ($msgType='danger');
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['cat_id']);
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $desc, $id);
        $stmt->execute(); $msg = 'Category updated!'; $msgType = 'success';
    } elseif ($action === 'delete') {
        $id = intval($_POST['cat_id']);
        $conn->query("DELETE FROM categories WHERE id=$id");
        $msg = 'Category deleted.'; $msgType = 'warning';
    }
}

$categories = $conn->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id=p.category_id GROUP BY c.id ORDER BY c.name");
include 'includes/header.php';
?>
🏷️ Categories
        </h1>
        <div class="topbar-actions">
            <div class="badge-time" id="current-time"></div>
            <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Category</button>
        </div>
    </div>
    <div class="content">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header"><h3>All Categories</h3></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>#</th><th>Category Name</th><th>Description</th><th>Products</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php $i=1; while($c = $categories->fetch_assoc()): ?>
                        <tr>
                            <td style="color:var(--muted)"><?= $i++ ?></td>
                            <td style="font-weight:600"><?= htmlspecialchars($c['name']) ?></td>
                            <td style="color:var(--muted)"><?= htmlspecialchars($c['description'] ?: '—') ?></td>
                            <td><span class="badge badge-info"><?= $c['product_count'] ?> items</span></td>
                            <td style="color:var(--muted);font-size:0.8rem"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <button class="btn btn-secondary btn-sm" onclick='editCat(<?= json_encode($c) ?>)'>✏️ Edit</button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete category? Products will become uncategorized.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header"><h3>➕ Add Category</h3><button class="modal-close" onclick="closeModal('addModal')">×</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Name *</label><input type="text" name="name" required placeholder="Category name"></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" placeholder="Optional..."></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header"><h3>✏️ Edit Category</h3><button class="modal-close" onclick="closeModal('editModal')">×</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="cat_id" id="edit_id">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Name *</label><input type="text" name="name" id="edit_name" required></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="edit_desc"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function editCat(c) {
    document.getElementById('edit_id').value = c.id;
    document.getElementById('edit_name').value = c.name;
    document.getElementById('edit_desc').value = c.description || '';
    openModal('editModal');
}
</script>
