<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$conn = getConnection();
$msg = ''; $msgType = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name       = trim($_POST['name'] ?? '');
        $cat_id     = intval($_POST['category_id'] ?? 0);
        $sku        = trim($_POST['sku'] ?? '');
        $qty        = intval($_POST['quantity'] ?? 0);
        $price      = floatval($_POST['price'] ?? 0);
        $cost_price = floatval($_POST['cost_price'] ?? 0);
        $threshold  = intval($_POST['low_stock_threshold'] ?? 10);
        $desc       = trim($_POST['description'] ?? '');

        if (!$name) { $msg = 'Product name is required.'; $msgType = 'danger'; }
        else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO products (name,category_id,sku,quantity,price,cost_price,low_stock_threshold,description) VALUES(?,?,?,?,?,?,?,?)");
                $stmt->bind_param("sisiidis", $name, $cat_id, $sku, $qty, $price, $cost_price, $threshold, $desc);
                if ($stmt->execute()) { $msg = 'Product added successfully!'; $msgType = 'success'; }
                else { $msg = 'Error: ' . $conn->error; $msgType = 'danger'; }
            } else {
                $id = intval($_POST['product_id'] ?? 0);
                $stmt = $conn->prepare("UPDATE products SET name=?,category_id=?,sku=?,quantity=?,price=?,cost_price=?,low_stock_threshold=?,description=? WHERE id=?");
                $stmt->bind_param("sisiidisi", $name, $cat_id, $sku, $qty, $price, $cost_price, $threshold, $desc, $id);
                if ($stmt->execute()) { $msg = 'Product updated successfully!'; $msgType = 'success'; }
                else { $msg = 'Error: ' . $conn->error; $msgType = 'danger'; }
            }
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['product_id'] ?? 0);
        $conn->query("DELETE FROM products WHERE id=$id");
        $msg = 'Product deleted.'; $msgType = 'warning';
    }
}

// Search / filter
$search = trim($_GET['search'] ?? '');
$catFilter = intval($_GET['cat'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
if ($search) $where .= " AND (p.name LIKE '%".  $conn->real_escape_string($search) ."%' OR p.sku LIKE '%". $conn->real_escape_string($search) ."%')";
if ($catFilter) $where .= " AND p.category_id = $catFilter";

$totalRows = $conn->query("SELECT COUNT(*) as c FROM products p $where")->fetch_assoc()['c'];
$totalPages = ceil($totalRows / $perPage);

$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$catArr = [];
while($c = $categories->fetch_assoc()) $catArr[] = $c;

include 'includes/header.php';
?>
📦 Products
        </h1>
        <div class="topbar-actions">
            <div class="badge-time" id="current-time"></div>
            <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Product</button>
        </div>
    </div>

    <div class="content">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>">
                <?= $msgType === 'success' ? '✅' : ($msgType === 'danger' ? '❌' : '⚠️') ?> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>All Products <span style="color:var(--muted);font-weight:400;font-size:0.85rem">(<?= $totalRows ?>)</span></h3>
                <div style="display:flex;gap:10px;align-items:center">
                    <form method="GET" style="display:flex;gap:8px;align-items:center">
                        <div class="search-bar">
                            <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <select name="cat" style="width:auto;padding:9px 12px">
                            <option value="0">All Categories</option>
                            <?php foreach($catArr as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                        <?php if($search || $catFilter): ?>
                            <a href="products.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="table-wrap">
                <?php if ($products->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th><th>Product Name</th><th>SKU</th><th>Category</th>
                            <th>Stock</th><th>Sell Price</th><th>Cost Price</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = $offset+1; while($p = $products->fetch_assoc()): ?>
                        <tr class="<?= $p['quantity'] <= $p['low_stock_threshold'] ? 'low-stock-row' : '' ?>">
                            <td style="color:var(--muted)"><?= $i++ ?></td>
                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($p['name']) ?></div>
                                <?php if($p['description']): ?>
                                    <div style="font-size:0.75rem;color:var(--muted);margin-top:2px"><?= htmlspecialchars(substr($p['description'],0,40)) ?>...</div>
                                <?php endif; ?>
                            </td>
                            <td><span style="font-family:monospace;font-size:0.8rem;color:var(--accent)"><?= htmlspecialchars($p['sku']) ?></span></td>
                            <td><?= htmlspecialchars($p['cat_name'] ?? 'Uncategorized') ?></td>
                            <td>
                                <span style="font-weight:700;color:<?= $p['quantity'] == 0 ? 'var(--danger)' : ($p['quantity'] <= $p['low_stock_threshold'] ? 'var(--warning)' : 'var(--success)') ?>">
                                    <?= $p['quantity'] ?>
                                </span>
                                <span style="font-size:0.75rem;color:var(--muted)"> / <?= $p['low_stock_threshold'] ?> min</span>
                            </td>
                            <td style="font-weight:600">₹<?= number_format($p['price'], 2) ?></td>
                            <td style="color:var(--muted)">₹<?= number_format($p['cost_price'], 2) ?></td>
                            <td>
                                <?php if ($p['quantity'] == 0): ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php elseif ($p['quantity'] <= $p['low_stock_threshold']): ?>
                                    <span class="badge badge-warning">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <button class="btn btn-secondary btn-sm" onclick='editProduct(<?= json_encode($p) ?>)'>✏️</button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="icon">📦</div>
                        <h3>No products found</h3>
                        <p>Add your first product to get started.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for($pg=1; $pg<=$totalPages; $pg++): ?>
                    <a href="?page=<?= $pg ?>&search=<?= urlencode($search) ?>&cat=<?= $catFilter ?>" class="page-btn <?= $pg==$page?'active':'' ?>"><?= $pg ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ Add New Product</h3>
            <button class="modal-close" onclick="closeModal('addModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Basmati Rice 5kg">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id">
                            <option value="0">Select Category</option>
                            <?php foreach($catArr as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU / Barcode</label>
                        <input type="text" name="sku" placeholder="e.g. GRC001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Selling Price (₹) *</label>
                        <input type="number" name="price" min="0" step="0.01" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cost Price (₹)</label>
                        <input type="number" name="cost_price" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Stock (Qty)</label>
                        <input type="number" name="quantity" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Low Stock Alert At</label>
                        <input type="number" name="low_stock_threshold" min="1" value="10">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Description</label>
                        <textarea name="description" placeholder="Optional product description..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Edit Product</h3>
            <button class="modal-close" onclick="closeModal('editModal')">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="product_id" id="edit_id">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="edit_category_id">
                            <option value="0">Select Category</option>
                            <?php foreach($catArr as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU / Barcode</label>
                        <input type="text" name="sku" id="edit_sku">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Selling Price (₹) *</label>
                        <input type="number" name="price" id="edit_price" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cost Price (₹)</label>
                        <input type="number" name="cost_price" id="edit_cost_price" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Stock (Qty)</label>
                        <input type="number" name="quantity" id="edit_quantity" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Low Stock Alert At</label>
                        <input type="number" name="low_stock_threshold" id="edit_threshold" min="1">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function editProduct(p) {
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_name').value = p.name;
    document.getElementById('edit_category_id').value = p.category_id || 0;
    document.getElementById('edit_sku').value = p.sku || '';
    document.getElementById('edit_price').value = p.price;
    document.getElementById('edit_cost_price').value = p.cost_price;
    document.getElementById('edit_quantity').value = p.quantity;
    document.getElementById('edit_threshold').value = p.low_stock_threshold;
    document.getElementById('edit_description').value = p.description || '';
    openModal('editModal');
}

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if(e.target === o) o.classList.remove('active'); });
});
</script>
