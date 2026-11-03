<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit();
}

$itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="detail-container" style="max-width: 800px;">
    <div class="summary-card" style="text-align: center; padding: 50px 30px;">
        <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--gold-glow); border: 2px solid var(--primary-gold); color: var(--primary-gold); font-size: 2.5rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i class="fas fa-check"></i>
        </div>

        <h1 style="font-family: var(--font-heading); font-size: 2.2rem; margin-bottom: 10px;">Order Placed Successfully!</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 25px;">
            Thank you for choosing TIMEAURA. Your order <strong>#<?php echo sanitize($order['order_number']); ?></strong> has been confirmed.
        </p>

        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 25px; text-align: left; margin-bottom: 30px;">
            <h3 style="font-family: var(--font-heading); color: var(--primary-gold); margin-bottom: 15px; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                Customer & Shipping Details
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 0.9rem; color: var(--text-main);">
                <div><strong>Customer Name:</strong> <?php echo sanitize($order['customer_name']); ?></div>
                <div><strong>Mobile (+91):</strong> <?php echo sanitize($order['mobile_number']); ?></div>
                <div><strong>Email:</strong> <?php echo sanitize($order['email']); ?></div>
                <div><strong>Order Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
                <div style="grid-column: span 2;"><strong>Shipping Address:</strong> <?php echo sanitize($order['address'] . ', ' . $order['city'] . ', ' . $order['state'] . ' - ' . $order['pincode']); ?></div>
            </div>
        </div>

        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 25px; text-align: left; margin-bottom: 30px;">
            <h3 style="font-family: var(--font-heading); color: var(--primary-gold); margin-bottom: 15px; font-size: 1.1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                Ordered Watches
            </h3>
            <?php foreach ($orderItems as $item): ?>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.95rem;">
                    <div>
                        <strong><?php echo sanitize($item['product_name']); ?></strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">Qty: <?php echo $item['quantity']; ?> &times; <?php echo formatPrice($item['price']); ?></div>
                    </div>
                    <div style="font-weight: 700; color: var(--primary-gold);"><?php echo formatPrice($item['total']); ?></div>
                </div>
            <?php endforeach; ?>

            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; margin-top: 15px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                <span>Grand Total Paid</span>
                <span style="color: var(--primary-gold);"><?php echo formatPrice($order['grand_total']); ?></span>
            </div>
        </div>

        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="products.php" class="btn btn-gold"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
            <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> Print Receipt</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
