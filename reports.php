<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$conn = getConnection();

// Period filter
$period = $_GET['period'] ?? '30';
$dateFilter = "AND sale_date >= DATE_SUB(NOW(), INTERVAL $period DAY)";

// Key metrics
$totalRevenue  = $conn->query("SELECT COALESCE(SUM(total_amount),0) as t FROM sales WHERE 1=1 $dateFilter")->fetch_assoc()['t'];
$totalOrders   = $conn->query("SELECT COUNT(*) as c FROM sales WHERE 1=1 $dateFilter")->fetch_assoc()['c'];
$totalProfit   = $conn->query("SELECT COALESCE(SUM((si.unit_price - p.cost_price)*si.quantity),0) as t FROM sale_items si JOIN products p ON si.product_id=p.id JOIN sales s ON si.sale_id=s.id WHERE 1=1 $dateFilter")->fetch_assoc()['t'];
$avgOrder      = $totalOrders > 0 ? $totalRevenue/$totalOrders : 0;

// Revenue by day
$dailyRev = $conn->query("SELECT DATE(sale_date) as d, SUM(total_amount) as t FROM sales WHERE 1=1 $dateFilter GROUP BY DATE(sale_date) ORDER BY d");
$revDays = []; $revAmounts = [];
while($r = $dailyRev->fetch_assoc()) { $revDays[] = date('d M', strtotime($r['d'])); $revAmounts[] = floatval($r['t']); }

// Revenue by payment method
$byPayment = $conn->query("SELECT payment_method, SUM(total_amount) as t, COUNT(*) as c FROM sales WHERE 1=1 $dateFilter GROUP BY payment_method");
$payLabels=[]; $payAmounts=[];
while($r = $byPayment->fetch_assoc()) { $payLabels[] = strtoupper($r['payment_method']); $payAmounts[] = floatval($r['t']); }

// Top products by revenue
$topByRev = $conn->query("SELECT p.name, SUM(si.quantity) as sold, SUM(si.subtotal) as revenue, SUM((si.unit_price-p.cost_price)*si.quantity) as profit FROM sale_items si JOIN products p ON si.product_id=p.id JOIN sales s ON si.sale_id=s.id WHERE 1=1 $dateFilter GROUP BY si.product_id ORDER BY revenue DESC LIMIT 8");

// Revenue by category
$byCat = $conn->query("SELECT c.name, SUM(si.subtotal) as revenue FROM sale_items si JOIN products p ON si.product_id=p.id LEFT JOIN categories c ON p.category_id=c.id JOIN sales s ON si.sale_id=s.id WHERE 1=1 $dateFilter GROUP BY p.category_id ORDER BY revenue DESC");
$catLabels=[]; $catAmounts=[];
while($r = $byCat->fetch_assoc()) { $catLabels[] = $r['name'] ?? 'Uncategorized'; $catAmounts[] = floatval($r['revenue']); }

// Stock value
$stockValue = $conn->query("SELECT COALESCE(SUM(quantity*cost_price),0) as t FROM products")->fetch_assoc()['t'];
$stockRetail = $conn->query("SELECT COALESCE(SUM(quantity*price),0) as t FROM products")->fetch_assoc()['t'];

include 'includes/header.php';
?>
📈 Reports
        </h1>
        <div class="topbar-actions">
            <div class="badge-time" id="current-time"></div>
            <form method="GET" style="display:flex;gap:8px">
                <select name="period" onchange="this.form.submit()" style="width:auto;padding:8px 12px">
                    <option value="7"  <?= $period=='7'  ?'selected':'' ?>>Last 7 Days</option>
                    <option value="30" <?= $period=='30' ?'selected':'' ?>>Last 30 Days</option>
                    <option value="90" <?= $period=='90' ?'selected':'' ?>>Last 90 Days</option>
                    <option value="365"<?= $period=='365'?'selected':'' ?>>Last Year</option>
                </select>
            </form>
        </div>
    </div>
    <div class="content">
        <!-- Metric Cards -->
        <div class="stats-grid" style="margin-bottom:24px">
            <div class="stat-card green"><div class="stat-icon">💰</div><div class="stat-value">₹<?= number_format($totalRevenue,0) ?></div><div class="stat-label">Total Revenue</div></div>
            <div class="stat-card blue"><div class="stat-icon">🏷️</div><div class="stat-value">₹<?= number_format($totalProfit,0) ?></div><div class="stat-label">Gross Profit</div></div>
            <div class="stat-card orange"><div class="stat-icon">🧾</div><div class="stat-value"><?= $totalOrders ?></div><div class="stat-label">Total Orders</div></div>
            <div class="stat-card" style="--bg-color:var(--info)"><div class="stat-icon">📊</div><div class="stat-value">₹<?= number_format($avgOrder,0) ?></div><div class="stat-label">Avg Order Value</div></div>
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
            <!-- Revenue Chart -->
            <div class="card">
                <div class="card-header"><h3>📈 Daily Revenue Trend</h3></div>
                <div class="card-body"><canvas id="revenueChart" height="180"></canvas></div>
            </div>
            <!-- Payment Method -->
            <div class="card">
                <div class="card-header"><h3>💳 By Payment Method</h3></div>
                <div class="card-body"><canvas id="paymentChart" height="180"></canvas></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;margin-bottom:20px">
            <!-- Top Products -->
            <div class="card">
                <div class="card-header"><h3>🏆 Top Products by Revenue</h3></div>
                <div class="table-wrap">
                    <?php if ($topByRev->num_rows > 0): ?>
                    <table>
                        <thead><tr><th>Product</th><th>Units Sold</th><th>Revenue</th><th>Profit</th></tr></thead>
                        <tbody>
                        <?php while($p = $topByRev->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:500"><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= $p['sold'] ?></td>
                                <td style="color:var(--success);font-weight:600">₹<?= number_format($p['revenue'],0) ?></td>
                                <td style="color:var(--accent)">₹<?= number_format($p['profit'],0) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="empty-state"><p>No sales data for this period</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="card">
                <div class="card-header"><h3>🏷️ Revenue by Category</h3></div>
                <div class="card-body">
                    <?php if (!empty($catLabels)): ?>
                    <canvas id="categoryChart" height="220"></canvas>
                    <?php else: ?>
                        <div class="empty-state"><p>No data</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stock Valuation -->
        <div class="card">
            <div class="card-header"><h3>📦 Current Stock Valuation</h3></div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px">
                <div style="text-align:center;padding:20px;background:var(--surface2);border-radius:12px">
                    <div style="font-size:2rem;margin-bottom:8px">📦</div>
                    <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--accent)">₹<?= number_format($stockValue,0) ?></div>
                    <div style="color:var(--muted);font-size:0.85rem;margin-top:4px">Cost Value</div>
                </div>
                <div style="text-align:center;padding:20px;background:var(--surface2);border-radius:12px">
                    <div style="font-size:2rem;margin-bottom:8px">🏷️</div>
                    <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--success)">₹<?= number_format($stockRetail,0) ?></div>
                    <div style="color:var(--muted);font-size:0.85rem;margin-top:4px">Retail Value</div>
                </div>
                <div style="text-align:center;padding:20px;background:var(--surface2);border-radius:12px">
                    <div style="font-size:2rem;margin-bottom:8px">💹</div>
                    <div style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--info)">₹<?= number_format($stockRetail-$stockValue,0) ?></div>
                    <div style="color:var(--muted);font-size:0.85rem;margin-top:4px">Potential Profit</div>
                </div>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartDefaults = {
    scales: {
        x: { grid: { color: 'rgba(31,45,69,0.8)' }, ticks: { color: '#64748b' } },
        y: { grid: { color: 'rgba(31,45,69,0.8)' }, ticks: { color: '#64748b' } }
    }
};

// Revenue Trend
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($revDays) ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode($revAmounts) ?>,
            borderColor: '#f97316',
            backgroundColor: 'rgba(249,115,22,0.1)',
            fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#f97316'
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: chartDefaults.scales }
});

// Payment Methods
new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($payLabels) ?>,
        datasets: [{
            data: <?= json_encode($payAmounts) ?>,
            backgroundColor: ['#f97316','#3b82f6','#22c55e','#a855f7'],
            borderWidth: 0, hoverOffset: 8
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 16 } } }, cutout: '65%' }
});

// Category
<?php if (!empty($catLabels)): ?>
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode($catAmounts) ?>,
            backgroundColor: ['#f97316','#3b82f6','#22c55e','#a855f7','#eab308','#ef4444'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true, indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(31,45,69,0.8)' }, ticks: { color: '#64748b', callback: v => '₹'+v } },
            y: { grid: { display: false }, ticks: { color: '#94a3b8' } }
        }
    }
});
<?php endif; ?>
</script>
