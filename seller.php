<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';


$id = $_GET['id'] ?? 's1';
$s  = find_seller($id, $SELLERS);
if (!$s) {
    $raw_id = (int)substr($id, 1);
    if ($raw_id > 0) {
        $sr = mysqli_query($conn, "SELECT s.*, (SELECT COUNT(*) FROM products p WHERE p.shop_id=s.id AND p.status='live') AS products_count FROM shops s WHERE s.id=$raw_id");
        $row = $sr ? mysqli_fetch_assoc($sr) : null;
        if ($row) $s = _normalize_shop($row);
    }
}
if (!$s) {
  header('Location: /limedime/sellers.php');
  exit;
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
$allowed_tabs = ['products', 'about', 'reviews'];
if (!in_array($tab, $allowed_tabs)) $tab = 'products';

$seller_products = [];
foreach ($PRODUCTS as $p) {
    if ($p['seller_id'] === $s['id']) $seller_products[] = $p;
}
$cat = find_category($s['cat'], $CATEGORIES);


$page_title  = $s['name'] . ' — Lime&Dime';
$active_route = 'seller';
$show_catbar  = false;
include '_partials/header.php';
?>

<div class="container page">
  <!-- Breadcrumb -->
  <div class="eyebrow" style="margin-bottom:16px;">
    <a href="/limedime/index.php" style="color:var(--ink-3);">Home</a> ·
    <a href="/limedime/sellers.php" style="color:var(--ink-3);">Sellers</a> ·
    <?= $s['name'] ?>
  </div>

  <!-- Shop header -->
  <div style="border-radius:var(--r-lg);overflow:hidden;margin-bottom:28px;">
    <!-- Cover -->
    <div style="height:200px;overflow:hidden;border-radius:var(--r-lg) var(--r-lg) 0 0;background:#111;">
      <?php if (!empty($s['cover_image'])): ?>
        <img src="/limedime/assets/img/shops/<?= htmlspecialchars($s['cover_image']) ?>"
          alt="<?= htmlspecialchars($s['name']) ?>"
          style="width:100%;height:200px;object-fit:cover;display:block;">
      <?php else: ?>
        <?= seller_cover($s['accent'], $s['pattern'], '#E1DD8F') ?>
      <?php endif; ?>
    </div>
    <!-- Shop info bar -->
    <div style="padding:0 28px 24px;background:var(--surface);border:1px solid var(--line);border-top:none;border-radius:0 0 var(--r-lg) var(--r-lg);">
      <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:flex-end;gap:18px;">
          <div class="seller-avatar" style="width:80px;height:80px;font-size:28px;margin:-40px 0 0;border:4px solid var(--surface);background:<?= $s['accent'] ?>;color:var(--lime);flex-shrink:0;"><?= $s['avatar'] ?></div>
          <div style="padding-bottom:4px;">
            <h1 class="h1" style="margin:0;font-size:30px;"><?= $s['name'] ?></h1>
            <p class="muted" style="margin:4px 0 0;font-size:13.5px;"><?= $s['tag'] ?></p>
          </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
          <div style="text-align:center;padding:0 16px;">
            <div style="font-family:Fraunces,serif;font-size:22px;font-weight:700;color:var(--wine);"><?= $s['products'] ?></div>
            <div class="muted" style="font-size:12px;">Products</div>
          </div>
          <div style="text-align:center;padding:0 16px;border-left:1px dashed var(--line);">
            <div class="follow-count" style="font-family:Fraunces,serif;font-size:22px;font-weight:700;color:var(--wine);"><?= $s['followers'] ?></div>
            <div class="muted" style="font-size:12px;">Followers</div>
          </div>
          <div style="text-align:center;padding:0 16px;border-left:1px dashed var(--line);">
            <div style="display:inline-flex;align-items:center;gap:4px;font-family:Fraunces,serif;font-size:22px;font-weight:700;color:var(--wine);"><?= icon_star(16) ?><?= $s['rating'] ?></div>
            <div class="muted" style="font-size:12px;">Rating</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs" style="margin-bottom:28px;border-bottom:1px solid var(--line);padding-bottom:0;">
    <?php foreach (['products' => 'Products', 'about' => 'About', 'reviews' => 'Reviews'] as $t_id => $t_label): ?>
      <a href="/limedime/seller.php?id=<?= $s['id'] ?>&tab=<?= $t_id ?>"
        class="tab <?= $tab === $t_id ? 'active' : '' ?>"
        style="text-decoration:none;padding-bottom:14px;"><?= $t_label ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($tab === 'products'): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <p class="muted" style="margin:0;"><?= count($seller_products) ?> products · <?= $cat['label'] ?></p>
      <select onchange="window.location.href='/limedime/seller.php?id=<?= $s['id'] ?>&tab=products&sort='+this.value"
        style="border:1px solid var(--line);border-radius:999px;padding:7px 14px;background:var(--surface);font-weight:700;font-size:12.5px;color:var(--ink-2);">
        <option value="relevance">Sort: Relevance</option>
        <option value="price-asc">Price: low → high</option>
        <option value="price-desc">Price: high → low</option>
        <option value="rating">Highest rated</option>
      </select>
    </div>
    <?php if (count($seller_products) > 0): ?>
      <div class="grid grid-products">
        <?php foreach ($seller_products as $p): ?>
          <article class="product-card card" onclick="location.href='/limedime/product.php?id=<?= $p['id'] ?>';" style="cursor:pointer;">
            <div class="product-thumb">
              <?= product_thumb($p) ?>
              <?php if ($p['quantity'] <= 0): ?><span class="product-tag" style="background:var(--ink-3);">Sold out</span><?php elseif ($p['tag']): ?><span class="product-tag"><?= $p['tag'] ?></span><?php endif; ?>
              <button class="product-fav" data-fav="<?= $p['id'] ?>" onclick="event.stopPropagation();toggleFav('<?= $p['id'] ?>');"><?= icon_heart(16) ?></button>
            </div>
            <div class="product-body">
              <div class="product-seller">
                <span style="width:6px;height:6px;border-radius:999px;background:<?= $cat['color'] ?>;"></span>
                <span><?= $p['seller'] ?></span>
              </div>
              <div class="product-name"><?= $p['name'] ?></div>
              <div class="product-meta">
                <span class="product-price">$<?= number_format($p['price'], 2) ?></span>
                <span class="product-rating"><?= icon_star(12) ?> <?= $p['rating'] ?> <span style="color:var(--ink-4);font-weight:500;">(<?= $p['reviews'] ?>)</span></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <h3 class="h3" style="margin:0 0 6px;">No products listed yet</h3>
        <p class="muted" style="margin:0;">This seller is setting up shop — check back soon.</p>
      </div>
    <?php endif; ?>

  <?php elseif ($tab === 'about'): ?>
    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:32px;">
      <div>
        <h2 class="h2" style="margin:0 0 14px;">About <?= $s['name'] ?></h2>
        <p style="color:var(--ink-2);line-height:1.7;margin-bottom:18px;">
          <?= $s['name'] ?> is a small independent shop specialising in <?= $cat['label'] ?> products.
          Founded in 2022, we craft everything in small batches with care for quality and the people who use our products.
          Every item is made to order, shipped within 2 business days, and packaged in recycled materials.
        </p>
        <p style="color:var(--ink-2);line-height:1.7;margin-bottom:24px;">
          We believe small businesses are the backbone of local communities. When you shop with us,
          you're supporting a real person — not a warehouse algorithm.
        </p>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;align-items:center;gap:12px;font-size:14px;">
            <span style="width:36px;height:36px;border-radius:10px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;"><?= icon_package(16) ?></span>
            <span><b>Free shipping</b> <span class="muted">on orders over $40</span></span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;font-size:14px;">
            <span style="width:36px;height:36px;border-radius:10px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;"><?= icon_clock(16) ?></span>
            <span><b>2-day dispatch</b> <span class="muted">from our workshop</span></span>
          </div>
          <div style="display:flex;align-items:center;gap:12px;font-size:14px;">
            <span style="width:36px;height:36px;border-radius:10px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;"><?= icon_check(16) ?></span>
            <span><b>30-day returns</b> <span class="muted">no questions asked</span></span>
          </div>
        </div>
      </div>
      <div>
        <div class="card" style="padding:24px;">
          <h3 class="h3" style="margin-top:0;">Shop info</h3>
          <div style="display:flex;flex-direction:column;gap:12px;font-size:13.5px;">
            <div style="display:flex;justify-content:space-between;"><span class="muted">Category</span><b><?= $cat['label'] ?></b></div>
            <div class="divider" style="margin:0;"></div>
            <div style="display:flex;justify-content:space-between;"><span class="muted">Products listed</span><b><?= $s['products'] ?></b></div>
            <div class="divider" style="margin:0;"></div>
            <div style="display:flex;justify-content:space-between;"><span class="muted">Followers</span><b class="follow-count"><?= $s['followers'] ?></b></div>
            <div class="divider" style="margin:0;"></div>
            <div style="display:flex;justify-content:space-between;"><span class="muted">Avg. rating</span><b><?= $s['rating'] ?> ★</b></div>
            <div class="divider" style="margin:0;"></div>
            <div style="display:flex;justify-content:space-between;"><span class="muted">Member since</span><b>Jan 2022</b></div>
          </div>
        </div>
        <div style="margin-top:14px;padding:16px;background:var(--surface-2);border-radius:var(--r);border:1px dashed var(--line);text-align:center;">
          <div class="eyebrow">Platform verified</div>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:8px;flex-wrap:wrap;">
            <span class="pill success">ID verified</span>
            <span class="pill success">Business licensed</span>
            <span class="pill success">Top seller</span>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($tab === 'reviews'): ?>
    <div style="display:grid;grid-template-columns:1fr 2.4fr;gap:32px;">
      <!-- Rating summary -->
      <div>
        <div class="card" style="padding:24px;text-align:center;">
          <div style="font-family:Fraunces,serif;font-size:52px;font-weight:700;color:var(--wine);line-height:1;"><?= $s['rating'] ?></div>
          <div style="display:flex;justify-content:center;gap:3px;color:var(--coral);margin:8px 0;">
            <?php for ($i = 0; $i < 5; $i++) echo icon_star(18); ?>
          </div>
          <p class="muted" style="margin:4px 0 18px;font-size:13px;">Based on 412 reviews</p>
          <?php foreach ([[5, 78], [4, 16], [3, 4], [2, 1], [1, 1]] as [$stars, $pct]): ?>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:12.5px;">
              <span style="width:14px;text-align:right;color:var(--ink-3);"><?= $stars ?></span>
              <?= icon_star(11) ?>
              <div style="flex:1;height:6px;background:var(--bg-2);border-radius:999px;overflow:hidden;">
                <div style="width:<?= $pct ?>%;height:100%;background:var(--coral);"></div>
              </div>
              <span style="width:28px;color:var(--ink-3);"><?= $pct ?>%</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- Review list -->
      <div>
        <?php foreach (
          [
            ['Asha N.', 5, '2 days ago', 'Genuinely one of the best sellers on here. Products arrive beautifully packaged and the quality is exactly as described.'],
            ['Marko P.', 5, '1 week ago', 'Fast shipping, great product. Already ordered twice and will be back for more. Support small businesses!'],
            ['Jules R.', 4, '2 weeks ago', 'Really nice quality. Slightly smaller than I expected from the photos but otherwise exactly what I needed.'],
            ['Dahlia Q.', 5, '3 weeks ago', 'Perfect gift for a foodie friend. They loved it. Will definitely order again for the holidays.'],
            ['Eli H.', 4, '1 month ago', 'Good seller, responsive to questions. Products are a bit pricey but the quality justifies it.'],
          ] as $i => [$who, $stars, $when, $body]
        ): ?>
          <div style="padding:20px 0;border-top:1px <?= $i === 0 ? 'solid' : 'dashed' ?> var(--line);">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
              <div class="seller-avatar" style="width:36px;height:36px;font-size:14px;margin-bottom:0;border-width:0;background:var(--lime);color:var(--wine);flex-shrink:0;"><?= $who[0] ?></div>
              <div>
                <b style="font-size:14px;"><?= $who ?></b>
                <div style="display:inline-flex;gap:2px;color:var(--coral);margin-left:8px;"><?php for ($j = 0; $j < $stars; $j++) echo icon_star(12); ?></div>
              </div>
              <span class="muted" style="font-size:12px;margin-left:auto;"><?= $when ?></span>
            </div>
            <p style="margin:0;color:var(--ink-2);line-height:1.6;"><?= $body ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>


<?php include '_partials/footer.php'; ?>