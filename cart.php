<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$sessionId = session_id();
$userId = $_SESSION['user_id'] ?? null;

// Handle POST actions for Cart updates or deletes BEFORE HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($productId > 0) {
        if ($action === 'add' || $action === 'update') {
            // Validate Stock
            $st = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ?");
            $st->execute([$productId]);
            $prod = $st->fetch();

            if ($prod) {
                if ($quantity <= 0) {
                    if ($userId) {
                        $del = $pdo->prepare("DELETE FROM cart WHERE (session_id = ? OR user_id = ?) AND product_id = ?");
                        $del->execute([$sessionId, $userId, $productId]);
                    } else {
                        $del = $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?");
                        $del->execute([$sessionId, $productId]);
                    }
                    setFlash('success', 'Item removed from cart.');
                } elseif ($quantity > $prod['stock_quantity']) {
                    setFlash('error', 'Cannot set quantity higher than available stock (' . $prod['stock_quantity'] . ')');
                } else {
                    if ($userId) {
                        $chk = $pdo->prepare("SELECT id, quantity FROM cart WHERE (session_id = ? OR user_id = ?) AND product_id = ?");
                        $chk->execute([$sessionId, $userId, $productId]);
                    } else {
                        $chk = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
                        $chk->execute([$sessionId, $productId]);
                    }
                    $row = $chk->fetch();

                    if ($row) {
                        $newQty = ($action === 'add') ? ($row['quantity'] + $quantity) : $quantity;
                        if ($newQty > $prod['stock_quantity']) {
                            $newQty = $prod['stock_quantity'];
                            setFlash('error', 'Maximum available stock reached (' . $prod['stock_quantity'] . ' units).');
                        } else {
                            setFlash('success', 'Cart updated successfully.');
                        }
                        $up = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                        $up->execute([$newQty, $row['id']]);
                    } else {
                        $ins = $pdo->prepare("INSERT INTO cart (session_id, user_id, product_id, quantity) VALUES (?, ?, ?, ?)");
                        $ins->execute([$sessionId, $userId, $productId, $quantity]);
                        setFlash('success', 'Watch added to cart successfully.');
                    }
                }
            } else {
                setFlash('error', 'Product not found.');
            }
        } elseif ($action === 'remove') {
            if ($userId) {
                $del = $pdo->prepare("DELETE FROM cart WHERE (session_id = ? OR user_id = ?) AND product_id = ?");
                $del->execute([$sessionId, $userId, $productId]);
            } else {
                $del = $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?");
                $del->execute([$sessionId, $productId]);
            }
            setFlash('success', 'Item removed from cart.');
        }
    }
    header("Location: cart.php");
    exit();
}

// Fetch Cart Items
if ($userId) {
    $cartStmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.brand, p.price, p.stock_quantity, p.image 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.session_id = ? OR c.user_id = ?
        ORDER BY c.id DESC
    ");
    $cartStmt->execute([$sessionId, $userId]);
} else {
    $cartStmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.brand, p.price, p.stock_quantity, p.image 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.session_id = ?
        ORDER BY c.id DESC
    ");
    $cartStmt->execute([$sessionId]);
}
$cartItems = $cartStmt->fetchAll();

$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}
$deliveryCharge = ($subtotal > 2000 || $subtotal == 0) ? 0 : 150;
$grandTotal = $subtotal + $deliveryCharge;

// Now load HTML layout
require_once __DIR__ . '/includes/header.php';
?>

<div class="detail-container">
    <div class="section-header" style="text-align: left; margin-bottom: 30px;">
        <span class="section-subtitle">Your Selection</span>
        <h1 class="section-title"><i class="fas fa-shopping-bag"></i> Shopping Cart</h1>
    </div>

    <?php if ($msg = getFlash('error')): ?>
        <div class="badge badge-danger" style="display:block; padding:12px; margin-bottom:20px; font-size:0.9rem;">
            <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($msg = getFlash('success')): ?>
        <div class="badge badge-success" style="display:block; padding:12px; margin-bottom:20px; font-size:0.9rem;">
            <i class="fas fa-check-circle"></i> <?php echo sanitize($msg); ?>
        </div>
    <?php endif; ?>

    <?php if (count($cartItems) > 0): ?>
        <div class="catalog-container" style="grid-template-columns: 1fr 340px; padding: 0;">
            <!-- Cart Items Table -->
            <div>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Watch Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <?php $itemTotal = $item['price'] * $item['quantity']; ?>
                            <tr>
                                <td>
                                    <div class="cart-item-info">
                                        <img src="<?php echo getImageUrl($item['image']); ?>" alt="<?php echo sanitize($item['name']); ?>" class="cart-item-img">
                                        <div>
                                            <div style="font-size: 0.75rem; color: var(--primary-gold); text-transform: uppercase; font-weight:700;"><?php echo sanitize($item['brand']); ?></div>
                                            <a href="product_details.php?id=<?php echo $item['product_id']; ?>" style="font-weight: 600; color: var(--text-main);">
                                                <?php echo sanitize($item['name']); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight: 600;"><?php echo formatPrice($item['price']); ?></td>
                                <td>
                                    <form action="cart.php" method="POST" class="qty-control">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>" class="qty-btn">-</button>
                                        <span class="qty-val"><?php echo $item['quantity']; ?></span>
                                        <button type="submit" name="quantity" value="<?php echo min($item['stock_quantity'], $item['quantity'] + 1); ?>" class="qty-btn" <?php echo $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : ''; ?>+</button>
                                    </form>
                                </td>
                                <td style="font-weight: 700; color: var(--primary-gold);"><?php echo formatPrice($itemTotal); ?></td>
                                <td>
                                    <form action="cart.php" method="POST">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" class="btn-add-cart" style="background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.3); color: #ef4444;" title="Remove Item">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                    <a href="products.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                </div>
            </div>

            <!-- Order Summary Side Card -->
            <div>
                <div class="summary-card">
                    <h3 style="font-family: var(--font-heading); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Order Summary</h3>
                    
                    <div class="summary-row">
                        <span>Items Subtotal</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Pan-India Delivery</span>
                        <span>
                            <?php if ($deliveryCharge === 0): ?>
                                <span style="color: #10b981; font-weight: 600;">FREE</span>
                            <?php else: ?>
                                <?php echo formatPrice($deliveryCharge); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Grand Total</span>
                        <span style="color: var(--primary-gold);"><?php echo formatPrice($grandTotal); ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-gold" style="width: 100%; margin-top: 25px;">
                        Proceed to Checkout <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 80px 20px; background: var(--bg-card); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <i class="fas fa-shopping-bag" style="font-size: 3.5rem; color: var(--text-muted); margin-bottom: 15px;"></i>
            <h3 style="font-family: var(--font-heading); margin-bottom: 10px;">Your Cart is Empty</h3>
            <p style="color: var(--text-muted); margin-bottom: 25px;">Browse our luxury horology collection and add your favorite timepieces.</p>
            <a href="products.php" class="btn btn-gold"><i class="fas fa-compass"></i> Explore Watches</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
