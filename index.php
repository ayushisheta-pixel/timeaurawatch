<?php
require_once __DIR__ . '/includes/header.php';

// Fetch Featured Watches (Products with discount or high-end)
$featuredStmt = $pdo->query("SELECT * FROM products ORDER BY price DESC LIMIT 3");
$featuredProducts = $featuredStmt->fetchAll();

// Fetch Men's Watches
$menStmt = $pdo->prepare("SELECT * FROM products WHERE gender = 'Men' OR category = 'Men' LIMIT 3");
$menStmt->execute();
$menProducts = $menStmt->fetchAll();

// Fetch Women's Watches
$womenStmt = $pdo->prepare("SELECT * FROM products WHERE gender = 'Women' OR category = 'Women' LIMIT 3");
$womenStmt->execute();
$womenProducts = $womenStmt->fetchAll();

// Fetch Smart Watches
$smartStmt = $pdo->prepare("SELECT * FROM products WHERE category = 'Smart Watches' OR movement LIKE '%Digital%' LIMIT 3");
$smartStmt->execute();
$smartProducts = $smartStmt->fetchAll();

$wishlistIds = getUserWishlistProductIds($pdo);
?>

<!-- Hero Banner Section -->
<section class="hero-section">
    <div class="hero-container">
        <div>
            <span class="hero-sub"><i class="fas fa-crown"></i> Exquisite Horology Since 2026</span>
            <h1 class="hero-title">Timeless Luxury, <span>Crafted</span> For Distinction.</h1>
            <p class="hero-desc">
                Discover TIMEAURA’s curated collection of Swiss automatics, gold chronographs, and futuristic smartwatches. Precision engineering delivered to your doorstep in India.
            </p>
            <div class="hero-btns">
                <a href="products.php" class="btn btn-gold"><i class="fas fa-compass"></i> Explore Collection</a>
                <a href="products.php?category=Luxury+Watches" class="btn btn-outline"><i class="fas fa-gem"></i> Luxury Edition</a>
            </div>
        </div>
        <div class="hero-image-wrap">
            <img src="uploads/products/watch1.webp" alt="TIMEAURA Masterpiece Watch" class="hero-img">
        </div>
    </div>
</section>

<!-- Brand Showcase Strip -->
<section class="section-padding" style="background: #080c14; padding: 40px 20px; border-bottom: 1px solid var(--border-color);">
    <div class="brands-strip">
        <a href="products.php?brand=TIMEAURA" class="brand-pill">TIMEAURA</a>
        <a href="products.php?brand=Titan" class="brand-pill">TITAN</a>
        <a href="products.php?brand=Casio" class="brand-pill">CASIO</a>
        <a href="products.php?brand=Fastrack" class="brand-pill">FASTRACK</a>
        <a href="products.php?brand=Timex" class="brand-pill">TIMEX</a>
        <a href="products.php?brand=Fossil" class="brand-pill">FOSSIL</a>
        <a href="products.php?brand=Omega" class="brand-pill">OMEGA</a>
    </div>
</section>

<!-- Product Categories Grid -->
<section class="section-padding">
    <div class="section-header">
        <span class="section-subtitle">Curated Categories</span>
        <h2 class="section-title">Shop By Watch Style</h2>
    </div>
    <div class="category-grid">
        <a href="products.php?gender=Men" class="category-card">
            <i class="fas fa-user-tie category-icon"></i>
            <h3 class="category-name">Men's Collection</h3>
        </a>
        <a href="products.php?gender=Women" class="category-card">
            <i class="fas fa-female category-icon"></i>
            <h3 class="category-name">Women's Collection</h3>
        </a>
        <a href="products.php?category=Smart+Watches" class="category-card">
            <i class="fas fa-microchip category-icon"></i>
            <h3 class="category-name">Smart Watches</h3>
        </a>
        <a href="products.php?category=Luxury+Watches" class="category-card">
            <i class="fas fa-gem category-icon"></i>
            <h3 class="category-name">Luxury Collection</h3>
        </a>
        <a href="products.php?category=Chronograph" class="category-card">
            <i class="fas fa-stopwatch category-icon"></i>
            <h3 class="category-name">Chronographs</h3>
        </a>
        <a href="products.php?category=New+Arrivals" class="category-card">
            <i class="fas fa-sparkles category-icon"></i>
            <h3 class="category-name">New Arrivals</h3>
        </a>
    </div>
</section>

<!-- Featured Flagship Watches -->
<section class="section-padding" style="background: var(--bg-surface);">
    <div class="section-header">
        <span class="section-subtitle">Exclusive Horology</span>
        <h2 class="section-title">Featured Luxury Watches</h2>
    </div>
    <div class="products-grid">
        <?php foreach ($featuredProducts as $product): 
            $isWishlisted = in_array((int)$product['id'], $wishlistIds, true);
        ?>
            <div class="product-card">
                <div class="card-badge">
                    <?php echo getStockBadge($product['stock_quantity']); ?>
                </div>
                <button type="button" 
                        class="btn-wishlist-toggle <?php echo $isWishlisted ? 'active' : ''; ?>" 
                        data-product-id="<?php echo $product['id']; ?>" 
                        title="<?php echo $isWishlisted ? 'In Wishlist' : 'Add to Wishlist'; ?>" 
                        aria-label="<?php echo $isWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                    <i class="<?php echo $isWishlisted ? 'fas fa-heart text-danger' : 'far fa-heart'; ?>"></i>
                </button>
                <div class="card-img-wrap">
                    <img src="<?php echo getImageUrl($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>" class="card-img">
                </div>
                <div class="card-body">
                    <span class="card-brand"><?php echo sanitize($product['brand']); ?></span>
                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="card-title">
                        <?php echo sanitize($product['name']); ?>
                    </a>
                    <div class="card-specs">
                        <span class="card-spec-item"><i class="fas fa-cogs"></i> <?php echo sanitize($product['movement']); ?></span>
                        <span class="card-spec-item"><i class="fas fa-tint"></i> <?php echo sanitize($product['water_resistance']); ?></span>
                    </div>
                    <div class="card-footer">
                        <div class="price-box">
                            <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                            <?php if ($product['discount_price']): ?>
                                <span class="original-price"><?php echo formatPrice($product['discount_price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="btn-add-cart btn-add-cart-ajax" data-product-id="<?php echo $product['id']; ?>" title="Add to Cart">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Men's Showcase Section -->
<section class="section-padding">
    <div class="section-header">
        <span class="section-subtitle">For Him</span>
        <h2 class="section-title">Men's Timepiece Highlights</h2>
    </div>
    <div class="products-grid">
        <?php foreach ($menProducts as $product): 
            $isWishlisted = in_array((int)$product['id'], $wishlistIds, true);
        ?>
            <div class="product-card">
                <div class="card-badge"><?php echo getStockBadge($product['stock_quantity']); ?></div>
                <button type="button" 
                        class="btn-wishlist-toggle <?php echo $isWishlisted ? 'active' : ''; ?>" 
                        data-product-id="<?php echo $product['id']; ?>" 
                        title="<?php echo $isWishlisted ? 'In Wishlist' : 'Add to Wishlist'; ?>" 
                        aria-label="<?php echo $isWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                    <i class="<?php echo $isWishlisted ? 'fas fa-heart text-danger' : 'far fa-heart'; ?>"></i>
                </button>
                <div class="card-img-wrap">
                    <img src="<?php echo getImageUrl($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>" class="card-img">
                </div>
                <div class="card-body">
                    <span class="card-brand"><?php echo sanitize($product['brand']); ?></span>
                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="card-title">
                        <?php echo sanitize($product['name']); ?>
                    </a>
                    <div class="card-footer">
                        <div class="price-box">
                            <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                        </div>
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline btn-sm">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Why Choose TIMEAURA -->
<section class="section-padding" style="background: #080c14; border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <span class="section-subtitle">Our Promise</span>
        <h2 class="section-title">Why Choose TIMEAURA</h2>
    </div>
    <div class="category-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
        <div class="category-card">
            <i class="fas fa-shield-check category-icon"></i>
            <h3 class="category-name">100% Authentic Guarantee</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 8px;">Direct manufacturer warranty & certified luxury horology provenance.</p>
        </div>
        <div class="category-card">
            <i class="fas fa-truck-fast category-icon"></i>
            <h3 class="category-name">Express Pan-India Shipping</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 8px;">Insured delivery across 20,000+ pincodes in India within 2-4 days.</p>
        </div>
        <div class="category-card">
            <i class="fas fa-headset category-icon"></i>
            <h3 class="category-name">Dedicated Concierge Support</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 8px;">Support available via phone (+91 98765 43210) & email support@timeaura.in.</p>
        </div>
        <div class="category-card">
            <i class="fas fa-rotate-left category-icon"></i>
            <h3 class="category-name">Hassle-Free Returns</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 8px;">7-day return policy for unopened and authentic watch items.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
