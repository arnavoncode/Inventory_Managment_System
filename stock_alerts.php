<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$conn = getConnection();

$outOfStock = $conn->query("SELECT p.*, c.name as cat FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.quantity = 0 ORDER BY p.name");
$lowStock   = $conn->query("SELECT p.*, c.name as cat FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.quantity > 0 AND p.quantity <= p.low_stock_threshold ORDER BY p.quantity ASC");
$healthyCount = $conn->query("SELECT COUNT(*) as c FROM products WHERE quantity > low_stock_threshold")->fetch_assoc()['c'];

// Handle restock
$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restock') {
    $id  = intval($_POST['product_id']);
    $add = intval($_POST['add_qty']);
    if ($add > 0) {
        $conn->query("UPDATE products SET quantity = quantity + $add WHERE id = $id");
        $msg = "Stock updated successfully!"; $msgType = 'success';
        // Refresh
        $outOfStock = $conn->query("SELECT p.*, c.name as cat FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.quantity = 0 ORDER BY p.name");
        $lowStock   = $conn->query("SELECT p.*, c.name as cat FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.quantity > 0 AND p.quantity <= p.low_stock_threshold ORDER BY p.quantity ASC");
    }
}

include 'includes/header.php';
?>
⚠️ Stock Alerts
        </h1>
        <div class="topbar-actions"><div class="badge-time" id="current-time"></div></div>
    </div>
    <div class="content">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom:24px">
            <div class="stat-card red">
                <div class="stat-icon">🚫</div>
                <div class="stat-value"><?= $outOfStock->num_rows ?></div>
                <div class="stat-label">Out of Stock</div>
            </div>
            <div class="stat-card" style="--card-color: var(--warning)">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value"><?= $lowStock->num_rows ?></div>
                <div class="stat-label">Low Stock</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?= $healthyCount ?></div>
                <div class="stat-label">Healthy Stock</div>
            </div>
        </div>

        <!-- OUT OF STOCK -->
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h3>🚫 Out of Stock (<?= $outOfStock->num_rows ?>)</h3></div>
            <?php if ($outOfStock->num_rows > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Restock</th></tr></thead>
                    <tbody>
                    <?php while($p = $outOfStock->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($p['name']) ?></td>
                            <td style="font-family:monospace;color:var(--accent)"><?= htmlspecialchars($p['sku']) ?></td>
                            <td><?= htmlspecialchars($p['cat'] ?? '—') ?></td>
                            <td>₹<?= number_format($p['price'],2) ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="openRestock(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $p['quantity'] ?>)">📦 Restock</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state"><div class="icon">✅</div><p>No out-of-stock items!</p></div>
            <?php endif; ?>
        </div>

        <!-- LOW STOCK -->
        <div class="card">
            <div class="card-header"><h3>⚠️ Low Stock (<?= $lowStock->num_rows ?>)</h3></div>
            <?php if ($lowStock->num_rows > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Current Qty</th><th>Min Threshold</th><th>Restock</th></tr></thead>
                    <tbody>
                    <?php while($p = $lowStock->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($p['name']) ?></td>
                            <td style="font-family:monospace;color:var(--accent)"><?= htmlspecialchars($p['sku']) ?></td>
                            <td><?= htmlspecialchars($p['cat'] ?? '—') ?></td>
                            <td>
                                <span style="color:var(--warning);font-weight:700"><?= $p['quantity'] ?></span>
                                <div style="margin-top:4px;background:var(--border);border-radius:4px;height:4px;width:80px">
                                    <div style="background:var(--warning);height:4px;border-radius:4px;width:<?= min(100, ($p['quantity']/$p['low_stock_threshold'])*100) ?>%"></div>
                                </div>
                            </td>
                            <td><?= $p['low_stock_threshold'] ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="openRestock(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $p['quantity'] ?>)">📦 Restock</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state"><div class="icon">✅</div><p>All items have healthy stock levels!</p></div>
            <?php endif; ?>
        </div>
    </div>

<!-- Restock Modal -->
<div class="modal-overlay" id="restockModal">
    <div class="modal">
        <div class="modal-header"><h3>📦 Restock Product</h3><button class="modal-close" onclick="closeModal('restockModal')">×</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="restock">
            <input type="hidden" name="product_id" id="restock_id">
            <div class="modal-body">
                <p style="margin-bottom:16px;color:var(--text2)">Restocking: <strong id="restock_name" style="color:var(--text)"></strong></p>
                <p style="margin-bottom:16px;color:var(--muted)">Current Qty: <strong id="restock_current" style="color:var(--warning)"></strong></p>
                <div class="form-group">
                    <label class="form-label">Add Quantity</label>
                    <input type="number" name="add_qty" min="1" value="10" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('restockModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Stock</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function openRestock(id, name, qty) {
    document.getElementById('restock_id').value = id;
    document.getElementById('restock_name').textContent = name;
    document.getElementById('restock_current').textContent = qty;
    openModal('restockModal');
}
</script>
