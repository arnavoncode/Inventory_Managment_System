<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$conn = getConnection();

$sale = $conn->query("SELECT * FROM sales WHERE id=$id")->fetch_assoc();
if (!$sale) { header('Location: sales.php'); exit(); }

$items = $conn->query("SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id=p.id WHERE si.sale_id=$id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= htmlspecialchars($sale['invoice_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f8fafc; color: #1e293b; }
        .page { max-width: 680px; margin: 40px auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.1); }
        .header { background: #0a0f1e; color: white; padding: 32px; display: flex; justify-content: space-between; align-items: flex-start; }
        .header h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; }
        .header h1 span { color: #f97316; }
        .header p { color: #94a3b8; font-size: 0.85rem; margin-top: 4px; }
        .invoice-label { text-align: right; }
        .invoice-label .inv-num { font-family: 'Syne', sans-serif; font-size: 1rem; color: #f97316; }
        .invoice-label .inv-date { color: #94a3b8; font-size: 0.85rem; margin-top: 4px; }
        .meta { padding: 24px 32px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; gap: 40px; }
        .meta-group label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 600; }
        .meta-group p { font-weight: 600; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #f1f5f9; }
        th { padding: 12px 16px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; text-align: left; }
        td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        .table-section { padding: 0 32px; }
        .totals { padding: 20px 32px; border-top: 2px solid #f97316; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; }
        .total-row.grand { font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; color: #f97316; }
        .footer-note { padding: 20px 32px; text-align: center; color: #94a3b8; font-size: 0.8rem; border-top: 1px solid #f1f5f9; }
        .actions { padding: 20px 32px; display: flex; gap: 12px; justify-content: center; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 8px; font-size: 0.875rem; font-weight: 500; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background: #f97316; color: white; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
        @media print { .actions { display: none; } .page { box-shadow: none; margin: 0; border-radius: 0; } }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div>
                <h1>Stock<span>Master</span></h1>
                <p>General Store — Tax Invoice</p>
            </div>
            <div class="invoice-label">
                <div class="inv-num"><?= htmlspecialchars($sale['invoice_number']) ?></div>
                <div class="inv-date"><?= date('d M Y, h:i A', strtotime($sale['sale_date'])) ?></div>
            </div>
        </div>

        <div class="meta">
            <div class="meta-group">
                <label>Customer</label>
                <p><?= htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer') ?></p>
            </div>
            <div class="meta-group">
                <label>Payment</label>
                <p><?= strtoupper($sale['payment_method']) ?></p>
            </div>
            <div class="meta-group">
                <label>Status</label>
                <p style="color:#22c55e">✅ Paid</p>
            </div>
        </div>

        <div class="table-section" style="padding-top:20px">
            <table>
                <thead><tr><th>#</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php $i=1; while($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td style="color:#94a3b8"><?= $i++ ?></td>
                        <td style="font-weight:500"><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>₹<?= number_format($item['unit_price'], 2) ?></td>
                        <td style="font-weight:600">₹<?= number_format($item['subtotal'], 2) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="totals">
            <div class="total-row"><span>Subtotal</span><span>₹<?= number_format($sale['total_amount'], 2) ?></span></div>
            <div class="total-row"><span style="color:#94a3b8">Tax (0%)</span><span style="color:#94a3b8">₹0.00</span></div>
            <div class="total-row grand"><span>TOTAL</span><span>₹<?= number_format($sale['total_amount'], 2) ?></span></div>
        </div>

        <div class="footer-note">Thank you for shopping with us! 🙏 Keep this invoice for your records.</div>

        <div class="actions">
            <button class="btn btn-primary" onclick="window.print()">🖨️ Print Invoice</button>
            <a href="new_sale.php" class="btn btn-secondary">🛒 New Sale</a>
            <a href="dashboard.php" class="btn btn-secondary">📊 Dashboard</a>
        </div>
    </div>
</body>
</html>
