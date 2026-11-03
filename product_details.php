<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header("Location: products.php");
    exit();
}

// Fetch Product Details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="detail-container" style="text-align:center; padding: 100px 20px;">
            <h2>Watch Not Found</h2>
            <p style="color:var(--text-muted); margin: 15px 0 25px;">The watch you requested is unavailable or has been removed.</p>
            <a href="products.php" class="btn btn-gold">Back to Catalog</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// Fetch Related Products from same brand or category
$relStmt = $pdo->prepare("SELECT * FROM products WHERE (category = ? OR brand = ?) AND id != ? LIMIT 3");
$relStmt->execute([$product['category'], $product['brand'], $product['id']]);
$relatedProducts = $relStmt->fetchAll();

$isWishlisted = isInWishlist($pdo, $product['id']);
$wishlistIds = getUserWishlistProductIds($pdo);

require_once __DIR__ . '/includes/header.php';
?>

<div class="detail-container">
    <!-- Main Product Section -->
    <div class="detail-grid">
        <!-- Gallery -->
        <div class="detail-gallery">
            <img src="<?php echo getImageUrl($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>" class="detail-main-img">
        </div>

        <!-- Specifications & Purchase -->
        <div>
            <span class="detail-brand"><?php echo sanitize($product['brand']); ?> &bull; <?php echo sanitize($product['gender']); ?></span>
            <h1 class="detail-title"><?php echo sanitize($product['name']); ?></h1>

            <div style="margin-bottom: 15px;">
                <?php echo getStockBadge($product['stock_quantity']); ?>
                <span class="badge badge-gold" style="margin-left: 8px;"><?php echo sanitize($product['category']); ?></span>
            </div>

            <div class="detail-price-wrap">
                <span class="detail-price"><?php echo formatPrice($product['price']); ?></span>
                <?php if (!empty($product['discount_price'])): ?>
                    <span class="original-price" style="font-size: 1.2rem;"><?php echo formatPrice($product['discount_price']); ?></span>
                    <span class="badge badge-success">Save <?php echo formatPrice($product['price'] - $product['discount_price']); ?></span>
                <?php endif; ?>
            </div>

            <p class="detail-desc"><?php echo nl2br(sanitize($product['description'])); ?></p>

            <!-- Technical Specification Table -->
            <table class="spec-table">
                <tr>
                    <td>Watch Movement</td>
                    <td><?php echo sanitize($product['movement']); ?></td>
                </tr>
                <tr>
                    <td>Case Material</td>
                    <td><?php echo sanitize($product['case_material']); ?></td>
                </tr>
                <tr>
                    <td>Strap Material</td>
                    <td><?php echo sanitize($product['strap_material']); ?></td>
                </tr>
                <tr>
                    <td>Dial Color</td>
                    <td><?php echo sanitize($product['dial_color']); ?></td>
                </tr>
                <tr>
                    <td>Case Color</td>
                    <td><?php echo sanitize($product['case_color']); ?></td>
                </tr>
                <tr>
                    <td>Water Resistance</td>
                    <td><?php echo sanitize($product['water_resistance']); ?></td>
                </tr>
                <tr>
                    <td>Warranty</td>
                    <td><?php echo sanitize($product['warranty']); ?></td>
                </tr>
            </table>

            <!-- Add to Cart & Buy Now Actions -->
            <div class="detail-actions-wrapper" style="margin-top: 25px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <?php if ($product['stock_quantity'] > 0): ?>
                    <form id="detailAddToCartForm" action="cart.php" method="POST" style="display:flex; gap:12px; align-items:center; flex-wrap: wrap; flex: 2; min-width: 280px; margin: 0;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="qty-control" style="height: 48px;">
                            <button type="button" class="qty-btn" onclick="let q=document.getElementById('detailQty'); if(q.value>1) q.value--;">-</button>
                            <input type="number" id="detailQty" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="qty-val" readonly style="background:transparent; border:none; color:var(--text-main);">
                            <button type="button" class="qty-btn" onclick="let q=document.getElementById('detailQty'); if(q.value < <?php echo $product['stock_quantity']; ?>) q.value++;">+</button>
                        </div>
                        <button type="submit" id="btnDetailAddToCart" class="btn btn-gold" style="flex: 1; height: 48px; min-width: 160px;"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
                    </form>
                    <button type="button" 
                            id="btnDetailWishlist"
                            class="btn btn-outline btn-detail-wishlist btn-wishlist-toggle <?php echo $isWishlisted ? 'active' : ''; ?>" 
                            data-product-id="<?php echo $product['id']; ?>"
                            title="<?php echo $isWishlisted ? 'In Wishlist' : 'Add to Wishlist'; ?>"
                            aria-label="<?php echo $isWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>"
                            style="height: 48px; border-radius: 30px; padding: 0 24px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; position: relative;">
                        <i class="<?php echo $isWishlisted ? 'fas fa-heart text-danger' : 'far fa-heart'; ?>"></i>
                        <span class="wishlist-btn-text"><?php echo $isWishlisted ? 'In Wishlist' : 'Add to Wishlist'; ?></span>
                    </button>
                <?php else: ?>
                    <div style="background: rgba(239,68,68,0.1); border: 1px solid var(--accent-red); padding: 15px; border-radius: var(--radius-sm); color: #f87171; text-align: center; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-circle"></i> This watch is currently Out of Stock. Register interest with customer support for restock updates.
                    </div>
                    <div>
                        <button type="button" 
                                class="btn btn-outline btn-detail-wishlist btn-wishlist-toggle <?php echo $isWishlisted ? 'active' : ''; ?>" 
                                data-product-id="<?php echo $product['id']; ?>"
                                title="<?php echo $isWishlisted ? 'Saved to Your Wishlist' : 'Save to Wishlist for Restock'; ?>"
                                aria-label="<?php echo $isWishlisted ? 'Remove from Wishlist' : 'Save to Wishlist for Restock'; ?>"
                                style="height: 48px; border-radius: 30px; padding: 0 24px; display: inline-flex; align-items: center; gap: 8px; width: 100%; justify-content: center; cursor: pointer; position: relative;">
                            <i class="<?php echo $isWishlisted ? 'fas fa-heart text-danger' : 'far fa-heart'; ?>"></i>
                            <span class="wishlist-btn-text"><?php echo $isWishlisted ? 'Saved to Your Wishlist' : 'Save to Wishlist for Restock'; ?></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (count($relatedProducts) > 0): ?>
        <div class="section-header" style="text-align: left; margin-top: 60px;">
            <span class="section-subtitle">Recommended Horology</span>
            <h2 class="section-title">Related Watches You May Like</h2>
        </div>
        <div class="products-grid">
            <?php foreach ($relatedProducts as $rel): 
                $isRelWishlisted = in_array((int)$rel['id'], $wishlistIds, true);
            ?>
                <div class="product-card">
                    <div class="card-badge"><?php echo getStockBadge($rel['stock_quantity']); ?></div>
                    <button type="button" 
                            class="btn-wishlist-toggle <?php echo $isRelWishlisted ? 'active' : ''; ?>" 
                            data-product-id="<?php echo $rel['id']; ?>" 
                            title="<?php echo $isRelWishlisted ? 'In Wishlist' : 'Add to Wishlist'; ?>" 
                            aria-label="<?php echo $isRelWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                        <i class="<?php echo $isRelWishlisted ? 'fas fa-heart text-danger' : 'far fa-heart'; ?>"></i>
                    </button>
                    <div class="card-img-wrap">
                        <a href="product_details.php?id=<?php echo $rel['id']; ?>">
                            <img src="<?php echo getImageUrl($rel['image']); ?>" alt="<?php echo sanitize($rel['name']); ?>" class="card-img">
                        </a>
                    </div>
                    <div class="card-body">
                        <span class="card-brand"><?php echo sanitize($rel['brand']); ?></span>
                        <a href="product_details.php?id=<?php echo $rel['id']; ?>" class="card-title"><?php echo sanitize($rel['name']); ?></a>
                        <div class="card-footer">
                            <span class="current-price"><?php echo formatPrice($rel['price']); ?></span>
                            <a href="product_details.php?id=<?php echo $rel['id']; ?>" class="btn btn-outline btn-sm">View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
