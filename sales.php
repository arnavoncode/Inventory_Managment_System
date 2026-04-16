<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$conn = getConnection();

$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['from'] ?? '';
$dateTo   = $_GET['to']   ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15; $offset = ($page-1)*$perPage;

$where = "WHERE 1=1";
if ($search) $where .= " AND (s.invoice_number LIKE '%".$conn->real_escape_string($search)."%' OR s.customer_name LIKE '%".$conn->real_escape_string($search)."%')";
if ($dateFrom) $where .= " AND DATE(s.sale_date) >= '".$conn->real_escape_string($dateFrom)."'";
if ($dateTo)   $where .= " AND DATE(s.sale_date) <= '".$conn->real_escape_string($dateTo)."'";

$totalRows  = $conn->query("SELECT COUNT(*) as c FROM sales s $where")->fetch_assoc()['c'];
$totalSales = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM sales s $where")->fetch_assoc()['t'];
$totalPages = ceil($totalRows / $perPage);

$sales = $conn->query("SELECT s.* FROM sales s $where ORDER BY s.sale_date DESC LIMIT $perPage OFFSET $offset");

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $sid = intval($_POST['sale_id']);
    // Restore stock
    $sItems = $conn->query("SELECT product_id, quantity FROM sale_items WHERE sale_id=$sid");
    while($si = $sItems->fetch_assoc()) {
        $conn->query("UPDATE products SET quantity = quantity + {$si['quantity']} WHERE id = {$si['product_id']}");
    }
    $conn->query("DELETE FROM sales WHERE id=$sid");
    header('Location: sales.php'); exit();
}

include 'includes/header.php';
?>
🧾 Sales History
        </h1>
        <div class="topbar-actions">
            <div class="badge-time" id="current-time"></div>
            <a href="new_sale.php" class="btn btn-primary">+ New Sale</a>
        </div>
    </div>
    <div class="content">
        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
            <div class="stat-card green"><div class="stat-icon">💰</div><div class="stat-value">₹<?= number_format($totalSales,0) ?></div><div class="stat-label">Total Revenue (Filtered)</div></div>
            <div class="stat-card blue"><div class="stat-icon">🧾</div><div class="stat-value"><?= $totalRows ?></div><div class="stat-label">Total Transactions</div></div>
            <div class="stat-card orange"><div class="stat-icon">📊</div><div class="stat-value">₹<?= $totalRows > 0 ? number_format($totalSales/$totalRows,0) : '0' ?></div><div class="stat-label">Average Sale Value</div></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>All Sales</h3>
                <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                    <div class="search-bar"><input type="text" name="search" placeholder="Invoice or customer..." value="<?= htmlspecialchars($search) ?>"></div>
                    <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" style="width:auto;padding:9px 12px">
                    <input type="date" name="to"   value="<?= htmlspecialchars($dateTo) ?>"   style="width:auto;padding:9px 12px">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <?php if($search||$dateFrom||$dateTo): ?><a href="sales.php" class="btn btn-secondary">Clear</a><?php endif; ?>
                </form>
            </div>
            <div class="table-wrap">
                <?php if ($sales->num_rows > 0): ?>
                <table>
                    <thead><tr><th>Invoice</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Date & Time</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while($s = $sales->fetch_assoc()):
                        $itemCount = $conn->query("SELECT SUM(quantity) as c FROM sale_items WHERE sale_id={$s['id']}")->fetch_assoc()['c'];
                    ?>
                        <tr>
                            <td><span style="font-family:monospace;color:var(--accent)"><?= htmlspecialchars($s['invoice_number']) ?></span></td>
                            <td style="font-weight:500"><?= htmlspecialchars($s['customer_name'] ?: 'Walk-in') ?></td>
                            <td><?= $itemCount ?> units</td>
                            <td style="font-weight:700;color:var(--success)">₹<?= number_format($s['total_amount'],2) ?></td>
                            <td><span class="badge badge-info"><?= strtoupper($s['payment_method']) ?></span></td>
                            <td style="color:var(--muted);font-size:0.8rem"><?= date('d M Y, h:i A', strtotime($s['sale_date'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a href="invoice.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">🧾 View</a>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this sale? Stock will be restored.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="sale_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state"><div class="icon">🧾</div><h3>No sales found</h3><p>Make your first sale!</p></div>
                <?php endif; ?>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for($pg=1;$pg<=$totalPages;$pg++): ?>
                    <a href="?page=<?=$pg?>&search=<?=urlencode($search)?>&from=<?=urlencode($dateFrom)?>&to=<?=urlencode($dateTo)?>" class="page-btn <?=$pg==$page?'active':''?>"><?=$pg?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>
