<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$conn = getConnection();

// Stats
$totalProducts   = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$lowStockCount   = $conn->query("SELECT COUNT(*) as c FROM products WHERE quantity <= low_stock_threshold")->fetch_assoc()['c'];
$todaySales      = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM sales WHERE DATE(sale_date)=CURDATE()")->fetch_assoc()['t'];
$totalCategories = $conn->query("SELECT COUNT(*) as c FROM categories")->fetch_assoc()['c'];
$monthlySales    = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM sales WHERE MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())")->fetch_assoc()['t'];
$totalSalesCount = $conn->query("SELECT COUNT(*) as c FROM sales")->fetch_assoc()['c'];

// Recent Sales
$recentSales = $conn->query("SELECT s.invoice_number, s.customer_name, s.total_amount, s.sale_date, s.payment_method FROM sales s ORDER BY s.sale_date DESC LIMIT 8");

// Low Stock items
$lowStock = $conn->query("SELECT p.name, p.quantity, p.low_stock_threshold, c.name as cat FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.quantity <= p.low_stock_threshold ORDER BY p.quantity ASC LIMIT 6");

// Sales by day for last 7 days
$salesChart = $conn->query("
    SELECT DATE(sale_date) as d, SUM(total_amount) as total
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(sale_date)
    ORDER BY d ASC
");

$chartDays = []; $chartTotals = [];
$chartData = [];
while($r = $salesChart->fetch_assoc()) { $chartData[$r['d']] = $r['total']; }
for($i=6; $i>=0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chartDays[] = date('D', strtotime($day));
    $chartTotals[] = $chartData[$day] ?? 0;
}

// Top selling products
$topProducts = $conn->query("
    SELECT p.name, SUM(si.quantity) as sold, SUM(si.subtotal) as revenue
    FROM sale_items si JOIN products p ON si.product_id=p.id
    GROUP BY si.product_id ORDER BY sold DESC LIMIT 5
");

$conn->close();

include 'includes/header.php';
?>
📊 Dashboard
        </h1>
        <div class="topbar-actions">
            <div class="badge-time" id="current-time"></div>
            <a href="new_sale.php" class="btn btn-primary">🛒 New Sale</a>
        </div>
    </div>

    <div class="content">
        <!-- STAT CARDS -->
        <div class="stats-grid">
            <div class="stat-card orange">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?= $totalProducts ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">💰</div>
                <div class="stat-value">₹<?= number_format($todaySales, 0) ?></div>
                <div class="stat-label">Today's Sales</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">📈</div>
                <div class="stat-value">₹<?= number_format($monthlySales, 0) ?></div>
                <div class="stat-label">This Month</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value"><?= $lowStockCount ?></div>
                <div class="stat-label">Low Stock Items</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1.6fr 1fr; gap:20px; margin-bottom:24px;">
            <!-- SALES CHART -->
            <div class="card">
                <div class="card-header">
                    <h3>📈 Sales — Last 7 Days</h3>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="200"></canvas>
                </div>
            </div>

            <!-- TOP PRODUCTS -->
            <div class="card">
                <div class="card-header">
                    <h3>🏆 Top Selling Products</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if ($topProducts->num_rows > 0): ?>
                    <table>
                        <thead><tr><th>Product</th><th>Sold</th><th>Revenue</th></tr></thead>
                        <tbody>
                        <?php while($p = $topProducts->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:500"><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= $p['sold'] ?> units</td>
                                <td style="color:var(--success)">₹<?= number_format($p['revenue'], 0) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state"><div class="icon">📊</div><p>No sales data yet</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1.6fr 1fr; gap:20px;">
            <!-- RECENT SALES -->
            <div class="card">
                <div class="card-header">
                    <h3>🧾 Recent Sales</h3>
                    <a href="sales.php" class="btn btn-secondary btn-sm">View All</a>
                </div>
                <div class="table-wrap">
                    <?php if ($recentSales->num_rows > 0): ?>
                    <table>
                        <thead><tr><th>Invoice</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php while($s = $recentSales->fetch_assoc()): ?>
                            <tr>
                                <td><span style="font-family:monospace;color:var(--accent)"><?= htmlspecialchars($s['invoice_number']) ?></span></td>
                                <td><?= htmlspecialchars($s['customer_name'] ?: 'Walk-in') ?></td>
                                <td style="font-weight:600">₹<?= number_format($s['total_amount'], 2) ?></td>
                                <td><span class="badge badge-info"><?= strtoupper($s['payment_method']) ?></span></td>
                                <td style="color:var(--muted);font-size:0.8rem"><?= date('d M, h:i A', strtotime($s['sale_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state"><div class="icon">🧾</div><p>No sales yet</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- LOW STOCK -->
            <div class="card">
                <div class="card-header">
                    <h3>⚠️ Low Stock Alerts</h3>
                    <a href="stock_alerts.php" class="btn btn-secondary btn-sm">View All</a>
                </div>
                <div class="table-wrap">
                    <?php if ($lowStock->num_rows > 0): ?>
                    <table>
                        <thead><tr><th>Product</th><th>Qty</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php while($p = $lowStock->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:500;font-size:0.85rem"><?= htmlspecialchars($p['name']) ?></div>
                                    <div style="font-size:0.75rem;color:var(--muted)"><?= htmlspecialchars($p['cat'] ?? 'Uncategorized') ?></div>
                                </td>
                                <td style="font-weight:700;color:<?= $p['quantity'] == 0 ? 'var(--danger)' : 'var(--warning)' ?>">
                                    <?= $p['quantity'] ?>
                                </td>
                                <td>
                                    <?php if ($p['quantity'] == 0): ?>
                                        <span class="badge badge-danger">Out</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Low</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state"><div class="icon">✅</div><p>All stock levels OK</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartDays) ?>,
        datasets: [{
            label: 'Sales (₹)',
            data: <?= json_encode($chartTotals) ?>,
            backgroundColor: 'rgba(249,115,22,0.3)',
            borderColor: '#f97316',
            borderWidth: 2,
            borderRadius: 8,
            hoverBackgroundColor: 'rgba(249,115,22,0.6)'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(31,45,69,0.8)' }, ticks: { color: '#64748b' } },
            y: { grid: { color: 'rgba(31,45,69,0.8)' }, ticks: { color: '#64748b', callback: v => '₹'+v } }
        }
    }
});
</script>
