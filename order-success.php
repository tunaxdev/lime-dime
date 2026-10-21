<?php
include '_partials/data.php';
include '_partials/icons.php';

$order_id  = (int)($_GET['id'] ?? 0);
$order_ref = $order_id > 0 ? 'LD-' . str_pad($order_id, 4, '0', STR_PAD_LEFT) : '';

$order = null;
if ($order_id > 0) {
    $result = mysqli_query($conn,
        "SELECT o.*, COUNT(ol.id) AS items
         FROM orders o LEFT JOIN order_line ol ON ol.order_id = o.id
         WHERE o.id = $order_id GROUP BY o.id"
    );
    if ($result) {
        $order = mysqli_fetch_assoc($result) ?: null;
    }
}

$page_title   = 'Order placed';
$active_route = 'success';
$show_catbar  = false;
include '_partials/header.php';
?>

<div class="container container-narrow page" style="text-align:center;padding-top:64px;">
    <div style="width:96px;height:96px;border-radius:999px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <?= icon_check(44) ?>
    </div>
    <div class="eyebrow">Order placed</div>
    <h1 class="h1" style="margin:12px 0;">Thanks for shopping small.</h1>
    <p class="muted" style="font-size:17px;max-width:460px;margin:0 auto 24px;">
        <?php if ($order_ref): ?>
        Your order <b><?= $order_ref ?></b> is confirmed<?= $order ? ' · ' . (int)$order['items'] . ' item' . ((int)$order['items'] !== 1 ? 's' : '') . ' · $' . number_format((float)$order['total'], 2) : '' ?>.
        We'll update you as it's packed and shipped.
        <?php else: ?>
        Your order is confirmed. We'll update you as it's packed and shipped.
        <?php endif; ?>
    </p>
    <div style="display:flex;gap:10px;justify-content:center;">
        <a href="/limedime/orders.php" class="btn btn-primary">View order</a>
        <a href="/limedime/index.php" class="btn btn-ghost">Keep shopping</a>
    </div>
</div>

<script>
    localStorage.removeItem('ld_cart');
    localStorage.removeItem('ld_cart_meta');
    document.cookie = 'ld_cart_count=0;path=/;max-age=31536000';
</script>

<?php include '_partials/footer.php'; ?>
