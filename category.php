<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

$cat_id  = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$query   = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort    = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';

$cat_info = find_category($cat_id, $CATEGORIES);
if (!$cat_info) {
  $cat_id = 'all';
  $cat_info = $CATEGORIES[0];
}

$filtered = [];
foreach ($PRODUCTS as $p) {
  $match_cat = ($cat_id === 'all' || $p['cat'] === $cat_id);
  $match_q   = ($query === '' || stripos($p['name'], $query) !== false || stripos($p['seller'], $query) !== false);
  if ($match_cat && $match_q) $filtered[] = $p;
}

if ($sort === 'price-asc') {
  usort($filtered, function($a, $b) {
    if ($a['price'] == $b['price']) return 0;
    return $a['price'] < $b['price'] ? -1 : 1;
  });
}
if ($sort === 'price-desc') {
  usort($filtered, function($a, $b) {
    if ($a['price'] == $b['price']) return 0;
    return $a['price'] > $b['price'] ? -1 : 1;
  });
}
if ($sort === 'rating') {
  usort($filtered, function($a, $b) {
    if ($a['rating'] == $b['rating']) return 0;
    return $a['rating'] > $b['rating'] ? -1 : 1;
  });
}

$page_title  = $cat_id === 'all' ? 'All products' : $cat_info['label'];
$active_route = 'category';
$show_catbar  = false;
$active_cat   = $cat_id;
include '_partials/header.php';
?>

<div class="container page">

  <div class="eyebrow" style="margin-bottom:8px;">
    <a href="/limedime/index.php" style="color:var(--ink-3);">Home</a> · <?= $cat_info['label'] ?>
    <?php if ($query): ?> · "<?= htmlspecialchars($query) ?>"<?php endif; ?>
  </div>

  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
    <div>
      <h1 class="h1" style="margin:0;"><?= $cat_id === 'all' ? 'All products' : $cat_info['label'] ?></h1>
      <p class="muted" style="margin-top:6px;margin-bottom:0;">
        <?= count($filtered) ?> <?= count($filtered) === 1 ? 'item' : 'items' ?> · curated from <?= $cat_id === 'all' ? '1,240' : '210' ?> sellers
      </p>
    </div>
    <form method="get" style="display:flex;gap:10px;align-items:center;">
      <?php if ($cat_id !== 'all'): ?><input type="hidden" name="cat" value="<?= $cat_id ?>">
      <?php endif; ?>
      <?php if ($query): ?><input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>">
      <?php endif; ?>
      <select name="sort" onchange="this.form.submit()"
        style="border:1px solid var(--line);border-radius:999px;padding:7px 14px;background:var(--surface);font-weight:700;font-size:12.5px;color:var(--ink-2);outline:none;">
        <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>Sort: Relevance</option>
        <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Price: low → high</option>
        <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Price: high → low</option>
        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Highest rated</option>
      </select>
    </form>
  </div>

  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px;">
    <?php foreach ($CATEGORIES as $cat):
      $is_active = ($active_cat === $cat['id']);
      $dot_style = $is_active ? 'background:rgba(225,221,143,.25);color:var(--lime);' : 'background:' . $cat['color'] . '22;color:' . $cat['color'] . ';';
    ?>
      <a href="/limedime/category.php?cat=<?= $cat['id'] ?><?= $query ? '&q=' . urlencode($query) : '' ?>"
        class="cat-chip <?= $is_active ? 'active' : '' ?>" style="text-decoration:none;">
        <?php if ($cat['id'] !== 'all'): ?>
          <span class="cat-dot" style="<?= $dot_style ?>"><?= cat_icon($cat['id'], 13) ?></span>
        <?php endif; ?>
        <span><?= $cat['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (count($filtered) > 0): ?>
    <div class="grid grid-products">
      <?php foreach ($filtered as $p):
        $cat = find_category($p['cat'], $CATEGORIES);
        $cat_color = $cat ? $cat['color'] : '#2A1A1D';
      ?>
        <a href="/limedime/product.php?id=<?= $p['id'] ?>" class="product-card card" style="text-decoration:none;">
          <div class="product-thumb">
            <?= product_thumb($p) ?>
            <?php if ($p['quantity'] <= 0): ?><span class="product-tag" style="background:var(--ink-3);">Sold out</span><?php elseif ($p['tag']): ?><span class="product-tag"><?= $p['tag'] ?></span><?php endif; ?>
            <button class="product-fav" data-fav="<?= $p['id'] ?>" onclick="event.preventDefault();toggleFav('<?= $p['id'] ?>')" aria-label="Favourite"><?= icon_heart(16) ?></button>
          </div>
          <div class="product-body">
            <div class="product-seller">
              <span style="width:6px;height:6px;border-radius:999px;background:<?= $cat_color ?>;"></span>
              <span><?= htmlspecialchars($p['seller']) ?></span>
            </div>
            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="product-meta">
              <span class="product-price">$<?= number_format($p['price'], 2) ?></span>
              <span class="product-rating"><?= icon_star(12) ?> <?= $p['rating'] ?> <span style="color:var(--ink-4);font-weight:500;">(<?= $p['reviews'] ?>)</span></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <h3 class="h3" style="margin:0 0 6px;">Nothing in this aisle yet</h3>
      <p class="muted" style="margin:0;">Try another category or come back next week — new sellers join every Monday.</p>
    </div>
  <?php endif; ?>

</div>

<?php include '_partials/footer.php'; ?>