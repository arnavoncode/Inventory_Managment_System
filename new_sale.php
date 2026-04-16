<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$conn = getConnection();
$msg = ''; $msgType = '';

// Process sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_sale') {
    $customer   = trim($_POST['customer_name'] ?? 'Walk-in Customer');
    $payment    = $_POST['payment_method'] ?? 'cash';
    $items      = json_decode($_POST['cart_items'] ?? '[]', true);
    $total      = floatval($_POST['total_amount'] ?? 0);

    if (!empty($items) && $total > 0) {
        $invoice = 'INV-' . date('Ymd') . '-' . str_pad(rand(1,9999), 4, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("INSERT INTO sales (invoice_number,customer_name,total_amount,payment_method,admin_id) VALUES(?,?,?,?,?)");
        $stmt->bind_param("ssdsi", $invoice, $customer, $total, $payment, $_SESSION['admin_id']);

        if ($stmt->execute()) {
            $saleId = $conn->insert_id;
            $ok = true;
            foreach ($items as $item) {
                $pid  = intval($item['id']);
                $qty  = intval($item['qty']);
                $uprice = floatval($item['price']);
                $sub  = $qty * $uprice;

                // Check stock
                $stock = $conn->query("SELECT quantity FROM products WHERE id=$pid")->fetch_assoc()['quantity'];
                if ($stock < $qty) { $ok = false; break; }

                $s2 = $conn->prepare("INSERT INTO sale_items (sale_id,product_id,quantity,unit_price,subtotal) VALUES(?,?,?,?,?)");
                $s2->bind_param("iiidd", $saleId, $pid, $qty, $uprice, $sub);
                $s2->execute();

                // Deduct stock
                $conn->query("UPDATE products SET quantity = quantity - $qty WHERE id = $pid");
            }

            if ($ok) {
                header("Location: invoice.php?id=$saleId");
                exit();
            } else {
                $conn->query("DELETE FROM sales WHERE id=$saleId");
                $msg = 'Insufficient stock for one or more items.'; $msgType = 'danger';
            }
        }
    } else {
        $msg = 'Cart is empty!'; $msgType = 'danger';
    }
}

// Get all products for search
$products = $conn->query("SELECT p.id, p.name, p.sku, p.price, p.quantity, c.name as cat FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.quantity > 0 ORDER BY p.name");
$productArr = [];
while($p = $products->fetch_assoc()) $productArr[] = $p;

include 'includes/header.php';
?>
🛒 New Sale
        </h1>
        <div class="topbar-actions"><div class="badge-time" id="current-time"></div></div>
    </div>
    <div class="content">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:20px">
            <!-- Product Search Panel -->
            <div>
                <div class="card" style="margin-bottom:16px">
                    <div class="card-header"><h3>🔍 Find Products</h3></div>
                    <div class="card-body">
                        <div class="search-bar" style="max-width:100%;margin-bottom:16px">
                            <input type="text" id="productSearch" placeholder="Search by name or SKU..." oninput="filterProducts()">
                        </div>
                        <div id="productList" style="max-height:420px;overflow-y:auto;display:grid;grid-template-columns:1fr 1fr;gap:8px"></div>
                    </div>
                </div>
            </div>

            <!-- Cart Panel -->
            <div>
                <div class="card">
                    <div class="card-header"><h3>🧾 Cart</h3><span id="cartCount" class="badge badge-info">0 items</span></div>
                    <div style="padding:16px">
                        <div class="form-group">
                            <label class="form-label">Customer Name</label>
                            <input type="text" id="customerName" placeholder="Walk-in Customer">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <select id="paymentMethod">
                                <option value="cash">💵 Cash</option>
                                <option value="card">💳 Card</option>
                                <option value="upi">📱 UPI</option>
                            </select>
                        </div>
                    </div>
                    <div id="cartItems" style="border-top:1px solid var(--border);max-height:260px;overflow-y:auto">
                        <div class="empty-state" style="padding:30px">
                            <div class="icon">🛒</div>
                            <p>Cart is empty</p>
                        </div>
                    </div>
                    <div style="padding:16px;border-top:1px solid var(--border)">
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.9rem;color:var(--muted)">
                            <span>Subtotal:</span> <span id="subtotal">₹0.00</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:16px;font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:700">
                            <span>Total:</span> <span id="totalDisplay" style="color:var(--accent)">₹0.00</span>
                        </div>
                        <button class="btn btn-primary" style="width:100%;padding:13px;font-size:1rem" onclick="processSale()">
                            ✅ Complete Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<form method="POST" id="saleForm" style="display:none">
    <input type="hidden" name="action" value="process_sale">
    <input type="hidden" name="customer_name" id="f_customer">
    <input type="hidden" name="payment_method" id="f_payment">
    <input type="hidden" name="cart_items" id="f_items">
    <input type="hidden" name="total_amount" id="f_total">
</form>

<?php include 'includes/footer.php'; ?>
<script>
const allProducts = <?= json_encode($productArr) ?>;
let cart = {};

function filterProducts() {
    const q = document.getElementById('productSearch').value.toLowerCase();
    const filtered = allProducts.filter(p => p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)));
    renderProducts(filtered.slice(0, 20));
}

function renderProducts(list) {
    const el = document.getElementById('productList');
    if (list.length === 0) {
        el.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:var(--muted);padding:20px">No products found</div>';
        return;
    }
    el.innerHTML = list.map(p => `
        <div onclick="addToCart(${p.id})" style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:12px;cursor:pointer;transition:all 0.15s" 
             onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="font-weight:600;font-size:0.85rem;margin-bottom:4px">${p.name}</div>
            <div style="font-size:0.75rem;color:var(--muted)">${p.cat || 'Uncategorized'}</div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;align-items:center">
                <span style="color:var(--accent);font-weight:700">₹${parseFloat(p.price).toFixed(2)}</span>
                <span style="font-size:0.72rem;padding:2px 8px;border-radius:20px;background:${p.quantity > 10 ? 'rgba(34,197,94,0.12)' : 'rgba(234,179,8,0.12)'};color:${p.quantity > 10 ? '#4ade80' : '#fde047'}">
                    ${p.quantity} left
                </span>
            </div>
        </div>
    `).join('');
}

function addToCart(id) {
    const p = allProducts.find(x => x.id == id);
    if (!p) return;
    if (cart[id]) {
        if (cart[id].qty >= p.quantity) { alert('Max stock reached!'); return; }
        cart[id].qty++;
    } else {
        cart[id] = { id: p.id, name: p.name, price: parseFloat(p.price), qty: 1, max: p.quantity };
    }
    renderCart();
}

function changeQty(id, delta) {
    if (!cart[id]) return;
    cart[id].qty += delta;
    if (cart[id].qty <= 0) delete cart[id];
    else if (cart[id].qty > cart[id].max) cart[id].qty = cart[id].max;
    renderCart();
}

function removeFromCart(id) { delete cart[id]; renderCart(); }

function renderCart() {
    const items = Object.values(cart);
    const el = document.getElementById('cartItems');
    document.getElementById('cartCount').textContent = items.length + ' items';

    if (items.length === 0) {
        el.innerHTML = '<div class="empty-state" style="padding:30px"><div class="icon">🛒</div><p>Cart is empty</p></div>';
        document.getElementById('subtotal').textContent = '₹0.00';
        document.getElementById('totalDisplay').textContent = '₹0.00';
        return;
    }

    let total = 0;
    el.innerHTML = items.map(item => {
        const sub = item.qty * item.price;
        total += sub;
        return `<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border)">
            <div style="flex:1;min-width:0">
                <div style="font-weight:500;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${item.name}</div>
                <div style="font-size:0.75rem;color:var(--muted)">₹${item.price.toFixed(2)} each</div>
            </div>
            <div style="display:flex;align-items:center;gap:6px">
                <button onclick="changeQty(${item.id}, -1)" style="width:26px;height:26px;border-radius:6px;background:var(--surface2);border:1px solid var(--border);color:var(--text);cursor:pointer">−</button>
                <span style="min-width:24px;text-align:center;font-weight:600">${item.qty}</span>
                <button onclick="changeQty(${item.id}, 1)" style="width:26px;height:26px;border-radius:6px;background:var(--surface2);border:1px solid var(--border);color:var(--text);cursor:pointer">+</button>
            </div>
            <div style="text-align:right;min-width:60px">
                <div style="font-weight:700;color:var(--accent)">₹${sub.toFixed(2)}</div>
                <button onclick="removeFromCart(${item.id})" style="font-size:0.7rem;color:var(--danger);background:none;border:none;cursor:pointer">Remove</button>
            </div>
        </div>`;
    }).join('');

    document.getElementById('subtotal').textContent = '₹' + total.toFixed(2);
    document.getElementById('totalDisplay').textContent = '₹' + total.toFixed(2);
}

function processSale() {
    const items = Object.values(cart);
    if (items.length === 0) { alert('Cart is empty!'); return; }
    const total = items.reduce((s, i) => s + i.qty * i.price, 0);
    document.getElementById('f_customer').value = document.getElementById('customerName').value || 'Walk-in Customer';
    document.getElementById('f_payment').value = document.getElementById('paymentMethod').value;
    document.getElementById('f_items').value = JSON.stringify(items);
    document.getElementById('f_total').value = total.toFixed(2);
    document.getElementById('saleForm').submit();
}

// Init
renderProducts(allProducts.slice(0, 20));
</script>
