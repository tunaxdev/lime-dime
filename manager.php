<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

require_login('/limedime/login.php');
require_role('seller', '/limedime/index.php');

$u   = current_user();
$uid = (int)$u['id'];

$shop_row = null;
$sq = mysqli_query($conn, "SELECT s.*, (SELECT COUNT(*) FROM products p WHERE p.shop_id=s.id AND p.status='live') AS products_count FROM shops s WHERE s.manager_id=$uid LIMIT 1");
if ($sq) $shop_row = mysqli_fetch_assoc($sq);
if (!$shop_row) {
    header('Location: /limedime/index.php');
    exit;
}
$shop    = _normalize_shop($shop_row);
$shop_id = (int)$shop_row['id'];


function _save_product_image($pid)
{
    global $conn;
    if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) return;
    $file    = $_FILES['product_image'];
    $mime    = mime_content_type($file['tmp_name']);
    $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($ext_map[$mime]) || $file['size'] > 5 * 1024 * 1024) return;
    $dir = __DIR__ . '/assets/img/products/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    foreach (['jpg', 'png', 'webp'] as $e) {
        $old = $dir . $pid . '.' . $e;
        if (file_exists($old)) unlink($old);
    }
    $fn      = $pid . '.' . $ext_map[$mime];
    move_uploaded_file($file['tmp_name'], $dir . $fn);
    $safe_fn = mysqli_real_escape_string($conn, $fn);
    mysqli_query($conn, "UPDATE products SET product_image='$safe_fn' WHERE id=$pid");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'approve_cancel') {
        $oid = (int)($_POST['order_id'] ?? 0);
        if ($oid) {
            mysqli_query($conn, "UPDATE orders SET status='cancelled' WHERE id=$oid AND status='cancel_requested'");
        }
        header('Location: /limedime/manager.php?tab=orders&ok=cancel_approved');
        exit;
    } elseif ($act === 'decline_cancel') {
        $oid = (int)($_POST['order_id'] ?? 0);
        if ($oid) {
            mysqli_query($conn, "UPDATE orders SET status='accepted', cancel_note=NULL WHERE id=$oid AND status='cancel_requested'");
        }
        header('Location: /limedime/manager.php?tab=orders&ok=cancel_declined');
        exit;
    } elseif ($act === 'add_product') {
        $name  = trim($_POST['name']     ?? '');
        $desc  = trim($_POST['desc']     ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $qty   = (int)($_POST['qty']     ?? 0);
        $cat   = in_array($_POST['cat'] ?? '', ['food', 'clothing', 'electronics', 'education', 'beauty'])
            ? $_POST['cat'] : 'food';
        $pstat = ($_POST['pstatus'] ?? '') === 'live' ? 'live' : 'draft';
        if ($name && $price >= 0) {
            $sn = mysqli_real_escape_string($conn, $name);
            $sd = mysqli_real_escape_string($conn, $desc);
            $sc = mysqli_real_escape_string($conn, $cat);
            $sp = mysqli_real_escape_string($conn, $pstat);
            mysqli_query($conn, "INSERT INTO products (name, description, price, quantity, shop_id, category, status, rating, reviews_count, illu_type, palette_1, palette_2) VALUES ('$sn', '$sd', $price, $qty, $shop_id, '$sc', '$sp', 0, 0, 'jar', '#E0777D', '#8E3B46')");
            $new_id = (int)mysqli_insert_id($conn);
            _save_product_image($new_id);
        }
        header('Location: /limedime/manager.php?tab=products&ok=added');
        exit;
    } elseif ($act === 'edit_product') {
        $pid   = (int)($_POST['product_id'] ?? 0);
        $name  = trim($_POST['name']    ?? '');
        $desc  = trim($_POST['desc']    ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $qty   = (int)($_POST['qty']    ?? 0);
        $cat   = in_array($_POST['cat'] ?? '', ['food', 'clothing', 'electronics', 'education', 'beauty'])
            ? $_POST['cat'] : 'food';
        $pstat = in_array($_POST['pstatus'] ?? '', ['live', 'draft', 'archived'])
            ? $_POST['pstatus'] : 'live';
        if ($pid && $name && $price >= 0) {
            $sn = mysqli_real_escape_string($conn, $name);
            $sd = mysqli_real_escape_string($conn, $desc);
            $sc = mysqli_real_escape_string($conn, $cat);
            $sp = mysqli_real_escape_string($conn, $pstat);
            mysqli_query($conn, "UPDATE products SET name='$sn', description='$sd', price=$price, quantity=$qty, category='$sc', status='$sp' WHERE id=$pid AND shop_id=$shop_id");
            _save_product_image($pid);
        }
        header('Location: /limedime/manager.php?tab=products&ok=edited');
        exit;
    } elseif ($act === 'upload_cover') {
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['cover'];
            $mime    = mime_content_type($file['tmp_name']);
            $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (isset($ext_map[$mime]) && $file['size'] <= 5 * 1024 * 1024) {
                $dir = __DIR__ . '/assets/img/shops/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                foreach (['jpg', 'png', 'webp'] as $e) {
                    $old = $dir . $shop_id . '.' . $e;
                    if (file_exists($old)) unlink($old);
                }
                $fn      = $shop_id . '.' . $ext_map[$mime];
                move_uploaded_file($file['tmp_name'], $dir . $fn);
                $safe_fn = mysqli_real_escape_string($conn, $fn);
                mysqli_query($conn, "UPDATE shops SET cover_image='$safe_fn' WHERE id=$shop_id");
            }
        }
        header('Location: /limedime/manager.php?tab=products&ok=cover');
        exit;
    } elseif ($act === 'update_shop') {
        $sname   = trim($_POST['shop_name']   ?? '');
        $stype   = in_array($_POST['type'] ?? '', ['food', 'clothing', 'electronics', 'education', 'beauty']) ? $_POST['type'] : 'food';
        $sloc    = trim($_POST['location']    ?? '');
        $savatar = mb_substr(trim($_POST['avatar'] ?? ''), 0, 4);
        $saccent = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['accent_color'] ?? '') ? $_POST['accent_color'] : '#E0777D';
        $spat    = in_array($_POST['pattern'] ?? '', ['dots', 'wave', 'grid', 'rays']) ? $_POST['pattern'] : 'dots';
        if ($sname) {
            $s1 = mysqli_real_escape_string($conn, $sname);
            $s2 = mysqli_real_escape_string($conn, $stype);
            $s3 = mysqli_real_escape_string($conn, $sloc);
            $s4 = mysqli_real_escape_string($conn, $savatar);
            $s5 = mysqli_real_escape_string($conn, $saccent);
            $s6 = mysqli_real_escape_string($conn, $spat);
            mysqli_query($conn, "UPDATE shops SET name='$s1', type='$s2', location='$s3', avatar='$s4', accent_color='$s5', pattern='$s6' WHERE id=$shop_id");
        }
        header('Location: /limedime/manager.php?tab=settings&ok=shop');
        exit;
    }
}


$shop_products = [];
$pq = mysqli_query($conn, "SELECT p.*, s.name AS shop_name FROM products p JOIN shops s ON s.id=p.shop_id WHERE p.shop_id=$shop_id ORDER BY p.id DESC");
if ($pq) {
    while ($prow = mysqli_fetch_assoc($pq)) $shop_products[] = _normalize_product($prow);
}

$shop_orders = [];
$oq = mysqli_query($conn, "SELECT o.id, o.order_date, o.status, o.total, u.name AS customer_name, (SELECT COUNT(*) FROM order_line WHERE order_id=o.id) AS items FROM orders o JOIN users u ON u.id=o.customer_id WHERE o.id IN (SELECT DISTINCT ol2.order_id FROM order_line ol2 JOIN products p2 ON p2.id=ol2.product_id WHERE p2.shop_id=$shop_id) ORDER BY o.order_date DESC LIMIT 50");
if ($oq) {
    while ($row = mysqli_fetch_assoc($oq)) {
        $shop_orders[] = [
            'id'       => 'LD-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
            'db_id'    => (int)$row['id'],
            'date'     => date('M j, Y', strtotime($row['order_date'])),
            'status'   => ucwords(str_replace('_', ' ', $row['status'])),
            'total'    => (float)$row['total'],
            'items'    => (int)$row['items'],
            'customer' => $row['customer_name'],
        ];
    }
}

$cancel_requests = [];
$cq = mysqli_query($conn, "SELECT o.id, o.order_date, o.total, o.cancel_note, u.name AS customer_name, (SELECT COUNT(*) FROM order_line WHERE order_id=o.id) AS items FROM orders o JOIN users u ON u.id=o.customer_id WHERE o.status='cancel_requested' AND o.id IN (SELECT DISTINCT ol2.order_id FROM order_line ol2 JOIN products p2 ON p2.id=ol2.product_id WHERE p2.shop_id=$shop_id) ORDER BY o.order_date DESC");
if ($cq) {
    while ($row = mysqli_fetch_assoc($cq)) {
        $cancel_requests[] = [
            'id'       => 'LD-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
            'db_id'    => (int)$row['id'],
            'date'     => date('M j, Y · H:i', strtotime($row['order_date'])),
            'total'    => (float)$row['total'],
            'items'    => (int)$row['items'],
            'customer' => $row['customer_name'],
            'note'     => $row['cancel_note'] ?? '',
        ];
    }
}

$shop_deliveries = [];
$dq = mysqli_query($conn, "SELECT o.id, o.status, o.total, o.order_date, u.name AS customer_name, (SELECT COUNT(*) FROM order_line WHERE order_id=o.id) AS items FROM orders o JOIN users u ON u.id=o.customer_id WHERE o.id IN (SELECT DISTINCT ol2.order_id FROM order_line ol2 JOIN products p2 ON p2.id=ol2.product_id WHERE p2.shop_id=$shop_id) AND o.status IN ('out_for_delivery','delivered') ORDER BY o.order_date DESC LIMIT 50");
if ($dq) {
    while ($row = mysqli_fetch_assoc($dq)) {
        $shop_deliveries[] = [
            'id'       => 'LD-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
            'db_id'    => (int)$row['id'],
            'date'     => date('M j · H:i', strtotime($row['order_date'])),
            'status'   => $row['status'],
            'total'    => (float)$row['total'],
            'items'    => (int)$row['items'],
            'customer' => $row['customer_name'],
        ];
    }
}

$rev_by_day = [];
$rq = mysqli_query($conn, "SELECT DATE(o.order_date) AS day, ROUND(SUM(ol.total_price),2) AS rev FROM orders o JOIN order_line ol ON ol.order_id=o.id JOIN products p ON p.id=ol.product_id WHERE p.shop_id=$shop_id AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) AND o.status != 'cancelled' GROUP BY DATE(o.order_date) ORDER BY day ASC");
if ($rq) {
    while ($r = mysqli_fetch_assoc($rq)) $rev_by_day[$r['day']] = (float)$r['rev'];
}

function mk_rev_chart($rev_by_day, $days, $gid)
{
    $w   = 600;
    $h   = 180;
    $pad = 16;
    $now = new DateTime();
    $vals = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d      = (clone $now)->modify("-{$i} days")->format('Y-m-d');
        $vals[] = (float)($rev_by_day[$d] ?? 0);
    }
    $max    = max(array_merge($vals, [1]));
    $n      = count($vals);
    $coords = [];
    foreach ($vals as $i => $v) {
        $x        = round($w * $i / max($n - 1, 1), 2);
        $y        = round($h - $pad - ($v / $max) * ($h - 2 * $pad), 2);
        $coords[] = "$x $y";
    }
    $line = 'M ' . implode(' L ', $coords);
    [$lx] = explode(' ', end($coords));
    $area = "$line L $lx $h L 0 $h Z";
    $grid = '';
    for ($i = 0; $i < 4; $i++) {
        $y     = $i * 45;
        $grid .= "<line x1=\"0\" x2=\"600\" y1=\"{$y}\" y2=\"{$y}\" stroke=\"var(--line)\" stroke-width=\"1\"/>";
    }
    $dots = '';
    $step = max(1, (int)($n / 4));
    $seen = [];
    for ($i = 0; $i < $n; $i += $step) {
        $seen[$i] = 1;
        [$cx, $cy] = explode(' ', $coords[$i]);
        $dots .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"4\" fill=\"var(--lime)\" stroke=\"var(--coral)\" stroke-width=\"2\"/>";
    }
    if (!isset($seen[$n - 1])) {
        [$cx, $cy] = explode(' ', end($coords));
        $dots .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"4\" fill=\"var(--lime)\" stroke=\"var(--coral)\" stroke-width=\"2\"/>";
    }
    return "<defs><linearGradient id=\"{$gid}\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0%\" stop-color=\"var(--coral)\" stop-opacity=\".35\"/><stop offset=\"100%\" stop-color=\"var(--coral)\" stop-opacity=\"0\"/></linearGradient></defs>{$grid}<path d=\"{$area}\" fill=\"url(#{$gid})\"/><path d=\"{$line}\" fill=\"none\" stroke=\"var(--coral)\" stroke-width=\"3\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>{$dots}";
}

$rev_charts = [
    '7d'  => mk_rev_chart($rev_by_day,  7, 'revG7'),
    '30d' => mk_rev_chart($rev_by_day, 30, 'revG30'),
    '90d' => mk_rev_chart($rev_by_day, 90, 'revG90'),
];

$rev_totals = [];
foreach (['7d' => 7, '30d' => 30, '90d' => 90] as $label => $days) {
    $now = new DateTime();
    $t   = 0;
    for ($i = $days - 1; $i >= 0; $i--) {
        $d  = (clone $now)->modify("-{$i} days")->format('Y-m-d');
        $t += $rev_by_day[$d] ?? 0;
    }
    $rev_totals[$label] = round($t, 2);
}

$today_orders   = 0;
$today_rev      = 0.0;
$pending_orders = 0;
$tsq = mysqli_query($conn, "SELECT
    (SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_line ol ON ol.order_id=o.id JOIN products p ON p.id=ol.product_id WHERE p.shop_id=$shop_id AND DATE(o.order_date)=CURDATE()) AS today_orders,
    (SELECT COALESCE(SUM(ol.total_price),0) FROM order_line ol JOIN products p ON p.id=ol.product_id JOIN orders o ON o.id=ol.order_id WHERE p.shop_id=$shop_id AND DATE(o.order_date)=CURDATE()) AS today_rev,
    (SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_line ol ON ol.order_id=o.id JOIN products p ON p.id=ol.product_id WHERE p.shop_id=$shop_id AND o.status='pending') AS pending_orders");
if ($tsq && $ts = mysqli_fetch_assoc($tsq)) {
    $today_orders   = (int)$ts['today_orders'];
    $today_rev      = (float)$ts['today_rev'];
    $pending_orders = (int)$ts['pending_orders'];
}

$tab     = $_GET['tab'] ?? 'dashboard';
$allowed = ['dashboard', 'products', 'orders', 'deliveries', 'settings'];
if (!in_array($tab, $allowed)) $tab = 'dashboard';

$active_del_cnt = 0;
foreach ($shop_deliveries as $d) {
    if ($d['status'] === 'out_for_delivery') $active_del_cnt++;
}

$page_title   = 'Manager — ' . $shop['name'];
$active_route = 'manager';
$show_catbar  = false;
include '_partials/header.php';

$sidebar_items = [
    ['id' => 'dashboard',  'label' => 'Dashboard',  'icon' => icon_home(18)],
    ['id' => 'products',   'label' => 'Products',   'icon' => icon_bag(18),     'count' => count($shop_products) ?: null],
    ['id' => 'orders',     'label' => 'Orders',     'icon' => icon_package(18), 'count' => ($pending_orders + count($cancel_requests)) ?: null],
    ['id' => 'deliveries', 'label' => 'Deliveries', 'icon' => icon_bike(18),    'count' => $active_del_cnt ?: null],
    ['id' => 'settings',   'label' => 'Settings',   'icon' => icon_settings(18)],
];
?>

<div class="container">
  <div class="sidebar-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div style="width:32px;height:32px;border-radius:10px;background:<?= $shop['accent'] ?>;color:var(--lime);display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;"><?= $shop['avatar'] ?></div>
        <div style="line-height:1.1;"><b><?= htmlspecialchars($shop['name']) ?></b>
          <div class="role">Manager · <?= ucfirst($shop['cat']) ?></div>
        </div>
      </div>
      <nav>
        <?php foreach ($sidebar_items as $item): ?>
          <a href="/limedime/manager.php?tab=<?= $item['id'] ?>" class="side-link <?= $tab === $item['id'] ? 'active' : '' ?>">
            <?= $item['icon'] ?> <span><?= $item['label'] ?></span>
            <?php if (!empty($item['count'])): ?><span class="count"><?= $item['count'] ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div style="margin-top:18px;padding-top:14px;border-top:1px dashed var(--line);">
        <a href="/limedime/seller.php?id=<?= $shop['id'] ?>" class="side-link"><?= icon_store(18) ?> <span>View shop page</span></a>
        <a href="/limedime/index.php" class="side-link"><?= icon_logout(18) ?> <span>Back to shop</span></a>
      </div>
    </aside>

    <main>

      <?php
      $ok = $_GET['ok'] ?? '';
      if ($ok === 'added'): ?>
        <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Product added.</div>
      <?php elseif ($ok === 'edited'): ?>
        <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Product updated.</div>
      <?php elseif ($ok === 'cover'): ?>
        <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Cover image updated. <a href="/limedime/seller.php?id=<?= $shop['id'] ?>" style="color:#3a7a28;font-weight:700;">View shop →</a></div>
      <?php elseif ($ok === 'shop'): ?>
        <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Shop settings saved. <a href="/limedime/seller.php?id=<?= $shop['id'] ?>" style="color:#3a7a28;font-weight:700;">View shop →</a></div>
      <?php endif; ?>

      <?php if ($tab === 'dashboard'): ?>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px;">
          <div>
            <div class="eyebrow"><?= date('l, M j · H:i') ?></div>
            <h1 class="h1" style="margin:4px 0 0;"><?= htmlspecialchars($shop['name']) ?></h1>
          </div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-ghost btn-sm" onclick="openModal('add-product-modal')"><?= icon_plus(14) ?> Add product</button>
            <a href="/limedime/manager.php?tab=orders" class="btn btn-primary btn-sm">View orders</a>
          </div>
        </div>

        <div class="stat-grid">
          <div class="stat">
            <div class="eyebrow">Orders today</div>
            <div class="stat-val"><?= $today_orders ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Revenue today</div>
            <div class="stat-val">$<?= number_format($today_rev, 2) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Products live</div>
            <div class="stat-val"><?= $shop['products'] ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Avg. rating</div>
            <div class="stat-val"><?= $shop['rating'] ?></div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:18px;margin-bottom:18px;">
          <div class="card" style="padding:22px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;">
              <div>
                <h3 class="h3" style="margin:0;" id="rev-period-label">Revenue, last 30 days</h3>
                <div style="font-family:Fraunces,serif;font-size:20px;font-weight:700;color:var(--wine);margin-top:2px;" id="rev-total-display">$<?= number_format($rev_totals['30d'], 2) ?></div>
              </div>
              <div class="tabs">
                <?php foreach (['7d', '30d', '90d'] as $t): ?>
                  <button class="tab rev-tab <?= $t === '30d' ? 'active' : '' ?>" data-period="<?= $t ?>" onclick="switchRevPeriod('<?= $t ?>')"><?= $t ?></button>
                <?php endforeach; ?>
              </div>
            </div>
            <svg id="rev-chart" viewBox="0 0 600 180" style="width:100%;height:180px;">
              <?= $rev_charts['30d'] ?>
            </svg>
            <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--ink-4);">
              <span id="rev-start-label">30 days ago</span><span>Today</span>
            </div>
          </div>

          <div class="card" style="padding:22px;">
            <h3 class="h3" style="margin-top:0;margin-bottom:14px;">Top products</h3>
            <?php
            $live_p = [];
            foreach ($shop_products as $p) {
              if ($p['status'] === 'live') $live_p[] = $p;
            }
            ?>
            <?php if (empty($live_p)): ?>
              <p class="muted" style="font-size:13px;">No live products yet.</p>
              <button class="btn btn-ghost btn-sm" style="margin-top:8px;" onclick="openModal('add-product-modal')"><?= icon_plus(14) ?> Add product</button>
            <?php else: ?>
              <?php foreach (array_slice($live_p, 0, 4) as $i => $p):
                $widths = [88, 72, 58, 44]; ?>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                  <span style="width:32px;height:32px;border-radius:8px;overflow:hidden;flex-shrink:0;display:inline-flex;"><?= product_illu($p['illu'], $p['palette']) ?></span>
                  <div style="flex:1;">
                    <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;"><?= htmlspecialchars($p['name']) ?></div>
                    <div style="height:4px;background:var(--bg-2);border-radius:999px;margin-top:5px;overflow:hidden;">
                      <div style="width:<?= $widths[$i] ?>%;height:100%;background:var(--coral);"></div>
                    </div>
                  </div>
                  <b style="font-size:13px;color:var(--wine);">$<?= number_format($p['price'], 2) ?></b>
                </div>
              <?php endforeach; ?>
              <a href="/limedime/manager.php?tab=products" style="color:var(--wine);font-size:13px;font-weight:700;">See all →</a>
            <?php endif; ?>
          </div>
        </div>

        <div class="table-wrap">
          <div class="table-head">
            <h3>Recent orders</h3><a href="/limedime/manager.php?tab=orders" style="color:var(--wine);font-weight:700;font-size:13px;">View all</a>
          </div>
          <table>
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($shop_orders, 0, 5) as $o):
                $st  = strtolower(str_replace(' ', '_', $o['status']));
                $spl = ['pending' => 'warn', 'accepted' => 'info', 'packed' => 'info', 'out_for_delivery' => 'warn', 'delivered' => 'success', 'cancelled' => 'danger'][$st] ?? '';
              ?>
                <tr>
                  <td><b style="font-family:Fraunces,serif;"><?= $o['id'] ?></b></td>
                  <td class="muted"><?= htmlspecialchars($o['customer']) ?></td>
                  <td class="muted"><?= $o['date'] ?></td>
                  <td><?= $o['items'] ?></td>
                  <td><b style="color:var(--wine);">$<?= number_format($o['total'], 2) ?></b></td>
                  <td><span class="pill <?= $spl ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($shop_orders)): ?><tr>
                  <td colspan="6" style="text-align:center;color:var(--ink-3);padding:28px 0;">No orders yet.</td>
                </tr><?php endif; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($tab === 'products'): ?>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px;">
          <div>
            <h1 class="h1" style="margin:0;">Products</h1>
            <p class="muted" style="margin-top:6px;"><?= count($shop_products) ?> listings</p>
          </div>
          <div style="display:flex;gap:8px;align-items:center;">
            <form method="post" enctype="multipart/form-data" style="display:inline;" id="cover-form">
              <input type="hidden" name="action" value="upload_cover">
              <label style="cursor:pointer;" title="Upload shop cover photo">
                <span class="btn btn-ghost btn-sm"><?= icon_plus(14) ?> Cover photo</span>
                <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp" style="display:none;"
                  onchange="this.closest('form').submit();">
              </label>
            </form>
            <button class="btn btn-primary btn-sm" onclick="openModal('add-product-modal')"><?= icon_plus(14) ?> Add product</button>
          </div>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shop_products as $p):
                $cat      = find_category($p['cat'], $CATEGORIES);
                $pst      = $p['status'];
                $spl      = $pst === 'live' ? 'success' : ($pst === 'archived' ? 'danger' : 'warn');
                $pst_label = ['live' => 'Available', 'draft' => 'Not available', 'archived' => 'Archived'][$pst] ?? ucfirst($pst);
              ?>
                <tr>
                  <td>
                    <div class="row-avatar">
                      <span style="width:36px;height:36px;border-radius:8px;overflow:hidden;flex-shrink:0;display:inline-flex;"><?= product_illu($p['illu'], $p['palette']) ?></span>
                      <span>
                        <b style="font-family:Fraunces,serif;"><?= htmlspecialchars($p['name']) ?></b>
                        <?php if ($p['tag']): ?><span class="pill" style="font-size:10px;margin-left:6px;"><?= htmlspecialchars($p['tag']) ?></span><?php endif; ?>
                        <div class="muted" style="font-size:11.5px;"><?= $p['id'] ?></div>
                      </span>
                    </div>
                  </td>
                  <td><span class="pill" style="background:var(--bg-2);"><?= $cat ? htmlspecialchars($cat['label']) : htmlspecialchars($p['cat']) ?></span></td>
                  <td><b style="color:var(--wine);">$<?= number_format($p['price'], 2) ?></b></td>
                  <td><?= $p['quantity'] ?></td>
                  <td><span class="pill <?= $spl ?>"><?= $pst_label ?></span></td>
                  <td style="text-align:right;">
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                      <a href="/limedime/product.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                      <button class="btn btn-ghost btn-sm" onclick="openEditProduct(<?= htmlspecialchars(json_encode([
                          'id'            => $p['db_id'],
                          'name'          => $p['name'],
                          'price'         => $p['price'],
                          'qty'           => $p['quantity'],
                          'cat'           => $p['cat'],
                          'status'        => $pst,
                          'desc'          => $p['description'],
                          'product_image' => $p['product_image'],
                      ]), ENT_QUOTES) ?>)">Edit</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($shop_products)): ?>
                <tr>
                  <td colspan="6" style="text-align:center;color:var(--ink-3);padding:32px 0;">
                    No products yet.
                    <button class="btn btn-primary btn-sm" style="margin-left:10px;" onclick="openModal('add-product-modal')"><?= icon_plus(14) ?> Add first product</button>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($tab === 'orders'): ?>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px;">
          <div>
            <h1 class="h1" style="margin:0;">Orders</h1>
            <p class="muted" style="margin-top:6px;"><?= count($shop_orders) ?> orders total</p>
          </div>
        </div>

        <?php if (($_GET['ok'] ?? '') === 'cancel_approved'): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:18px;">Order cancelled and customer notified.</div>
        <?php elseif (($_GET['ok'] ?? '') === 'cancel_declined'): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:18px;">Cancellation request declined — order restored to active.</div>
        <?php endif; ?>

        <?php if (!empty($cancel_requests)): ?>
          <div style="margin-bottom:28px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
              <h2 class="h2" style="margin:0;font-size:20px;">Cancel requests</h2>
              <span style="background:var(--wine);color:var(--surface);font-size:12px;font-weight:700;border-radius:999px;padding:2px 9px;"><?= count($cancel_requests) ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <?php foreach ($cancel_requests as $cr): ?>
                <div class="card" style="padding:20px;border-color:var(--wine);border-width:1.5px;">
                  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                    <div>
                      <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                        <b style="font-family:Fraunces,serif;font-size:17px;"><?= $cr['id'] ?></b>
                        <span class="pill danger" style="font-size:11px;">Cancel requested</span>
                      </div>
                      <div class="muted" style="font-size:13px;"><?= htmlspecialchars($cr['customer']) ?> · <?= $cr['date'] ?> · <?= $cr['items'] ?> item<?= $cr['items'] !== 1 ? 's' : '' ?> · <b style="color:var(--wine);">$<?= number_format($cr['total'], 2) ?></b></div>
                      <?php if ($cr['note']): ?>
                        <div style="margin-top:8px;padding:10px 14px;background:var(--surface-2);border-radius:var(--r);border:1px dashed var(--line);font-size:13px;color:var(--ink-2);">
                          <b style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-3);">Reason:</b> <?= htmlspecialchars($cr['note']) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                      <form method="post">
                        <input type="hidden" name="action" value="decline_cancel">
                        <input type="hidden" name="order_id" value="<?= $cr['db_id'] ?>">
                        <button class="btn btn-ghost btn-sm">Decline</button>
                      </form>
                      <form method="post">
                        <input type="hidden" name="action" value="approve_cancel">
                        <input type="hidden" name="order_id" value="<?= $cr['db_id'] ?>">
                        <button class="btn btn-primary btn-sm" style="background:var(--wine);border-color:var(--wine);">Approve cancellation</button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <?php
        $p_cnt = 0;
        $d_cnt = 0;
        foreach ($shop_orders as $o) {
          $st = strtolower(str_replace(' ', '_', $o['status']));
          if ($st === 'pending') $p_cnt++;
          if ($st === 'delivered') $d_cnt++;
        }
        $t_rev = array_sum(array_column($shop_orders, 'total'));
        ?>
        <div class="stat-grid" style="margin-bottom:22px;">
          <div class="stat">
            <div class="eyebrow">Pending</div>
            <div class="stat-val"><?= $p_cnt ?></div><?php if ($p_cnt): ?><div class="stat-delta down"><?= icon_trend(12) ?> Action needed</div><?php endif; ?>
          </div>
          <div class="stat">
            <div class="eyebrow">Delivered</div>
            <div class="stat-val"><?= $d_cnt ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Revenue</div>
            <div class="stat-val">$<?= number_format($t_rev, 2) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Total orders</div>
            <div class="stat-val"><?= count($shop_orders) ?></div>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shop_orders as $o):
                $st  = strtolower(str_replace(' ', '_', $o['status']));
                $spl = ['pending' => 'warn', 'accepted' => 'info', 'packed' => 'info', 'out_for_delivery' => 'warn', 'delivered' => 'success', 'cancelled' => 'danger'][$st] ?? '';
              ?>
                <tr>
                  <td><b style="font-family:Fraunces,serif;"><?= $o['id'] ?></b></td>
                  <td class="muted"><?= htmlspecialchars($o['customer']) ?></td>
                  <td class="muted"><?= $o['date'] ?></td>
                  <td><?= $o['items'] ?></td>
                  <td><b style="color:var(--wine);">$<?= number_format($o['total'], 2) ?></b></td>
                  <td><span class="pill <?= $spl ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($shop_orders)): ?><tr>
                  <td colspan="6" style="text-align:center;color:var(--ink-3);padding:28px 0;">No orders yet.</td>
                </tr><?php endif; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($tab === 'deliveries'): ?>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px;">
          <div>
            <h1 class="h1" style="margin:0;">Deliveries</h1>
            <p class="muted" style="margin-top:6px;">Live delivery status for your shop's orders</p>
          </div>
        </div>
        <div class="stat-grid" style="margin-bottom:22px;">
          <div class="stat">
            <div class="eyebrow">Out for delivery</div>
            <div class="stat-val"><?= $active_del_cnt ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Delivered</div>
            <?php
            $del_done = 0;
            foreach ($shop_deliveries as $d) {
              if ($d['status'] === 'delivered') $del_done++;
            }
            ?>
            <div class="stat-val"><?= $del_done ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Total</div>
            <div class="stat-val"><?= count($shop_deliveries) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Pending</div>
            <div class="stat-val"><?= $pending_orders ?></div>
          </div>
        </div>
        <?php if (empty($shop_deliveries)): ?>
          <div class="card" style="padding:40px;text-align:center;color:var(--ink-3);">
            <b>No deliveries yet.</b>
            <p class="muted" style="margin:4px 0 0;font-size:13px;">Orders will appear here once a courier picks them up.</p>
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($shop_deliveries as $d):
                  $spl    = $d['status'] === 'delivered' ? 'success' : 'warn';
                  $slabel = $d['status'] === 'delivered' ? 'Delivered' : 'Out for delivery';
                ?>
                  <tr>
                    <td><b style="font-family:Fraunces,serif;"><?= $d['id'] ?></b></td>
                    <td><?= htmlspecialchars($d['customer']) ?></td>
                    <td class="muted"><?= $d['date'] ?></td>
                    <td><?= $d['items'] ?></td>
                    <td><b style="color:var(--wine);">$<?= number_format($d['total'], 2) ?></b></td>
                    <td><span class="pill <?= $spl ?>"><?= $slabel ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'settings'): ?>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px;">
          <div>
            <h1 class="h1" style="margin:0;">Shop settings</h1>
            <p class="muted" style="margin-top:6px;">Edit your public shop profile</p>
          </div>
          <a href="/limedime/seller.php?id=<?= $shop['id'] ?>" class="btn btn-ghost btn-sm"><?= icon_store(14) ?> View shop page</a>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
          <div class="card" style="padding:24px;grid-column:1/-1;">
            <h3 class="h3" style="margin-top:0;">Profile</h3>
            <form method="post">
              <input type="hidden" name="action" value="update_shop">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="field" style="grid-column:1/-1;">
                  <label>Shop name</label>
                  <input name="shop_name" value="<?= htmlspecialchars($shop['name']) ?>" required>
                </div>
                <div class="field">
                  <label>Category</label>
                  <select name="type" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
                    <?php foreach (['food', 'clothing', 'electronics', 'education', 'beauty'] as $c): ?>
                      <option value="<?= $c ?>" <?= $shop['cat'] === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label>Location</label>
                  <input name="location" value="<?= htmlspecialchars($shop_row['location'] ?? '') ?>" placeholder="Portland, OR">
                </div>
                <div class="field">
                  <label>Avatar (emoji)</label>
                  <input name="avatar" value="<?= htmlspecialchars($shop['avatar']) ?>" placeholder="🌿" maxlength="4" style="font-size:20px;">
                </div>
                <div class="field">
                  <label>Accent colour</label>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <input type="color" name="accent_color" value="<?= htmlspecialchars($shop['accent']) ?>"
                      style="width:44px;height:40px;padding:2px;border:1px solid var(--line);border-radius:var(--r);cursor:pointer;background:var(--surface);">
                    <input id="accent-hex" type="text" value="<?= htmlspecialchars($shop['accent']) ?>"
                      style="flex:1;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);font-size:14px;"
                      readonly>
                  </div>
                </div>
                <div class="field">
                  <label>Cover pattern</label>
                  <select name="pattern" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
                    <?php foreach (['dots' => 'Dots', 'wave' => 'Wave', 'grid' => 'Grid', 'rays' => 'Rays'] as $pv => $pl): ?>
                      <option value="<?= $pv ?>" <?= ($shop['pattern'] === $pv) ? 'selected' : '' ?>><?= $pl ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div style="display:flex;justify-content:flex-end;margin-top:18px;">
                <button type="submit" class="btn btn-primary">Save changes</button>
              </div>
            </form>
          </div>

          <div class="card" style="padding:24px;">
            <h3 class="h3" style="margin-top:0;">Cover photo</h3>
            <p class="muted" style="font-size:13px;margin-bottom:16px;">Shown at the top of your public shop page. JPG, PNG or WebP, max 5 MB.</p>
            <?php if (!empty($shop['cover_image'])): ?>
              <div style="height:120px;border-radius:var(--r);overflow:hidden;margin-bottom:14px;background:url('/limedime/assets/img/shops/<?= htmlspecialchars($shop['cover_image']) ?>') center/cover no-repeat;"></div>
            <?php else: ?>
              <div style="height:120px;border-radius:var(--r);overflow:hidden;margin-bottom:14px;border:1px dashed var(--line);">
                <?= seller_cover($shop['accent'], $shop['pattern'], '#E1DD8F') ?>
              </div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="upload_cover">
              <label style="display:flex;align-items:center;gap:10px;padding:12px 16px;border:1.5px dashed var(--line);border-radius:var(--r);cursor:pointer;background:var(--surface-2);transition:border-color .15s;" id="cover-drop-zone">
                <span style="width:34px;height:34px;border-radius:8px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?= icon_plus(16) ?></span>
                <div style="flex:1;">
                  <div style="font-weight:600;font-size:14px;">Upload new cover</div>
                  <div class="muted" style="font-size:12px;" id="cover-fname">JPG / PNG / WebP · max 5 MB</div>
                </div>
                <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp" style="display:none;"
                  onchange="document.getElementById('cover-fname').textContent=this.files[0]?this.files[0].name:'JPG / PNG / WebP · max 5 MB';document.getElementById('cover-drop-zone').style.borderColor=this.files[0]?'var(--teal)':'var(--line)';">
              </label>
              <div style="display:flex;justify-content:flex-end;margin-top:12px;">
                <button type="submit" class="btn btn-ghost btn-sm">Upload</button>
              </div>
            </form>
          </div>

          <div class="card" style="padding:24px;">
            <h3 class="h3" style="margin-top:0;">Preview</h3>
            <p class="muted" style="font-size:13px;margin-bottom:14px;">How your shop card appears to buyers.</p>
            <article class="seller-card" style="pointer-events:none;margin:0;">
              <div class="seller-cover" style="<?= !empty($shop['cover_image']) ? 'background:#111;' : '' ?>">
                <?php if (!empty($shop['cover_image'])): ?>
                  <img src="/limedime/assets/img/shops/<?= htmlspecialchars($shop['cover_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                <?php else: ?>
                  <?= seller_cover($shop['accent'], $shop['pattern']) ?>
                <?php endif; ?>
              </div>
              <div class="seller-body">
                <div class="seller-avatar" style="background:<?= $shop['accent'] ?>;color:var(--lime);"><?= $shop['avatar'] ?></div>
                <div class="seller-name"><?= htmlspecialchars($shop['name']) ?></div>
                <div class="seller-sub">Featured · <?= ucfirst($shop['cat']) ?></div>
                <div class="seller-stats">
                  <span><b><?= $shop['products'] ?></b> products</span>
                  <span><b><?= $shop['followers'] ?></b> followers</span>
                  <span style="display:inline-flex;gap:4px;align-items:center;"><?= icon_star(11) ?> <b><?= $shop['rating'] ?></b></span>
                </div>
              </div>
            </article>
          </div>
        </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<div id="add-product-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:var(--surface);border-radius:var(--r-lg);padding:28px;width:480px;max-width:calc(100vw - 40px);box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto;">
    <h3 class="h3" style="margin:0 0 20px;">Add product</h3>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_product">
      <div class="field" style="margin-bottom:12px;">
        <label>Product name *</label>
        <input name="name" placeholder="e.g. Honey Lavender Jam" required>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
        <div class="field"><label>Price ($) *</label><input name="price" type="number" step="0.01" min="0" placeholder="0.00" required></div>
        <div class="field"><label>Quantity</label><input name="qty" type="number" min="0" value="1"></div>
        <div class="field">
          <label>Category</label>
          <select name="cat" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
            <?php foreach (['food', 'clothing', 'electronics', 'education', 'beauty'] as $c): ?>
              <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Status</label>
          <select name="pstatus" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
            <option value="live">Available</option>
            <option value="draft">Not available</option>
          </select>
        </div>
      </div>
      <div class="field" style="margin-bottom:12px;">
        <label>Product photo <span class="muted" style="font-weight:400;">(optional · JPG/PNG/WebP · max 5 MB)</span></label>
        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px dashed var(--line);border-radius:var(--r);cursor:pointer;background:var(--surface-2);">
          <span style="font-size:13px;color:var(--ink-3);" id="ap-photo-name">Choose file…</span>
          <input type="file" name="product_image" accept=".jpg,.jpeg,.png,.webp" style="display:none;"
            onchange="document.getElementById('ap-photo-name').textContent=this.files[0]?this.files[0].name:'Choose file…';">
        </label>
      </div>
      <div class="field" style="margin-bottom:4px;">
        <label>Description</label>
        <textarea name="desc" rows="3" placeholder="Describe your product…" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);font-family:inherit;font-size:14px;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
        <button type="button" class="btn btn-ghost" onclick="closeModal('add-product-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon_plus(14) ?> Add product</button>
      </div>
    </form>
  </div>
</div>

<div id="edit-product-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:var(--surface);border-radius:var(--r-lg);padding:28px;width:480px;max-width:calc(100vw - 40px);box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto;">
    <h3 class="h3" style="margin:0 0 20px;">Edit product</h3>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit_product">
      <input type="hidden" name="product_id" id="ep-id">
      <div class="field" style="margin-bottom:12px;"><label>Product name *</label><input name="name" id="ep-name" required></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
        <div class="field"><label>Price ($) *</label><input name="price" id="ep-price" type="number" step="0.01" min="0" required></div>
        <div class="field"><label>Quantity</label><input name="qty" id="ep-qty" type="number" min="0"></div>
        <div class="field">
          <label>Category</label>
          <select name="cat" id="ep-cat" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
            <?php foreach (['food', 'clothing', 'electronics', 'education', 'beauty'] as $c): ?>
              <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Status</label>
          <select name="pstatus" id="ep-status" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
            <option value="live">Available</option>
            <option value="draft">Not available</option>
            <option value="archived">Archived</option>
          </select>
        </div>
      </div>
      <div class="field" style="margin-bottom:12px;">
        <label>Product photo <span class="muted" style="font-weight:400;">(optional · JPG/PNG/WebP · max 5 MB)</span></label>
        <div id="ep-current-thumb" style="display:none;margin-bottom:8px;display:flex;align-items:center;gap:10px;">
          <img id="ep-thumb-img" src="" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:1px solid var(--line);">
          <span class="muted" style="font-size:12px;">Current photo — upload new to replace</span>
        </div>
        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px dashed var(--line);border-radius:var(--r);cursor:pointer;background:var(--surface-2);">
          <span style="font-size:13px;color:var(--ink-3);" id="ep-photo-name">Choose file…</span>
          <input type="file" name="product_image" accept=".jpg,.jpeg,.png,.webp" style="display:none;"
            onchange="document.getElementById('ep-photo-name').textContent=this.files[0]?this.files[0].name:'Choose file…';">
        </label>
      </div>
      <div class="field" style="margin-bottom:4px;"><label>Description</label>
        <textarea name="desc" id="ep-desc" rows="3" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);font-family:inherit;font-size:14px;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
        <button type="button" class="btn btn-ghost" onclick="closeModal('edit-product-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<script>
  var revCharts = <?= json_encode($rev_charts, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  var revTotals = <?= json_encode($rev_totals) ?>;
  var revLabels = { '7d': '7 days', '30d': '30 days', '90d': '90 days' };
  var revStartLabels = { '7d': '7 days ago', '30d': '30 days ago', '90d': '90 days ago' };

  function switchRevPeriod(period) {
    document.getElementById('rev-chart').innerHTML = revCharts[period];
    document.getElementById('rev-total-display').textContent = '$' + parseFloat(revTotals[period] || 0).toFixed(2);
    document.getElementById('rev-period-label').textContent = 'Revenue, last ' + revLabels[period];
    document.getElementById('rev-start-label').textContent = revStartLabels[period];
    document.querySelectorAll('.rev-tab').forEach(function(btn) {
      btn.classList.toggle('active', btn.dataset.period === period);
    });
  }

  function openModal(id) {
    document.getElementById(id).style.display = 'flex';
  }

  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
  }

  function openEditProduct(data) {
    document.getElementById('ep-id').value = data.id;
    document.getElementById('ep-name').value = data.name;
    document.getElementById('ep-price').value = data.price;
    document.getElementById('ep-qty').value = data.qty;
    document.getElementById('ep-cat').value = data.cat;
    document.getElementById('ep-status').value = data.status;
    document.getElementById('ep-desc').value = data.desc || '';
    document.getElementById('ep-photo-name').textContent = 'Choose file…';
    var thumb = document.getElementById('ep-current-thumb');
    var img   = document.getElementById('ep-thumb-img');
    if (data.product_image) {
      img.src = '/limedime/assets/img/products/' + data.product_image;
      thumb.style.display = 'flex';
    } else {
      thumb.style.display = 'none';
    }
    openModal('edit-product-modal');
  }

  document.addEventListener('input', function(e) {
    if (e.target.name === 'accent_color') {
      var hex = document.getElementById('accent-hex');
      if (hex) hex.value = e.target.value;
    }
  });

  document.addEventListener('click', function(e) {
    ['add-product-modal', 'edit-product-modal'].forEach(function(id) {
      var m = document.getElementById(id);
      if (m && e.target === m) m.style.display = 'none';
    });
  });
</script>

<?php include '_partials/footer.php'; ?>
