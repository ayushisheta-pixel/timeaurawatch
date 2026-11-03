<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$sessionId = session_id();
$cartStmt = $pdo->prepare("
    SELECT c.quantity, p.id as product_id, p.name, p.brand, p.price, p.stock_quantity 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.session_id = ?
");
$cartStmt->execute([$sessionId]);
$cartItems = $cartStmt->fetchAll();

if (count($cartItems) === 0) {
    header("Location: cart.php");
    exit();
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}
$deliveryCharge = ($subtotal > 2000) ? 0 : 150;
$grandTotal = $subtotal + $deliveryCharge;

$errorMsg = '';

// Handle Checkout Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['customer_name'] ?? '');
    $mobile = trim($_POST['mobile_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    if (empty($name) || empty($mobile) || empty($email) || empty($address) || empty($city) || empty($state) || empty($pincode)) {
        $errorMsg = "Please complete all mandatory checkout address fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address.";
    } elseif (!validateIndianMobile($mobile)) {
        $errorMsg = "Please provide a valid 10-digit Indian mobile number (+91).";
    } else {
        try {
            $pdo->beginTransaction();

            $formattedMobile = formatIndianMobile($mobile);
            $orderNumber = generateOrderNumber();

            // 1. Create Order Record
            $orderStmt = $pdo->prepare("
                INSERT INTO orders 
                (order_number, customer_name, mobile_number, email, address, city, state, pincode, subtotal, delivery_charge, grand_total, payment_status, order_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Cash on Delivery', 'Confirmed')
            ");
            $orderStmt->execute([
                $orderNumber, $name, $formattedMobile, $email, $address, $city, $state, $pincode, $subtotal, $deliveryCharge, $grandTotal
            ]);
            $orderId = $pdo->lastInsertId();

            // 2. Insert Order Items & Update Stock
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total) VALUES (?, ?, ?, ?, ?, ?)");
            $stockStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");

            foreach ($cartItems as $item) {
                $itemTotal = $item['price'] * $item['quantity'];
                $itemStmt->execute([
                    $orderId, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $itemTotal
                ]);

                // Decrement stock
                $stockStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }

            // 3. Clear Session Cart
            $clearCart = $pdo->prepare("DELETE FROM cart WHERE session_id = ?");
            $clearCart->execute([$sessionId]);

            $pdo->commit();

            header("Location: order_success.php?id=" . $orderId);
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Checkout Order Error: " . $e->getMessage());
            $errorMsg = "Order processing failed due to a database error. Please try again.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="detail-container">
    <div class="section-header" style="text-align: left; margin-bottom: 30px;">
        <span class="section-subtitle">Final Step</span>
        <h1 class="section-title"><i class="fas fa-lock"></i> Secure Checkout</h1>
    </div>

    <?php if ($errorMsg): ?>
        <div class="badge badge-danger" style="display:block; padding: 14px; margin-bottom: 25px; font-size: 0.92rem;">
            <i class="fas fa-exclamation-triangle"></i> <?php echo sanitize($errorMsg); ?>
        </div>
    <?php endif; ?>

    <form action="checkout.php" method="POST">
        <div class="catalog-container" style="grid-template-columns: 1fr 380px; padding: 0;">
            <!-- Delivery & Address Form -->
            <div class="summary-card" style="background: var(--bg-card);">
                <h3 style="font-family: var(--font-heading); margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    Shipping & Customer Details
                </h3>

                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="customer_name" class="form-control" required placeholder="e.g. Vikram Sharma" value="<?php echo isset($_POST['customer_name']) ? sanitize($_POST['customer_name']) : ''; ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Indian Mobile Number (+91) *</label>
                        <div class="phone-input-group">
                            <span class="phone-prefix">+91</span>
                            <input type="tel" name="mobile_number" class="form-control indian-mobile-input" required placeholder="9876543210" maxlength="13" value="<?php echo isset($_POST['mobile_number']) ? sanitize($_POST['mobile_number']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="vikram@example.com" value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Delivery Address *</label>
                    <textarea name="address" class="form-control" rows="3" required placeholder="Flat / House No., Building Name, Street / Locality"><?php echo isset($_POST['address']) ? sanitize($_POST['address']) : ''; ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">City *</label>
                        <input type="text" name="city" class="form-control" required placeholder="Mumbai" value="<?php echo isset($_POST['city']) ? sanitize($_POST['city']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State *</label>
                        <input type="text" name="state" class="form-control" required placeholder="Maharashtra" value="<?php echo isset($_POST['state']) ? sanitize($_POST['state']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pincode *</label>
                        <input type="text" name="pincode" class="form-control" maxlength="6" required placeholder="400001" value="<?php echo isset($_POST['pincode']) ? sanitize($_POST['pincode']) : ''; ?>">
                    </div>
                </div>
            </div>

            <!-- Order Items & Payment Summary -->
            <div>
                <div class="summary-card">
                    <h3 style="font-family: var(--font-heading); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                        Order Review
                    </h3>

                    <div style="max-height: 240px; overflow-y: auto; margin-bottom: 20px; padding-right: 5px;">
                        <?php foreach ($cartItems as $item): ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.9rem;">
                                <div>
                                    <div style="font-weight: 600; color: var(--text-main);"><?php echo sanitize($item['name']); ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">Qty: <?php echo $item['quantity']; ?> &times; <?php echo formatPrice($item['price']); ?></div>
                                </div>
                                <div style="font-weight: 700; color: var(--primary-gold);">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row">
                        <span>Items Subtotal</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Pan-India Delivery</span>
                        <span><?php echo $deliveryCharge === 0 ? '<span style="color:#10b981; font-weight:600;">FREE</span>' : formatPrice($deliveryCharge); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total Payable</span>
                        <span style="color: var(--primary-gold);"><?php echo formatPrice($grandTotal); ?></span>
                    </div>

                    <div style="background: var(--bg-surface); padding: 15px; border-radius: var(--radius-sm); margin: 20px 0; border: 1px dashed var(--border-gold);">
                        <i class="fas fa-money-bill-wave" style="color: var(--primary-gold);"></i> 
                        <strong style="color: var(--text-main); font-size: 0.9rem;"> Payment Method:</strong>
                        <span style="display:block; font-size:0.82rem; color:var(--text-muted); margin-top:4px;">Cash on Delivery / UPI Pay on Delivery</span>
                    </div>

                    <button type="submit" class="btn btn-gold" style="width: 100%;">
                        <i class="fas fa-check-circle"></i> Place Order Now
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
