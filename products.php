<?php
require_once __DIR__ . '/includes/header.php';
$search = trim($_GET['search'] ?? '');
$selectedBrand = $_GET['brand'] ?? '';
$selectedCategory = $_GET['category'] ?? '';
$selectedGender = $_GET['gender'] ?? '';
$selectedMovement = $_GET['movement'] ?? '';
$selectedStrap = $_GET['strap'] ?? '';
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$sort = $_GET['sort'] ?? 'default';

// Fetch Filter Options from Database
$brandsList = $pdo->query("SELECT DISTINCT brand FROM products ORDER BY brand ASC")->fetchAll(PDO::FETCH_COLUMN);
$categoriesList = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
$movementsList = $pdo->query("SELECT DISTINCT movement FROM products ORDER BY movement ASC")->fetchAll(PDO::FETCH_COLUMN);
$strapsList = $pdo->query("SELECT DISTINCT strap_material FROM products ORDER BY strap_material ASC")->fetchAll(PDO::FETCH_COLUMN);

// Build SQL Query Dynamically
$where = [];
$params = [];

if (!empty($search)) {
    $searchPattern = '%' . $search . '%';
    $where[] = "(LOWER(name) LIKE LOWER(?) OR LOWER(brand) LIKE LOWER(?) OR LOWER(category) LIKE LOWER(?) OR LOWER(gender) LIKE LOWER(?) OR LOWER(description) LIKE LOWER(?) OR LOWER(movement) LIKE LOWER(?) OR LOWER(strap_material) LIKE LOWER(?))";
    $params = array_merge($params, array_fill(0, 7, $searchPattern));
}

if (!empty($selectedBrand)) {
    $where[] = "brand = ?";
    $params[] = $selectedBrand;
}

if (!empty($selectedCategory)) {
    $where[] = "category = ?";
    $params[] = $selectedCategory;
}

if (!empty($selectedGender)) {
    $where[] = "gender = ?";
    $params[] = $selectedGender;
}

if (!empty($selectedMovement)) {
    $where[] = "movement = ?";
    $params[] = $selectedMovement;
}

if (!empty($selectedStrap)) {
    $where[] = "strap_material = ?";
    $params[] = $selectedStrap;
}

if ($minPrice !== null) {
    $where[] = "price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== null) {
    $where[] = "price <= ?";
    $params[] = $maxPrice;
}

$sql = "SELECT * FROM products";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Order By logic
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY id DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY name DESC";
        break;
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$wishlistIds = getUserWishlistProductIds($pdo);
?>

<div class="catalog-container">
    <aside class="filter-sidebar">
        <form action="products.php" method="GET" id="filterForm">
            <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?php echo sanitize($search); ?>">
            <?php endif; ?>

            <div class="filter-title">
                <span><i class="fas fa-filter"></i> Filters</span>
                <a href="products.php" class="filter-reset">Reset All</a>
            </div>
            <div class="filter-group">
                <h4 class="filter-group-title">Gender</h4>
                <select name="gender" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Genders</option>
                    <option value="Men" <?php echo $selectedGender === 'Men' ? 'selected' : ''; ?>>Men</option>
                    <option value="Women" <?php echo $selectedGender === 'Women' ? 'selected' : ''; ?>>Women</option>
                    <option value="Unisex" <?php echo $selectedGender === 'Unisex' ? 'selected' : ''; ?>>Unisex</option>
                </select>
            </div>
            <div class="filter-group">
                <h4 class="filter-group-title">Brand</h4>
                <select name="brand" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Brands</option>
                    <?php foreach ($brandsList as $b): ?>
                        <option value="<?php echo sanitize($b); ?>" <?php echo $selectedBrand === $b ? 'selected' : ''; ?>>
                            <?php echo sanitize($b); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <h4 class="filter-group-title">Category</h4>
                <select name="category" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categoriesList as $c): ?>
                        <option value="<?php echo sanitize($c); ?>" <?php echo $selectedCategory === $c ? 'selected' : ''; ?>>
                            <?php echo sanitize($c); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <h4 class="filter-group-title">Watch Movement</h4>
                <select name="movement" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Movements</option>
                    <?php foreach ($movementsList as $m): ?>
                        <option value="<?php echo sanitize($m); ?>" <?php echo $selectedMovement === $m ? 'selected' : ''; ?>>
                            <?php echo sanitize($m); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <h4 class="filter-group-title">Strap Material</h4>
                <select name="strap" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Materials</option>
                    <?php foreach ($strapsList as $s): ?>
                        <option value="<?php echo sanitize($s); ?>" <?php echo $selectedStrap === $s ? 'selected' : ''; ?>>
                            <?php echo sanitize($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Price Range Filter (in ₹) -->
            <div class="filter-group">
                <h4 class="filter-group-title">Price Range (₹)</h4>
                <div style="display: flex; gap: 8px;">
                    <input type="number" name="min_price" class="filter-select" placeholder="Min ₹" 
                           value="<?php echo $minPrice !== null ? $minPrice : ''; ?>">
                    <input type="number" name="max_price" class="filter-select" placeholder="Max ₹" 
                           value="<?php echo $maxPrice !== null ? $maxPrice : ''; ?>">
                </div>
                <button type="submit" class="btn btn-gold btn-sm" style="width: 100%; margin-top: 10px;">Apply Price</button>
            </div>
        </form>
    </aside>

    <!-- Main Catalog Area -->
    <main>
        <!-- Top Controls Bar -->
        <div class="catalog-header-bar">
            <div class="results-count">
                Showing <strong><?php echo count($products); ?></strong> watches
                <?php if (!empty($search)): ?>
                    for "<strong><?php echo sanitize($search); ?></strong>"
                <?php endif; ?>
            </div>

            <!-- Sorting Select -->
            <div class="sort-select-wrap">
                <label for="sortSelect" style="font-size: 0.88rem; color: var(--text-muted);">Sort By:</label>
                <select id="sortSelect" class="filter-select" style="width: 180px;" onchange="updateSort(this.value)">
                    <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Default</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                    <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                    <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                </select>
            </div>
        </div>

        <!-- Product Grid -->
        <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): 
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
                            <span class="card-brand"><?php echo sanitize($product['brand']); ?> &bull; <?php echo sanitize($product['gender']); ?></span>
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="card-title">
                                <?php echo sanitize($product['name']); ?>
                            </a>
                            <div class="card-specs">
                                <span class="card-spec-item"><i class="fas fa-cogs"></i> <?php echo sanitize($product['movement']); ?></span>
                                <span class="card-spec-item"><i class="fas fa-ring"></i> <?php echo sanitize($product['strap_material']); ?></span>
                            </div>
                            <div class="card-footer">
                                <div class="price-box">
                                    <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                                    <?php if (!empty($product['discount_price'])): ?>
                                        <span class="original-price"><?php echo formatPrice($product['discount_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <button class="btn-add-cart btn-add-cart-ajax" data-product-id="<?php echo $product['id']; ?>" title="Add to Cart">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <i class="fas fa-search" style="font-size: 3rem; color: var(--primary-gold); margin-bottom: 15px;"></i>
                <h3 style="font-family: var(--font-heading); margin-bottom: 10px;">No Watches Found</h3>
                <p style="color: var(--text-muted); margin-bottom: 20px;">We couldn't find any watches matching your exact filter criteria or search query.</p>
                <a href="products.php" class="btn btn-gold">Clear Filters & View All Watches</a>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
function updateSort(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', val);
    window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
