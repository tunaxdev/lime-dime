<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

$page_title  = 'Your cart';
$active_route = 'cart';
$show_catbar  = false;
include '_partials/header.php';
?>

<div class="container page">
  <h1 class="h1" style="margin-bottom:18px;">Your cart</h1>

  <!-- Cart is rendered client-side from localStorage -->
  <div id="cart-empty" style="display:none;">
    <div class="empty-state">
      <div style="margin-bottom:12px;display:flex;justify-content:center;color:var(--ink-4);"><?= icon_bag(48) ?></div>
      <h3 class="h3" style="margin:0 0 6px;">Nothing in here yet</h3>
      <p class="muted">Add a few things from the shelves and they'll show up here.</p>
      <a href="/limedime/index.php" class="btn btn-primary" style="margin-top:16px;display:inline-flex;">Start browsing</a>
    </div>
  </div>

  <div id="cart-content" style="display:none;">
    <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:32px;">
      <div id="cart-lines"></div>
      <aside style="position:sticky;top:88px;align-self:start;">
        <div class="card" style="padding:22px;">
          <h3 class="h3" style="margin:0 0 16px;">Order summary</h3>
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;">
            <span style="color:var(--ink-3);">Subtotal</span>
            <span id="summary-subtotal">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;">
            <span style="color:var(--ink-3);">Shipping</span>
            <span id="summary-shipping">—</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;">
            <span style="color:var(--ink-3);">Estimated tax</span>
            <span>—</span>
          </div>
          <div style="height:1px;background:var(--line);margin:16px 0;"></div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;">
            <span><b>Total</b></span>
            <span id="summary-total" style="font-family:Fraunces,serif;font-size:22px;color:var(--wine);font-weight:700;">—</span>
          </div>
          <a href="/limedime/checkout.php" class="btn btn-primary btn-lg btn-block" style="margin-top:16px;display:flex;justify-content:center;">
            Checkout <?= icon_chev(14) ?>
          </a>
          <p class="muted" id="shipping-note" style="font-size:11.5px;margin-top:10px;text-align:center;"></p>
        </div>
      </aside>
    </div>
  </div>
</div>

<script>
  <?php
  $cart_products_js = [];
  foreach ($PRODUCTS as $p) {
    $cart_products_js[] = [
      'id' => $p['id'],
      'name' => $p['name'],
      'price' => $p['price'],
      'seller' => $p['seller'],
      'illu' => $p['illu'],
      'palette' => $p['palette'],
    ];
  }
  ?>
  var PRODUCTS_DATA = <?= json_encode($cart_products_js) ?>;

  function buildIlluSVG(illu, palette) {

    var a = palette[0],
      b = palette[1];
    return '<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="' + a + '" opacity=".3"/><circle cx="100" cy="100" r="50" fill="' + a + '" opacity=".6"/></svg>';
  }

  function renderCart() {
    var cart = JSON.parse(localStorage.getItem('ld_cart') || '{}');
    var meta = JSON.parse(localStorage.getItem('ld_cart_meta') || '{}');
    var keys = Object.keys(cart).filter(k => cart[k] > 0);

    if (keys.length === 0) {
      document.getElementById('cart-empty').style.display = '';
      document.getElementById('cart-content').style.display = 'none';
      return;
    }
    document.getElementById('cart-empty').style.display = 'none';
    document.getElementById('cart-content').style.display = '';

    var subtotal = 0;
    var html = '';
    keys.forEach(function(pid) {
      var q = cart[pid];
      var m = meta[pid];
      if (!m) {

        var found = PRODUCTS_DATA.find(p => p.id === pid);
        if (found) m = {
          name: found.name,
          price: found.price,
          seller: found.seller,
          illu: found.illu,
          palette: found.palette
        };
      }
      if (!m) return;
      subtotal += m.price * q;
      html += '<div class="cart-line" id="line-' + pid + '">' +
        '<div class="cart-thumb">' + buildIlluSVG(m.illu, m.palette) + '</div>' +
        '<div><div class="eyebrow" style="margin-bottom:2px;">' + esc(m.seller) + '</div>' +
        '<div style="font-family:Fraunces,serif;font-size:17px;font-weight:600;">' + esc(m.name) + '</div>' +
        '<div class="muted" style="font-size:12.5px;margin-top:4px;">$' + m.price.toFixed(2) + ' each</div></div>' +
        '<div class="qty">' +
        '<button onclick="updateQty(\'' + pid + '\',-1)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M5 12h14"/></svg></button>' +
        '<span>' + q + '</span>' +
        '<button onclick="updateQty(\'' + pid + '\',1)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></button>' +
        '</div>' +
        '<div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">' +
        '<div style="font-family:Fraunces,serif;font-size:19px;font-weight:700;color:var(--wine);">$' + (m.price * q).toFixed(2) + '</div>' +
        '<button class="icon-btn" style="width:32px;height:32px;" onclick="updateQty(\'' + pid + '\',0-' + q + ')">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/></svg>' +
        '</button></div>' +
        '</div>';
    });

    document.getElementById('cart-lines').innerHTML = html;

    var shipping = subtotal > 40 ? 0 : 4.90;
    var total = subtotal + shipping;
    document.getElementById('summary-subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('summary-shipping').innerHTML = shipping === 0 ?
      '<span style="color:var(--teal);">Free</span>' : '$' + shipping.toFixed(2);
    document.getElementById('summary-total').textContent = '$' + total.toFixed(2);
    document.getElementById('shipping-note').textContent = shipping === 0 ?
      'You unlocked free shipping' :
      'Add $' + (40 - subtotal).toFixed(2) + ' more for free shipping';
  }

  function updateQty(pid, delta) {
    var cart = JSON.parse(localStorage.getItem('ld_cart') || '{}');
    cart[pid] = (cart[pid] || 0) + delta;
    if (cart[pid] <= 0) delete cart[pid];
    localStorage.setItem('ld_cart', JSON.stringify(cart));
    renderCart();
    updateNavCount();
  }

  function esc(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
  }

  document.addEventListener('DOMContentLoaded', renderCart);
</script>

<?php include '_partials/footer.php'; ?>