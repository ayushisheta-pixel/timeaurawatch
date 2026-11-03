<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$sessionId = session_id();
$userId = $_SESSION['user_id'] ?? null;
ensureWishlistTable($pdo);

// Handle non-AJAX POST actions (Progressive Enhancement)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'clear') {
        if ($userId) {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? OR session_id = ?");
            $stmt->execute([$userId, $sessionId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE session_id = ?");
            $stmt->execute([$sessionId]);
        }
        setFlash('success', 'Your wishlist has been cleared.');
        header("Location: wishlist.php");
        exit();
    } elseif ($action === 'remove' && $productId > 0) {
        if ($userId) {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE (user_id = ? OR session_id = ?) AND product_id = ?");
            $stmt->execute([$userId, $sessionId, $productId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE session_id = ? AND product_id = ?");
            $stmt->execute([$sessionId, $productId]);
        }
        setFlash('success', 'Item removed from your wishlist.');
        header("Location: wishlist.php");
        exit();
    } elseif ($action === 'move_to_cart' && $productId > 0) {
        // Check product stock
        $st = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ?");
        $st->execute([$productId]);
        $prod = $st->fetch();

        if ($prod && $prod['stock_quantity'] > 0) {
            // Add to cart
            $cartCheck = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
            $cartCheck->execute([$sessionId, $productId]);
            $existingCart = $cartCheck->fetch();

            if ($existingCart) {
                if ($existingCart['quantity'] < $prod['stock_quantity']) {
                    $cartUp = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
                    $cartUp->execute([$existingCart['id']]);
                }
            } else {
                $cartIns = $pdo->prepare("INSERT INTO cart (session_id, user_id, product_id, quantity) VALUES (?, ?, ?, 1)");
                $cartIns->execute([$sessionId, $userId, $productId]);
            }

            // Remove from wishlist
            if ($userId) {
                $del = $pdo->prepare("DELETE FROM wishlist WHERE (user_id = ? OR session_id = ?) AND product_id = ?");
                $del->execute([$userId, $sessionId, $productId]);
            } else {
                $del = $pdo->prepare("DELETE FROM wishlist WHERE session_id = ? AND product_id = ?");
                $del->execute([$sessionId, $productId]);
            }
            setFlash('success', 'Moved "' . htmlspecialchars($prod['name']) . '" to your cart.');
        } else {
            setFlash('error', 'Sorry, this watch is currently out of stock.');
        }
        header("Location: wishlist.php");
        exit();
    } elseif ($action === 'add_all_to_cart') {
        // Fetch all in-stock wishlisted items
        if ($userId) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.id, p.name, p.stock_quantity 
                FROM wishlist w 
                JOIN products p ON w.product_id = p.id 
                WHERE (w.user_id = ? OR w.session_id = ?) AND p.stock_quantity > 0
            ");
            $stmt->execute([$userId, $sessionId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.id, p.name, p.stock_quantity 
                FROM wishlist w 
                JOIN products p ON w.product_id = p.id 
                WHERE w.session_id = ? AND p.stock_quantity > 0
            ");
            $stmt->execute([$sessionId]);
        }
        $inStockItems = $stmt->fetchAll();
        $movedCount = 0;

        foreach ($inStockItems as $item) {
            $cartCheck = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
            $cartCheck->execute([$sessionId, $item['id']]);
            $existingCart = $cartCheck->fetch();

            if ($existingCart) {
                if ($existingCart['quantity'] < $item['stock_quantity']) {
                    $cartUp = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
                    $cartUp->execute([$existingCart['id']]);
                    $movedCount++;
                }
            } else {
                $cartIns = $pdo->prepare("INSERT INTO cart (session_id, user_id, product_id, quantity) VALUES (?, ?, ?, 1)");
                $cartIns->execute([$sessionId, $userId, $item['id']]);
                $movedCount++;
            }

            // Remove moved item from wishlist
            if ($userId) {
                $del = $pdo->prepare("DELETE FROM wishlist WHERE (user_id = ? OR session_id = ?) AND product_id = ?");
                $del->execute([$userId, $sessionId, $item['id']]);
            } else {
                $del = $pdo->prepare("DELETE FROM wishlist WHERE session_id = ? AND product_id = ?");
                $del->execute([$sessionId, $item['id']]);
            }
        }

        if ($movedCount > 0) {
            setFlash('success', "Moved $movedCount available watches to your shopping cart!");
        } else {
            setFlash('error', "No available in-stock items could be moved.");
        }
        header("Location: wishlist.php");
        exit();
    }
}

// Fetch Wishlist Items
if ($userId) {
    $stmt = $pdo->prepare("
        SELECT p.id as product_id, p.name, p.brand, p.category, p.gender, p.price, p.discount_price, 
               p.stock_quantity, p.movement, p.strap_material, p.image, MAX(w.id) as wishlist_id
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.user_id = ? OR w.session_id = ?
        GROUP BY p.id
        ORDER BY wishlist_id DESC
    ");
    $stmt->execute([$userId, $sessionId]);
} else {
    $stmt = $pdo->prepare("
        SELECT p.id as product_id, p.name, p.brand, p.category, p.gender, p.price, p.discount_price, 
               p.stock_quantity, p.movement, p.strap_material, p.image, MAX(w.id) as wishlist_id
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.session_id = ?
        GROUP BY p.id
        ORDER BY wishlist_id DESC
    ");
    $stmt->execute([$sessionId]);
}
$wishlistItems = $stmt->fetchAll();

// If empty, fetch top 3 recommendations
$recommendations = [];
if (empty($wishlistItems)) {
    $recStmt = $pdo->query("SELECT * FROM products ORDER BY stock_quantity DESC, id DESC LIMIT 3");
    $recommendations = $recStmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="detail-container">
    <!-- Breadcrumb & Header -->
    <div class="section-header" style="text-align: left; margin-bottom: 25px;">
        <span class="section-subtitle"><i class="fas fa-gem"></i> Curated Selections</span>
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: gap: 20px;">
            <div>
                <h1 class="section-title" style="margin-bottom: 6px;">
                    <i class="fas fa-heart text-gold" style="color: var(--primary-gold); margin-right: 8px;"></i>
                    My Wishlist
                    <span id="wishlistPageCountBadge" class="badge badge-gold" style="font-size: 1rem; vertical-align: middle; margin-left: 8px;">
                        <?php echo count($wishlistItems); ?> <?php echo count($wishlistItems) === 1 ? 'Watch' : 'Watches'; ?>
                    </span>
                </h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Save your favorite luxury horology pieces. Transfer them directly to your bag whenever you are ready.
                </p>
            </div>

            <?php if (!empty($wishlistItems)): ?>
                <div class="wishlist-toolbar-actions" style="display: flex; gap: 12px; margin-top: 15px;">
                    <form action="wishlist.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="add_all_to_cart">
                        <button type="submit" class="btn btn-gold btn-sm" title="Move all in-stock watches to cart">
                            <i class="fas fa-shopping-bag"></i> Move All to Cart
                        </button>
                    </form>
                    <button type="button" id="btnClearWishlist" class="btn btn-outline btn-sm" style="color: var(--text-muted); border-color: var(--border-color);" title="Clear entire wishlist">
                        <i class="fas fa-trash-alt"></i> Clear Wishlist
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($msg = getFlash('error')): ?>
        <div class="badge badge-danger" style="display:block; padding:12px; margin-bottom:20px; font-size:0.9rem; text-align: left;">
            <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($msg = getFlash('success')): ?>
        <div class="badge badge-success" style="display:block; padding:12px; margin-bottom:20px; font-size:0.9rem; text-align: left;">
            <i class="fas fa-check-circle"></i> <?php echo sanitize($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Wishlist Items Grid or Empty State -->
    <div id="wishlistContentArea">
        <?php if (!empty($wishlistItems)): ?>
            <div class="products-grid wishlist-grid" id="wishlistGrid">
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="product-card wishlist-card" id="wishlistItem-<?php echo $item['product_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>">
                        
                        <!-- Badges & Action Bar -->
                        <div class="card-badge">
                            <?php echo getStockBadge($item['stock_quantity']); ?>
                        </div>

                        <!-- Remove from Wishlist Quick Button -->
                        <button type="button" 
                                class="btn-wishlist-remove btn-wishlist-remove-ajax" 
                                data-product-id="<?php echo $item['product_id']; ?>" 
                                title="Remove from wishlist" 
                                aria-label="Remove watch from wishlist">
                            <i class="fas fa-times"></i>
                        </button>

                        <!-- Product Image -->
                        <div class="card-img-wrap">
                            <a href="product_details.php?id=<?php echo $item['product_id']; ?>">
                                <img src="<?php echo getImageUrl($item['image']); ?>" alt="<?php echo sanitize($item['name']); ?>" class="card-img">
                            </a>
                        </div>

                        <!-- Product Information -->
                        <div class="card-body">
                            <span class="card-brand"><?php echo sanitize($item['brand']); ?> &bull; <?php echo sanitize($item['gender']); ?></span>
                            <a href="product_details.php?id=<?php echo $item['product_id']; ?>" class="card-title">
                                <?php echo sanitize($item['name']); ?>
                            </a>

                            <div class="card-specs">
                                <span class="card-spec-item"><i class="fas fa-cogs"></i> <?php echo sanitize($item['movement']); ?></span>
                                <span class="card-spec-item"><i class="fas fa-ring"></i> <?php echo sanitize($item['strap_material']); ?></span>
                            </div>

                            <div class="card-footer" style="flex-direction: column; align-items: stretch; gap: 12px; padding-top: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div class="price-box">
                                        <span class="current-price"><?php echo formatPrice($item['price']); ?></span>
                                        <?php if (!empty($item['discount_price'])): ?>
                                            <span class="original-price"><?php echo formatPrice($item['discount_price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($item['discount_price'])): ?>
                                        <span class="badge badge-success" style="font-size: 0.75rem;">
                                            Save <?php echo formatPrice($item['price'] - $item['discount_price']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div style="display: flex; gap: 10px;">
                                    <?php if ($item['stock_quantity'] > 0): ?>
                                        <button type="button" 
                                                class="btn btn-gold btn-sm btn-move-cart-ajax" 
                                                data-product-id="<?php echo $item['product_id']; ?>" 
                                                style="flex: 1; padding: 10px 14px;">
                                            <i class="fas fa-shopping-bag"></i> Move to Cart
                                        </button>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-outline btn-sm" 
                                                disabled 
                                                style="flex: 1; opacity: 0.6; cursor: not-allowed; border-color: var(--accent-red); color: var(--accent-red);">
                                            <i class="fas fa-times-circle"></i> Out of Stock
                                        </button>
                                    <?php endif; ?>

                                    <form action="wishlist.php" method="POST" style="margin: 0; display: inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" 
                                                class="btn btn-outline btn-sm" 
                                                title="Remove" 
                                                style="padding: 10px 14px; border-color: var(--border-color); color: var(--text-muted);">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty Wishlist State -->
            <div class="wishlist-empty-state" style="text-align: center; padding: 80px 20px; background: var(--bg-card); border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 50px;">
                <div class="empty-icon-wrap" style="width: 90px; height: 90px; border-radius: 50%; background: rgba(212, 175, 55, 0.1); border: 2px dashed var(--primary-gold); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                    <i class="far fa-heart" style="font-size: 2.8rem; color: var(--primary-gold);"></i>
                </div>
                <h2 style="font-family: var(--font-heading); font-size: 2rem; margin-bottom: 12px;">Your Wishlist is Currently Empty</h2>
                <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 30px; font-size: 1rem; line-height: 1.6;">
                    You haven't saved any luxury timepieces to your wishlist yet. Tap the heart icon on any watch in our catalog to save it for future consideration.
                </p>
                <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                    <a href="products.php" class="btn btn-gold">
                        <i class="fas fa-compass"></i> Explore Watch Catalog
                    </a>
                    <a href="products.php?category=Luxury+Watches" class="btn btn-outline">
                        <i class="fas fa-crown"></i> View Luxury Collection
                    </a>
                </div>
            </div>

            <!-- Curated Recommendations -->
            <?php if (!empty($recommendations)): ?>
                <div class="section-header" style="text-align: left; margin-top: 40px; margin-bottom: 25px;">
                    <span class="section-subtitle">Recommended For You</span>
                    <h3 class="section-title" style="font-size: 1.6rem;">Popular Timepieces You May Like</h3>
                </div>
                <div class="products-grid">
                    <?php foreach ($recommendations as $rec): ?>
                        <div class="product-card">
                            <div class="card-badge"><?php echo getStockBadge($rec['stock_quantity']); ?></div>
                            <button type="button" 
                                    class="btn-wishlist-toggle" 
                                    data-product-id="<?php echo $rec['id']; ?>" 
                                    title="Add to Wishlist" 
                                    aria-label="Add to Wishlist">
                                <i class="far fa-heart"></i>
                            </button>
                            <div class="card-img-wrap">
                                <a href="product_details.php?id=<?php echo $rec['id']; ?>">
                                    <img src="<?php echo getImageUrl($rec['image']); ?>" alt="<?php echo sanitize($rec['name']); ?>" class="card-img">
                                </a>
                            </div>
                            <div class="card-body">
                                <span class="card-brand"><?php echo sanitize($rec['brand']); ?></span>
                                <a href="product_details.php?id=<?php echo $rec['id']; ?>" class="card-title">
                                    <?php echo sanitize($rec['name']); ?>
                                </a>
                                <div class="card-footer">
                                    <div class="price-box">
                                        <span class="current-price"><?php echo formatPrice($rec['price']); ?></span>
                                    </div>
                                    <a href="product_details.php?id=<?php echo $rec['id']; ?>" class="btn btn-outline btn-sm">View Watch</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
