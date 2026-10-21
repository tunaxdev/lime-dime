<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

$id = isset($_GET['id']) ? $_GET['id'] : 'p1';
$p  = find_product($id, $PRODUCTS);
if (!$p) {
  $p = $PRODUCTS[0];
}

$seller  = find_seller_by_name($p['seller'], $SELLERS);
$cat     = find_category($p['cat'], $CATEGORIES);
$related = [];
foreach ($PRODUCTS as $x) {
  if ($x['cat'] === $p['cat'] && $x['id'] !== $p['id']) $related[] = $x;
}
$related = array_slice($related, 0, 4);

$page_title  = $p['name'];
$active_route = 'product';
$show_catbar  = false;
include '_partials/header.php';
?>

<div class="container page">

  <div class="eyebrow" style="margin-bottom:16px;">
    <a href="/limedime/index.php" style="color:var(--ink-3);">Home</a> ·
    <a href="/limedime/category.php?cat=<?= $p['cat'] ?>" style="color:var(--ink-3);"><?= $cat ? $cat['label'] : $p['cat'] ?></a> ·
    <?= htmlspecialchars($p['name']) ?>
  </div>

  <div style="display:grid;grid-template-columns:1.1fr 1fr;gap:36px;">
    <div>
      <div style="background:var(--surface);border:1px solid var(--line);border-radius:var(--r-lg);overflow:hidden;aspect-ratio:1/1;">
        <?= product_thumb($p) ?>
      </div>
    </div>

    <div>
      <div class="eyebrow" style="color:var(--coral);"><?= htmlspecialchars($p['seller']) ?></div>
      <h1 class="h1" style="margin:8px 0 12px;font-size:40px;"><?= htmlspecialchars($p['name']) ?></h1>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
        <span style="display:inline-flex;align-items:center;gap:4px;color:var(--coral);">
          <?php for ($i = 0; $i < 5; $i++) echo icon_star(15); ?>
        </span>
        <span class="muted" style="font-size:13px;"><?= $p['rating'] ?> · <?= $p['reviews'] ?> reviews</span>
      </div>
      <div style="font-family:Fraunces,serif;font-size:42px;font-weight:700;color:var(--wine);letter-spacing:-0.02em;margin-bottom:18px;">
        $<?= number_format($p['price'], 2) ?>
      </div>
      <p class="muted" style="line-height:1.6;margin-bottom:24px;">
        Hand-finished in small batches by <?= htmlspecialchars($p['seller']) ?>. Each piece is unique, with subtle variation in tone and texture — exactly the kind of imperfection we love. Ships within 2 business days, packaged in recycled paper.
      </p>

      <?php $out_of_stock = $p['quantity'] <= 0; ?>
      <form id="atc-form" style="display:flex;gap:14px;align-items:center;margin-bottom:18px;">
        <input type="hidden" name="pid" value="<?= $p['id'] ?>">
        <input type="hidden" name="pname" value="<?= htmlspecialchars($p['name']) ?>">
        <input type="hidden" name="pprice" value="<?= $p['price'] ?>">
        <input type="hidden" name="pillu" value="<?= $p['illu'] ?>">
        <input type="hidden" name="ppalette" value="<?= implode(',', $p['palette']) ?>">
        <input type="hidden" name="pseller" value="<?= htmlspecialchars($p['seller']) ?>">
        <?php if ($out_of_stock): ?>
          <div style="padding:14px 20px;background:var(--surface-2);border:1.5px solid var(--line);border-radius:var(--r);color:var(--ink-3);font-weight:600;font-size:14px;">
            Out of stock
          </div>
        <?php else: ?>
          <div class="qty">
            <button type="button" onclick="adjQty(-1)"><?= icon_minus(14) ?></button>
            <span id="qty-display">1</span>
            <button type="button" onclick="adjQty(1)"><?= icon_plus(14) ?></button>
          </div>
          <button type="button" class="btn btn-primary btn-lg" onclick="addToCartAndGo()">
            <?= icon_bag(16) ?> Add to cart · <span id="atc-price">$<?= number_format($p['price'], 2) ?></span>
          </button>
        <?php endif; ?>
        <button type="button" class="icon-btn" id="fav-btn" data-fav="<?= $p['id'] ?>" onclick="toggleFav('<?= $p['id'] ?>')"><?= icon_heart(20) ?></button>
      </form>

      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:16px;background:var(--surface-2);border-radius:var(--r);border:1px solid var(--line);">
        <div style="display:flex;gap:10px;align-items:flex-start;">
          <span style="width:30px;height:30px;border-radius:8px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?= icon_package(16) ?></span>
          <div style="line-height:1.3;">
            <div style="font-size:12.5px;font-weight:700;">Free shipping</div>
            <div style="font-size:11.5px;color:var(--ink-3);">Over $40</div>
          </div>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-start;">
          <span style="width:30px;height:30px;border-radius:8px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?= icon_clock(16) ?></span>
          <div style="line-height:1.3;">
            <div style="font-size:12.5px;font-weight:700;">2-day dispatch</div>
            <div style="font-size:11.5px;color:var(--ink-3);">From the seller</div>
          </div>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-start;">
          <span style="width:30px;height:30px;border-radius:8px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?= icon_check(16) ?></span>
          <div style="line-height:1.3;">
            <div style="font-size:12.5px;font-weight:700;">30-day return</div>
            <div style="font-size:11.5px;color:var(--ink-3);">No questions</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:32px;margin-top:56px;">
    <div>
      <h3 class="h3" style="margin-bottom:16px;">What people say</h3>
      <?php
      $reviews = [
        ['Asha N.', 5, '5 days ago', 'Worth every dime. Packaging was thoughtful and the colour is even better in person.'],
        ['Marko P.', 5, '2 weeks ago', 'Quick shipping. Already ordered a second one for a friend.'],
        ['Jules R.', 4, '3 weeks ago', 'Lovely product, smaller than I expected from the photos but exactly what I needed.'],
      ];
      foreach ($reviews as $i => [$who, $stars, $when, $body]):
      ?>
        <div style="padding:16px 0;border-top:<?= $i ? '1px dashed var(--line)' : '1px solid var(--line)' ?>;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
            <div class="seller-avatar" style="width:32px;height:32px;font-size:13px;margin-bottom:0;border-width:0;background:var(--lime);color:var(--wine);"><?= $who[0] ?></div>
            <b style="font-size:14px;"><?= $who ?></b>
            <span style="display:inline-flex;gap:1px;color:var(--coral);"><?php for ($j = 0; $j < $stars; $j++) echo icon_star(12); ?></span>
            <span class="muted" style="font-size:12px;margin-left:auto;"><?= $when ?></span>
          </div>
          <p style="margin:0;color:var(--ink-2);"><?= $body ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($seller): ?>
      <aside>
        <h3 class="h3" style="margin-bottom:16px;">About the seller</h3>
        <a href="/limedime/seller.php?id=<?= $seller['id'] ?>" class="seller-card" style="text-decoration:none;">
          <div class="seller-cover" style="<?= !empty($seller['cover_image']) ? 'background:#111;' : '' ?>">
            <?php if (!empty($seller['cover_image'])): ?>
              <img src="/limedime/assets/img/shops/<?= htmlspecialchars($seller['cover_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
            <?php else: ?>
              <?= seller_cover($seller['accent'], $seller['pattern']) ?>
            <?php endif; ?>
          </div>
          <div class="seller-body">
            <div class="seller-avatar" style="background:<?= $seller['accent'] ?>;color:var(--lime);"><?= $seller['avatar'] ?></div>
            <div class="seller-name"><?= htmlspecialchars($seller['name']) ?></div>
            <div class="seller-sub"><?= htmlspecialchars($seller['tag']) ?></div>
            <div class="seller-stats">
              <span><b><?= $seller['products'] ?></b> products</span>
              <span><b><?= $seller['followers'] ?></b> followers</span>
              <span style="display:inline-flex;gap:4px;align-items:center;"><?= icon_star(11) ?> <b><?= $seller['rating'] ?></b></span>
            </div>
          </div>
        </a>
      </aside>
    <?php endif; ?>
  </div>

  <?php if (count($related) > 0): ?>
    <h3 class="h3" style="margin:56px 0 18px;">More from this shelf</h3>
    <div class="grid grid-products">
      <?php foreach ($related as $rp):
        $rcat = find_category($rp['cat'], $CATEGORIES);
        $rcat_color = $rcat ? $rcat['color'] : '#2A1A1D';
      ?>
        <a href="/limedime/product.php?id=<?= $rp['id'] ?>" class="product-card card" style="text-decoration:none;">
          <div class="product-thumb">
            <?= product_thumb($rp) ?>
            <?php if ($rp['quantity'] <= 0): ?><span class="product-tag" style="background:var(--ink-3);">Sold out</span><?php elseif ($rp['tag']): ?><span class="product-tag"><?= $rp['tag'] ?></span><?php endif; ?>
          </div>
          <div class="product-body">
            <div class="product-seller">
              <span style="width:6px;height:6px;border-radius:999px;background:<?= $rcat_color ?>;"></span>
              <span><?= htmlspecialchars($rp['seller']) ?></span>
            </div>
            <div class="product-name"><?= htmlspecialchars($rp['name']) ?></div>
            <div class="product-meta">
              <span class="product-price">$<?= number_format($rp['price'], 2) ?></span>
              <span class="product-rating"><?= icon_star(12) ?> <?= $rp['rating'] ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<script>
  var productPrice = <?= $p['price'] ?>;
  var productId = '<?= $p['id'] ?>';
  var qty = 1;

  function adjQty(d) {
    qty = Math.max(1, qty + d);
    document.getElementById('qty-display').textContent = qty;
    document.getElementById('atc-price').textContent = '$' + (productPrice * qty).toFixed(2);
  }

  function addToCartAndGo() {
    var cart = JSON.parse(localStorage.getItem('ld_cart') || '{}');
    var meta = JSON.parse(localStorage.getItem('ld_cart_meta') || '{}');
    cart[productId] = (cart[productId] || 0) + qty;
    meta[productId] = {
      name: '<?= addslashes($p['name']) ?>',
      price: productPrice,
      seller: '<?= addslashes($p['seller']) ?>',
      illu: '<?= $p['illu'] ?>',
      palette: ['<?= $p['palette'][0] ?>', '<?= $p['palette'][1] ?>']
    };
    localStorage.setItem('ld_cart', JSON.stringify(cart));
    localStorage.setItem('ld_cart_meta', JSON.stringify(meta));
    window.location.href = '/limedime/cart.php';
  }

  document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('fav-btn');
    if (btn && isFaved(productId)) btn.classList.add('on');
  });
</script>

<?php include '_partials/footer.php'; ?>