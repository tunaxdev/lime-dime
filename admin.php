<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

$tab = $_GET['tab'] ?? 'dashboard';
$allowed = ['dashboard', 'orders', 'users', 'sellers', 'products', 'couriers', 'reports', 'settings', 'messages'];
if (!in_array($tab, $allowed)) $tab = 'dashboard';

require_login('/limedime/login.php');
require_role('admin');


if (isset($_GET['action']) && $_GET['action'] === 'download') {
  $report   = preg_replace('/[^a-z_]/', '', $_GET['report'] ?? '');
  $filename = 'limedime_' . $report . '_' . date('Y-m-d') . '.csv';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  $out = fopen('php://output', 'w');
  switch ($report) {
    case 'monthly_gmv':
      fputcsv($out, ['Month', 'Orders', 'GMV ($)']);
      $rq = mysqli_query($conn, 'SELECT DATE_FORMAT(order_date,"%Y-%m") AS m, COUNT(*) AS c, ROUND(SUM(total),2) AS gmv FROM orders GROUP BY m ORDER BY m DESC');
      if ($rq) { while ($r = mysqli_fetch_assoc($rq)) fputcsv($out, [$r['m'], $r['c'], $r['gmv']]); }
      break;
    case 'seller_payouts':
      fputcsv($out, ['Seller', 'Orders', 'Payout ($)']);
      $rq = mysqli_query($conn, 'SELECT s.name, COUNT(DISTINCT o.id) AS cnt, ROUND(SUM(ol.total_price),2) AS total FROM order_line ol JOIN orders o ON o.id=ol.order_id JOIN products p ON p.id=ol.product_id JOIN shops s ON s.id=p.shop_id GROUP BY s.id ORDER BY total DESC');
      if ($rq) { while ($r = mysqli_fetch_assoc($rq)) fputcsv($out, [$r['name'], $r['cnt'], $r['total']]); }
      break;
    case 'courier_earnings':
      fputcsv($out, ['Courier', 'Deliveries', 'Earnings ($)']);
      $rq = mysqli_query($conn, 'SELECT u.name, COUNT(d.id) AS cnt, ROUND(SUM(d.payout),2) AS total FROM deliveries d JOIN users u ON u.id=d.courier_id WHERE d.status="delivered" GROUP BY u.id ORDER BY total DESC');
      if ($rq) { while ($r = mysqli_fetch_assoc($rq)) fputcsv($out, [$r['name'], $r['cnt'], $r['total']]); }
      break;
    case 'tax_summary':
      fputcsv($out, ['Month', 'Tax ($)']);
      $rq = mysqli_query($conn, 'SELECT DATE_FORMAT(order_date,"%Y-%m") AS m, ROUND(SUM(tax),2) AS tax FROM orders GROUP BY m ORDER BY m DESC');
      if ($rq) { while ($r = mysqli_fetch_assoc($rq)) fputcsv($out, [$r['m'], $r['tax']]); }
      break;
    case 'refunds':
      fputcsv($out, ['Order ID', 'Customer', 'Amount ($)', 'Date']);
      $rq = mysqli_query($conn, 'SELECT o.id, u.name, o.total, o.order_date FROM orders o JOIN users u ON u.id=o.customer_id WHERE o.status="cancelled" ORDER BY o.order_date DESC');
      if ($rq) { while ($r = mysqli_fetch_assoc($rq)) fputcsv($out, ['LD-' . str_pad($r['id'], 4, '0', STR_PAD_LEFT), $r['name'], $r['total'], $r['order_date']]); }
      break;
    case 'audit_log':
      fclose($out);
      header('Content-Type: application/json');
      header('Content-Disposition: attachment; filename="limedime_audit_' . date('Y-m-d') . '.json"');
      echo json_encode(['generated_at' => date('c'), 'note' => 'Audit log not yet implemented.'], JSON_PRETTY_PRINT);
      exit;
    default:
      fputcsv($out, ['No data available.']);
  }
  fclose($out);
  exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act      = $_POST['action']   ?? '';
  $from_tab = $_POST['from_tab'] ?? $tab;
  $redir    = '/limedime/admin.php?tab=' . $from_tab . '&ok=1';

  if ($act === 'suspend_user') {
    $uid2 = (int)$_POST['user_id'];
    mysqli_query($conn, "UPDATE users SET status='suspended' WHERE id=$uid2 AND role!='admin'");
  } elseif ($act === 'activate_user') {
    $uid2 = (int)$_POST['user_id'];
    mysqli_query($conn, "UPDATE users SET status='active' WHERE id=$uid2");
  } elseif ($act === 'delete_user') {
    $uid2 = (int)$_POST['user_id'];
    mysqli_query($conn, "DELETE FROM users WHERE id=$uid2 AND role!='admin'");
    $redir = '/limedime/admin.php?tab=' . $from_tab;
  } elseif ($act === 'approve_user') {
    $uid2 = (int)$_POST['user_id'];
    mysqli_query($conn, "UPDATE users SET status='active' WHERE id=$uid2");
    $sr = mysqli_query($conn, "SELECT id FROM shops WHERE manager_id=$uid2 LIMIT 1");
    if ($sr && ($srow = mysqli_fetch_assoc($sr))) {
      $sid = (int)$srow['id'];
      mysqli_query($conn, "UPDATE shops SET status='active' WHERE id=$sid");
    }
    $redir = '/limedime/admin.php?tab=users&role=Pending&ok=approved';
  } elseif ($act === 'reject_user') {
    $uid2 = (int)$_POST['user_id'];
    mysqli_query($conn, "UPDATE users SET status='suspended' WHERE id=$uid2");
    $redir = '/limedime/admin.php?tab=users&role=Pending&ok=rejected';
  } elseif ($act === 'suspend_shop') {
    $shop_id = (int)$_POST['shop_id'];
    mysqli_query($conn, "UPDATE shops SET status='suspended' WHERE id=$shop_id");
  } elseif ($act === 'activate_shop') {
    $shop_id = (int)$_POST['shop_id'];
    mysqli_query($conn, "UPDATE shops SET status='active' WHERE id=$shop_id");
  } elseif ($act === 'save_commission') {
    $std   = (float)($_POST['commission_standard']       ?? 8.5);
    $new   = (float)($_POST['commission_new_seller']     ?? 5.0);
    $boost = (float)($_POST['commission_featured_boost'] ?? 24.0);
    mysqli_query($conn, "INSERT INTO platform_settings (`key`,`value`) VALUES ('commission_standard','$std') ON DUPLICATE KEY UPDATE `value`='$std'");
    mysqli_query($conn, "INSERT INTO platform_settings (`key`,`value`) VALUES ('commission_new_seller','$new') ON DUPLICATE KEY UPDATE `value`='$new'");
    mysqli_query($conn, "INSERT INTO platform_settings (`key`,`value`) VALUES ('commission_featured_boost','$boost') ON DUPLICATE KEY UPDATE `value`='$boost'");
    $redir = '/limedime/admin.php?tab=settings&ok=commission';
  } elseif ($act === 'save_regions') {
    $all_regions = ['Portland', 'Brooklyn', 'Seattle', 'Austin', 'Chicago', 'Denver', 'Boston', 'Oakland'];
    $selected = [];
    foreach ($all_regions as $r) {
      if (isset($_POST['region_' . strtolower($r)])) $selected[] = $r;
    }
    $sv = mysqli_real_escape_string($conn, implode(',', $selected));
    mysqli_query($conn, "INSERT INTO platform_settings (`key`,`value`) VALUES ('regions','$sv') ON DUPLICATE KEY UPDATE `value`='$sv'");
    $redir = '/limedime/admin.php?tab=settings&ok=regions';
  } elseif ($act === 'mark_message') {
    $mid = (int)($_POST['message_id'] ?? 0);
    $st  = in_array($_POST['status'] ?? '', ['unread', 'read', 'archived']) ? $_POST['status'] : 'read';
    if ($mid) {
      $ss = mysqli_real_escape_string($conn, $st);
      mysqli_query($conn, "UPDATE contact_messages SET status='$ss' WHERE id=$mid");
    }
    $redir = '/limedime/admin.php?tab=messages&ok=1';
  } elseif ($act === 'delete_message') {
    $mid = (int)($_POST['message_id'] ?? 0);
    if ($mid) mysqli_query($conn, "DELETE FROM contact_messages WHERE id=$mid");
    $redir = '/limedime/admin.php?tab=messages';
  } elseif ($act === 'edit_product') {
    $pid   = (int)$_POST['product_id'];
    $name  = trim($_POST['name']        ?? '');
    $price = (float)($_POST['price']    ?? 0);
    $qty   = (int)($_POST['quantity']   ?? 0);
    $cat   = trim($_POST['category']    ?? 'food');
    $pstat = in_array($_POST['pstatus'] ?? '', ['live', 'draft', 'archived']) ? $_POST['pstatus'] : 'live';
    $desc  = trim($_POST['description'] ?? '');
    if ($name && $price >= 0) {
      $sn = mysqli_real_escape_string($conn, $name);
      $sc = mysqli_real_escape_string($conn, $cat);
      $sp = mysqli_real_escape_string($conn, $pstat);
      $sd = mysqli_real_escape_string($conn, $desc);
      mysqli_query($conn, "UPDATE products SET name='$sn', price=$price, quantity=$qty, category='$sc', status='$sp', description='$sd' WHERE id=$pid");
    }
    $redir = '/limedime/admin.php?tab=products&ok=1';
  }
  header('Location: ' . $redir);
  exit;
}


$pill_map = [
  'pending' => 'warn',
  'accepted' => 'info',
  'packed' => 'info',
  'out_for_delivery' => 'warn',
  'delivered' => 'success',
  'cancelled' => 'danger'
];

$admin_orders = [];
if (in_array($tab, ['dashboard', 'orders'])) {
  $aq = mysqli_query($conn, 'SELECT o.id, o.order_date, o.status, o.total, u.name AS customer_name, COUNT(ol.id) AS items, MAX(s.name) AS seller_name FROM orders o JOIN users u ON u.id=o.customer_id LEFT JOIN order_line ol ON ol.order_id=o.id LEFT JOIN products p ON p.id=ol.product_id LEFT JOIN shops s ON s.id=p.shop_id GROUP BY o.id, o.order_date, o.status, o.total, u.name ORDER BY o.order_date DESC LIMIT 50');
  if ($aq) { while ($r = mysqli_fetch_assoc($aq)) $admin_orders[] = $r; }
}

$admin_couriers = [];
$courier_docs   = [];
if ($tab === 'couriers') {
  $cq = mysqli_query($conn, 'SELECT u.*, (SELECT COUNT(*) FROM deliveries d WHERE d.courier_id=u.id AND d.status="delivered" AND DATE(d.delivered_at)=CURDATE()) AS today_deliveries FROM users u WHERE u.role="courier" ORDER BY u.name');
  if ($cq) { while ($r = mysqli_fetch_assoc($cq)) $admin_couriers[] = $r; }
  foreach ($admin_couriers as $cr) {
    $cid = (int)$cr['id'];
    $dq = mysqli_query($conn, "SELECT * FROM documents WHERE user_id=$cid ORDER BY uploaded_at ASC");
    $courier_docs[$cid] = [];
    if ($dq) { while ($dr = mysqli_fetch_assoc($dq)) $courier_docs[$cid][] = $dr; }
  }
}

$admin_shops = [];
if ($tab === 'sellers') {
  $sq = mysqli_query($conn, 'SELECT s.*, (SELECT COUNT(*) FROM products p WHERE p.shop_id=s.id AND p.status="live") AS products_count FROM shops s ORDER BY s.id');
  if ($sq) { while ($r = mysqli_fetch_assoc($sq)) $admin_shops[] = $r; }
}

$role_filter   = $_GET['role'] ?? 'All';
$pending_users = [];
$user_docs     = [];
if ($tab === 'users' && $role_filter === 'Pending') {
  $puq = mysqli_query($conn, 'SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.customer_id=u.id) AS orders_count FROM users u WHERE u.status="pending" ORDER BY u.created_at DESC');
  if ($puq) { while ($r = mysqli_fetch_assoc($puq)) $pending_users[] = $r; }
  foreach ($pending_users as $pu) {
    $puid = (int)$pu['id'];
    $dq = mysqli_query($conn, "SELECT * FROM documents WHERE user_id=$puid ORDER BY uploaded_at ASC");
    $user_docs[$puid] = [];
    if ($dq) { while ($dr = mysqli_fetch_assoc($dq)) $user_docs[$puid][] = $dr; }
  }
}

$pending_count = 0;
if ($tab === 'users') {
  $pcq = mysqli_query($conn, 'SELECT COUNT(*) AS cnt FROM users WHERE status="pending"');
  if ($pcq && ($pcr = mysqli_fetch_assoc($pcq))) $pending_count = (int)$pcr['cnt'];
}


$contact_messages = [];
$unread_msg_count = 0;
$umq = mysqli_query($conn, 'SELECT COUNT(*) AS cnt FROM contact_messages WHERE status="unread"');
if ($umq && ($umr = mysqli_fetch_assoc($umq))) $unread_msg_count = (int)$umr['cnt'];
if ($tab === 'messages') {
  $mf = $_GET['mf'] ?? 'all';
  if ($mf === 'unread') $where = 'WHERE status="unread"';
  elseif ($mf === 'archived') $where = 'WHERE status="archived"';
  else $where = '';
  $cmq = mysqli_query($conn, 'SELECT * FROM contact_messages ' . $where . ' ORDER BY created_at DESC');
  if ($cmq) { while ($r = mysqli_fetch_assoc($cmq)) $contact_messages[] = $r; }
}


$platform_settings = [];
if ($tab === 'settings') {
  $psq = mysqli_query($conn, 'SELECT `key`,`value` FROM platform_settings');
  if ($psq) { while ($r = mysqli_fetch_assoc($psq)) $platform_settings[$r['key']] = $r['value']; }
}


$page_title   = 'Admin — Lime&Dime';
$active_route = 'admin';
$show_catbar  = false;
include '_partials/header.php';

$sidebar_items = [
  ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => icon_home(18)],
  ['id' => 'orders',   'label' => 'Orders',    'icon' => icon_package(18), 'count' => (string)count($admin_orders)],
  ['id' => 'users',    'label' => 'Users',     'icon' => icon_users(18)],
  ['id' => 'sellers',  'label' => 'Sellers',   'icon' => icon_store(18)],
  ['id' => 'products', 'label' => 'Products',  'icon' => icon_bag(18)],
  ['id' => 'couriers', 'label' => 'Couriers',  'icon' => icon_bike(18)],
  ['id' => 'reports',  'label' => 'Reports',   'icon' => icon_trend(18)],
  ['id' => 'settings', 'label' => 'Settings',  'icon' => icon_settings(18)],
  ['id' => 'messages', 'label' => 'Messages',  'icon' => icon_mail(18), 'count' => $unread_msg_count > 0 ? (string)$unread_msg_count : ''],
];
?>

<div class="container">
  <div class="sidebar-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <img src="/limedime/assets/img/logo.png" width="32" height="32" alt="" style="object-fit:contain;">
        <div style="line-height:1.1;"><b>Operations</b>
          <div class="role">Admin · Lime&amp;Dime HQ</div>
        </div>
      </div>
      <nav>
        <?php foreach ($sidebar_items as $item): ?>
          <a href="/limedime/admin.php?tab=<?= $item['id'] ?>" class="side-link <?= $tab === $item['id'] ? 'active' : '' ?>">
            <?= $item['icon'] ?> <span><?= $item['label'] ?></span>
            <?php if (isset($item['count'])): ?><span class="count"><?= $item['count'] ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div style="margin-top:18px;padding-top:14px;border-top:1px dashed var(--line);">
        <a href="/limedime/index.php" class="side-link"><?= icon_logout(18) ?> <span>Back to shop</span></a>
      </div>
    </aside>

    <main>
      <?php
      $ok = $_GET['ok'] ?? '';
      if ($ok === 'approved'): ?>
        <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Account approved — user can now sign in.</div>
      <?php elseif ($ok === 'rejected'): ?>
        <div style="background:rgba(142,59,70,.1);border:1px solid var(--wine);border-radius:var(--r);padding:12px 16px;color:var(--wine);font-size:13.5px;margin-bottom:16px;">Application rejected.</div>
      <?php elseif ($ok): ?>
        <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Changes saved.</div>
      <?php endif; ?>

      <?php if ($tab === 'dashboard'): ?>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px;">
          <div>
            <div class="eyebrow"><?= date('l, M j · H:i') ?></div>
            <h1 class="h1" style="margin:4px 0 0;">Operations dashboard</h1>
          </div>
          <a href="?action=download&report=monthly_gmv" class="btn btn-ghost btn-sm">Export CSV</a>
        </div>
        <div class="stat-grid">
          <?php
          $today_cnt = 0;
          $today_str = date('Y-m-d');
          foreach ($admin_orders as $r) {
            if (date('Y-m-d', strtotime($r['order_date'])) === $today_str) $today_cnt++;
          }
          $total_rev   = array_sum(array_column($admin_orders, 'total'));
          $paq = mysqli_query($conn, 'SELECT COUNT(*) AS cnt FROM users WHERE status="pending"');
          $pending_apps = ($paq && ($par = mysqli_fetch_assoc($paq))) ? (int)$par['cnt'] : 0;
          ?>
          <div class="stat">
            <div class="eyebrow">Orders today</div>
            <div class="stat-val"><?= $today_cnt ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Total revenue</div>
            <div class="stat-val">$<?= number_format($total_rev, 0) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Active sellers</div>
            <div class="stat-val"><?= count($SELLERS) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Pending applications</div>
            <div class="stat-val"><?= $pending_apps ?></div><?php if ($pending_apps > 0): ?><div class="stat-delta down"><?= icon_trend(12) ?> <a href="/limedime/admin.php?tab=users&role=Pending" style="color:inherit;">Review now</a></div><?php endif; ?>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:18px;">
          <div class="table-wrap">
            <div class="table-head">
              <h3>Recent orders</h3><a href="/limedime/admin.php?tab=orders" style="color:var(--wine);font-weight:700;font-size:13px;">View all</a>
            </div>
            <table>
              <thead>
                <tr>
                  <th>Order</th>
                  <th>Customer</th>
                  <th>Seller</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($admin_orders, 0, 5) as $row):
                  $oid = 'LD-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                  $pl = $pill_map[$row['status']] ?? ''; ?>
                  <tr>
                    <td><b style="font-family:Fraunces,serif;"><?= $oid ?></b></td>
                    <td>
                      <div class="row-avatar"><span class="av wine"><?= strtoupper(mb_substr($row['customer_name'], 0, 1)) ?></span><?= htmlspecialchars($row['customer_name']) ?></div>
                    </td>
                    <td class="muted"><?= htmlspecialchars($row['seller_name'] ?? '—') ?></td>
                    <td><b style="color:var(--wine);">$<?= number_format($row['total'], 2) ?></b></td>
                    <td><span class="pill <?= $pl ?>"><?= ucwords(str_replace('_', ' ', $row['status'])) ?></span></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($admin_orders)): ?><tr>
                    <td colspan="5" style="text-align:center;color:var(--ink-3);padding:28px 0;">No orders yet.</td>
                  </tr><?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="card" style="padding:22px;">
            <h3 class="h3" style="margin-top:0;">Quick stats</h3>
            <?php
            $pending_ord = 0;
            $delivered = 0;
            foreach ($admin_orders as $r) {
              if ($r['status'] === 'pending') $pending_ord++;
              if ($r['status'] === 'delivered') $delivered++;
            }
            foreach ([['Total revenue', '$' . number_format($total_rev, 2)], ['Pending orders', $pending_ord], ['Delivered orders', $delivered], ['Active shops', count($SELLERS)], ['Total users', count($ADMIN_USERS)]] as [$label, $val]):
            ?>
              <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px dashed var(--line);font-size:13.5px;">
                <span class="muted"><?= $label ?></span><b><?= $val ?></b>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      <?php elseif ($tab === 'orders'): ?>
        <h1 class="h1" style="margin:0 0 22px;">Orders</h1>
        <?php
        $today_cnt = 0;
        $pending_cnt = 0;
        $today_str = date('Y-m-d');
        foreach ($admin_orders as $r) {
          if (date('Y-m-d', strtotime($r['order_date'])) === $today_str) $today_cnt++;
          if ($r['status'] === 'pending') $pending_cnt++;
        }
        $total_rev   = array_sum(array_column($admin_orders, 'total'));
        $avg_order   = count($admin_orders) ? $total_rev / count($admin_orders) : 0;
        ?>
        <div class="stat-grid">
          <div class="stat">
            <div class="eyebrow">Today</div>
            <div class="stat-val"><?= $today_cnt ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Pending</div>
            <div class="stat-val"><?= $pending_cnt ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Total revenue</div>
            <div class="stat-val">$<?= number_format($total_rev, 2) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Avg. order</div>
            <div class="stat-val">$<?= number_format($avg_order, 2) ?></div>
          </div>
        </div>
        <div class="table-wrap">
          <div class="table-head">
            <h3>All orders</h3><a href="?action=download&report=monthly_gmv" class="btn btn-ghost btn-sm">Export CSV</a>
          </div>
          <table>
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Seller</th>
                <th>Items</th>
                <th>Total</th>
                <th>Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admin_orders as $row):
                $oid = 'LD-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                $pl = $pill_map[$row['status']] ?? ''; ?>
                <tr>
                  <td><b style="font-family:Fraunces,serif;"><?= $oid ?></b></td>
                  <td>
                    <div class="row-avatar"><span class="av wine"><?= strtoupper(mb_substr($row['customer_name'], 0, 1)) ?></span><?= htmlspecialchars($row['customer_name']) ?></div>
                  </td>
                  <td class="muted"><?= htmlspecialchars($row['seller_name'] ?? '—') ?></td>
                  <td><?= (int)$row['items'] ?></td>
                  <td><b style="color:var(--wine);">$<?= number_format($row['total'], 2) ?></b></td>
                  <td class="muted"><?= date('M j, H:i', strtotime($row['order_date'])) ?></td>
                  <td><span class="pill <?= $pl ?>"><?= ucwords(str_replace('_', ' ', $row['status'])) ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($admin_orders)): ?><tr>
                  <td colspan="7" style="text-align:center;color:var(--ink-3);padding:28px 0;">No orders yet.</td>
                </tr><?php endif; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($tab === 'users'): ?>
        <?php
        if (!in_array($role_filter, ['All', 'Customer', 'Seller', 'Courier', 'Pending'])) $role_filter = 'All';
        ?>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px;">
          <div>
            <h1 class="h1" style="margin:0;">Users</h1>
            <?php if ($role_filter === 'Pending'): ?>
              <p class="muted" style="margin-top:6px;"><?= count($pending_users) ?> pending applications</p>
            <?php else: ?>
              <?php
              if ($role_filter === 'All') {
                $filtered = $ADMIN_USERS;
              } else {
                $filtered = [];
                foreach ($ADMIN_USERS as $r) {
                  if ($r['role'] === $role_filter && $r['status'] !== 'pending') $filtered[] = $r;
                }
              }
              ?>
              <p class="muted" style="margin-top:6px;"><?= count($filtered) ?> accounts</p>
            <?php endif; ?>
          </div>
          <div class="tabs">
            <?php foreach (['All', 'Customer', 'Seller', 'Courier'] as $r): ?>
              <a href="/limedime/admin.php?tab=users&role=<?= $r ?>" class="tab <?= $role_filter === $r ? 'active' : '' ?>"><?= $r ?></a>
            <?php endforeach; ?>
            <a href="/limedime/admin.php?tab=users&role=Pending" class="tab <?= $role_filter === 'Pending' ? 'active' : '' ?>" style="position:relative;">
              Pending
              <?php if ($pending_count > 0): ?><span style="position:absolute;top:-4px;right:-4px;width:8px;height:8px;border-radius:999px;background:var(--coral);"></span><?php endif; ?>
            </a>
          </div>
        </div>

        <?php if ($role_filter === 'Pending'): ?>
          <?php if (empty($pending_users)): ?>
            <div class="card" style="padding:40px;text-align:center;color:var(--ink-3);">No pending applications.</div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:16px;">
              <?php foreach ($pending_users as $pu):
                $ini = '';
                foreach (array_slice(explode(' ', $pu['name']), 0, 2) as $w) $ini .= strtoupper($w[0]);
                $docs = $user_docs[$pu['id']] ?? [];
                $av   = $pu['role'] === 'seller' ? 'coral' : 'teal';
              ?>
                <div class="card" style="padding:22px;">
                  <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap;">
                    <div style="width:48px;height:48px;border-radius:12px;background:var(--<?= $av ?>);color:var(--lime);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;flex-shrink:0;"><?= $ini ?></div>
                    <div style="flex:1;min-width:200px;">
                      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                        <b style="font-size:16px;"><?= htmlspecialchars($pu['name']) ?></b>
                        <span class="pill <?= $pu['role'] === 'seller' ? 'warn' : 'info' ?>"><?= ucfirst($pu['role']) ?></span>
                        <span class="pill warn">Pending</span>
                      </div>
                      <div style="display:flex;gap:18px;flex-wrap:wrap;font-size:13px;color:var(--ink-3);">
                        <span><?= htmlspecialchars($pu['email']) ?></span>
                        <?php if ($pu['phone']): ?><span><?= htmlspecialchars($pu['phone']) ?></span><?php endif; ?>
                        <?php if ($pu['city']): ?><span><?= htmlspecialchars($pu['city']) ?></span><?php endif; ?>
                        <span>Applied <?= date('M j, Y', strtotime($pu['created_at'])) ?></span>
                      </div>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0;">
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="approve_user">
                        <input type="hidden" name="user_id" value="<?= $pu['id'] ?>">
                        <input type="hidden" name="from_tab" value="users">
                        <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                      </form>
                      <form method="post" style="display:inline;" onsubmit="return confirm('Reject this application?')">
                        <input type="hidden" name="action" value="reject_user">
                        <input type="hidden" name="user_id" value="<?= $pu['id'] ?>">
                        <input type="hidden" name="from_tab" value="users">
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--wine);">Reject</button>
                      </form>
                    </div>
                  </div>
                  <?php if (!empty($docs)): ?>
                    <div style="margin-top:16px;padding-top:16px;border-top:1px dashed var(--line);">
                      <div class="eyebrow" style="margin-bottom:10px;">Submitted documents</div>
                      <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        <?php foreach ($docs as $doc): ?>
                          <a href="/limedime/assets/docs/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank"
                            style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid var(--line);border-radius:var(--r);font-size:13px;text-decoration:none;color:var(--ink-2);background:var(--surface-2);">
                            <?= icon_package(14) ?>
                            <?= htmlspecialchars($doc['label']) ?>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php else: ?>
                    <div style="margin-top:12px;padding-top:12px;border-top:1px dashed var(--line);font-size:13px;color:var(--ink-4);">No documents uploaded.</div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <?php
          $filtered = [];
          foreach ($ADMIN_USERS as $r) {
            if ($r['status'] === 'pending') continue;
            if ($role_filter === 'All' || $r['role'] === $role_filter) $filtered[] = $r;
          }
          ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>User</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Joined</th>
                  <th>Orders</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($filtered as $row):
                  $ini = '';
                  foreach (array_slice(explode(' ', $row['name']), 0, 2) as $w) $ini .= strtoupper($w[0]);
                  $avc  = $row['role'] === 'Seller' ? 'coral' : ($row['role'] === 'Courier' ? 'teal' : 'wine');
                  $rpl  = $row['role'] === 'Seller' ? 'warn'  : ($row['role'] === 'Courier' ? 'info'  : '');
                  $spl  = $row['status'] === 'active' ? 'success' : ($row['status'] === 'suspended' ? 'danger' : 'warn');
                  $rid  = (int)substr($row['id'], 2);
                ?>
                  <tr>
                    <td>
                      <div class="row-avatar">
                        <span class="av <?= $avc ?>"><?= $ini ?></span>
                        <span><b><?= htmlspecialchars($row['name']) ?></b>
                          <div class="muted" style="font-size:11.5px;"><?= $row['id'] ?></div>
                        </span>
                      </div>
                    </td>
                    <td class="muted"><?= htmlspecialchars($row['email']) ?></td>
                    <td><span class="pill <?= $rpl ?>"><?= $row['role'] ?></span></td>
                    <td class="muted"><?= $row['joined'] ?></td>
                    <td><?= $row['orders'] ?></td>
                    <td><span class="pill <?= $spl ?>"><?= $row['status'] ?></span></td>
                    <td style="text-align:right;">
                      <button class="btn btn-ghost btn-sm"
                        onclick="openManageUser(<?= $rid ?>,<?= htmlspecialchars(json_encode($row['name']), ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($row['role']), ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($row['status']), ENT_QUOTES) ?>,'users')">
                        Manage
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($filtered)): ?><tr>
                    <td colspan="7" style="text-align:center;color:var(--ink-3);padding:28px 0;">No users found.</td>
                  </tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'sellers'): ?>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px;">
          <div>
            <h1 class="h1" style="margin:0;">Sellers</h1>
            <p class="muted" style="margin-top:6px;"><?= count($admin_shops) ?> shops total</p>
          </div>
        </div>
        <div class="grid grid-sellers">
          <?php foreach ($admin_shops as $raw):
            $s = _normalize_shop($raw);
            $st = $raw['status'] ?? 'active';
            $spl = $st === 'active' ? 'success' : ($st === 'suspended' ? 'danger' : 'warn');
          ?>
            <article class="seller-card">
              <div class="seller-cover"><?= seller_cover($s['accent'], $s['pattern']) ?></div>
              <div class="seller-body">
                <div class="seller-avatar" style="background:<?= $s['accent'] ?>;color:var(--lime);"><?= $s['avatar'] ?></div>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                  <div>
                    <div class="seller-name"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="seller-sub"><?= $s['tag'] ?></div>
                  </div>
                  <span class="pill <?= $spl ?>"><?= ucfirst($st) ?></span>
                </div>
                <div class="seller-stats">
                  <span><b><?= $s['products'] ?></b> products</span>
                  <span><b><?= $s['followers'] ?></b> followers</span>
                  <span style="display:inline-flex;gap:4px;align-items:center;"><?= icon_star(11) ?> <b><?= $s['rating'] ?></b></span>
                </div>
                <div style="display:flex;gap:6px;margin-top:12px;">
                  <a href="/limedime/seller.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm" style="flex:1;">View</a>
                  <?php if ($st !== 'suspended'): ?>
                    <form method="post" style="flex:1;" onsubmit="return confirm('Suspend this shop?')">
                      <input type="hidden" name="action" value="suspend_shop">
                      <input type="hidden" name="shop_id" value="<?= $raw['id'] ?>">
                      <input type="hidden" name="from_tab" value="sellers">
                      <button class="btn btn-ghost btn-sm" style="width:100%;color:var(--coral);">Suspend</button>
                    </form>
                  <?php else: ?>
                    <form method="post" style="flex:1;">
                      <input type="hidden" name="action" value="activate_shop">
                      <input type="hidden" name="shop_id" value="<?= $raw['id'] ?>">
                      <input type="hidden" name="from_tab" value="sellers">
                      <button class="btn btn-ghost btn-sm" style="width:100%;color:var(--teal);">Activate</button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
          <?php if (empty($admin_shops)): ?><p class="muted" style="padding:28px 0;">No shops yet.</p><?php endif; ?>
        </div>

      <?php elseif ($tab === 'products'): ?>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px;">
          <div>
            <h1 class="h1" style="margin:0;">Products</h1>
            <p class="muted" style="margin-top:6px;"><?= count($PRODUCTS) ?> listings</p>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Seller</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($PRODUCTS as $p):
                $cat = find_category($p['cat'], $CATEGORIES);
              ?>
                <tr>
                  <td>
                    <div class="row-avatar">
                      <span style="width:36px;height:36px;border-radius:8px;overflow:hidden;flex-shrink:0;display:inline-flex;"><?= product_illu($p['illu'], $p['palette']) ?></span>
                      <span><b style="font-family:Fraunces,serif;"><?= htmlspecialchars($p['name']) ?></b>
                        <div class="muted" style="font-size:11.5px;"><?= $p['id'] ?></div>
                      </span>
                    </div>
                  </td>
                  <td class="muted"><?= htmlspecialchars($p['seller']) ?></td>
                  <td><span class="pill" style="background:var(--bg-2);"><?= $cat ? $cat['label'] : $p['cat'] ?></span></td>
                  <td><b style="color:var(--wine);">$<?= number_format($p['price'], 2) ?></b></td>
                  <td><?= $p['quantity'] ?></td>
                  <td><span class="pill success">Live</span></td>
                  <td style="text-align:right;">
                    <button class="btn btn-ghost btn-sm" onclick="openEditProduct(<?= htmlspecialchars(json_encode([
                                                                                    'id' => $p['db_id'],
                                                                                    'name' => $p['name'],
                                                                                    'price' => $p['price'],
                                                                                    'qty' => $p['quantity'],
                                                                                    'cat' => $p['cat'],
                                                                                    'status' => 'live',
                                                                                    'desc' => $p['description']
                                                                                  ])) ?>)">Edit</button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($PRODUCTS)): ?><tr>
                  <td colspan="7" style="text-align:center;color:var(--ink-3);padding:28px 0;">No products yet.</td>
                </tr><?php endif; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($tab === 'couriers'): ?>
        <h1 class="h1" style="margin:0 0 22px;">Couriers</h1>
        <?php
        $active_today_c = 0;
        $pending_c = 0;
        foreach ($admin_couriers as $r) {
          if ($r['today_deliveries'] > 0) $active_today_c++;
          if ($r['status'] === 'pending') $pending_c++;
        }
        ?>
        <div class="stat-grid">
          <div class="stat">
            <div class="eyebrow">Total registered</div>
            <div class="stat-val"><?= count($admin_couriers) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Active today</div>
            <div class="stat-val"><?= $active_today_c ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Deliveries today</div>
            <div class="stat-val"><?= array_sum(array_column($admin_couriers, 'today_deliveries')) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Pending</div>
            <div class="stat-val"><?= $pending_c ?></div>
          </div>
        </div>
        <div class="table-wrap">
          <div class="table-head">
            <h3>All couriers</h3>
          </div>
          <table>
            <thead>
              <tr>
                <th>Courier</th>
                <th>Email</th>
                <th>City</th>
                <th>Today</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admin_couriers as $row):
                $ini = '';
                foreach (array_slice(explode(' ', $row['name']), 0, 2) as $w) $ini .= strtoupper($w[0]);
                $spl = $row['status'] === 'active' ? 'success' : ($row['status'] === 'suspended' ? 'danger' : 'warn');
              ?>
                <?php $cdocs = $courier_docs[(int)$row['id']] ?? []; ?>
                <tr>
                  <td>
                    <div class="row-avatar"><span class="av teal"><?= $ini ?></span><?= htmlspecialchars($row['name']) ?></div>
                  </td>
                  <td class="muted"><?= htmlspecialchars($row['email']) ?></td>
                  <td class="muted"><?= htmlspecialchars($row['city'] ?? '—') ?></td>
                  <td><b><?= (int)$row['today_deliveries'] ?></b></td>
                  <td><span class="pill <?= $spl ?>"><?= $row['status'] ?></span></td>
                  <td style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
                    <button class="btn btn-ghost btn-sm" style="color:<?= empty($cdocs) ? 'var(--ink-3)' : 'var(--teal)' ?>;<?= !empty($cdocs) ? 'border-color:var(--teal);' : '' ?>"
                      onclick="openDocsModal(<?= $row['id'] ?>,<?= htmlspecialchars(json_encode($row['name']), ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($cdocs), ENT_QUOTES) ?>)">
                      <?= icon_package(13) ?> Docs<?php if (!empty($cdocs)): ?> <span style="font-size:10px;font-weight:700;background:var(--teal);color:white;border-radius:999px;padding:1px 5px;"><?= count($cdocs) ?></span><?php endif; ?>
                    </button>
                    <button class="btn btn-ghost btn-sm"
                      onclick="openManageUser(<?= $row['id'] ?>,<?= htmlspecialchars(json_encode($row['name']), ENT_QUOTES) ?>,'Courier',<?= htmlspecialchars(json_encode($row['status']), ENT_QUOTES) ?>,'couriers')">
                      Manage
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($admin_couriers)): ?><tr>
                  <td colspan="6" style="text-align:center;color:var(--ink-3);padding:28px 0;">No couriers yet.</td>
                </tr><?php endif; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($tab === 'reports'): ?>
        <h1 class="h1" style="margin:0 0 22px;">Reports</h1>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
          <?php foreach (
            [
              ['Monthly GMV',        'monthly_gmv',      'CSV', 'var(--coral)'],
              ['Seller payouts',     'seller_payouts',   'CSV', 'var(--wine)'],
              ['Courier earnings',   'courier_earnings', 'CSV', 'var(--teal)'],
              ['Tax summary',        'tax_summary',      'CSV', '#4C86A8'],
              ['Refunds & disputes', 'refunds',          'CSV', '#C8A87A'],
              ['Audit log',          'audit_log',        'JSON', 'var(--ink-2)'],
            ] as [$title, $slug, $fmt, $color]
          ): ?>
            <div class="card" style="padding:20px;">
              <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="eyebrow"><?= $title ?></div>
                <span style="width:36px;height:36px;border-radius:10px;background:<?= $color ?>;color:var(--lime);display:inline-flex;align-items:center;justify-content:center;"><?= icon_trend(18) ?></span>
              </div>
              <div class="muted" style="font-size:12px;margin-top:8px;"><?= $fmt ?> · Generated on demand</div>
              <a href="?action=download&report=<?= $slug ?>" class="btn btn-ghost btn-sm" style="margin-top:12px;width:100%;display:flex;justify-content:center;">Download <?= $fmt ?></a>
            </div>
          <?php endforeach; ?>
        </div>

      <?php elseif ($tab === 'messages'): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px;">
          <div>
            <h1 class="h1" style="margin:0 0 4px;">Contact messages</h1>
            <p class="muted" style="margin:0;">Messages sent via the contact form.</p>
          </div>
          <div style="display:flex;gap:8px;">
            <?php foreach (['all' => 'All', 'unread' => 'Unread', 'archived' => 'Archived'] as $mfk => $mfl): ?>
              <a href="/limedime/admin.php?tab=messages&mf=<?= $mfk ?>"
                class="btn <?= ($_GET['mf'] ?? 'all') === $mfk ? 'btn-primary' : 'btn-ghost' ?> btn-sm"><?= $mfl ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if (empty($contact_messages)): ?>
          <div class="empty-state">
            <h3 class="h3" style="margin:0 0 6px;">No messages yet</h3>
            <p class="muted" style="margin:0;">When someone submits the contact form, messages will appear here.</p>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($contact_messages as $cm): ?>
              <div class="card" style="padding:20px;<?= $cm['status'] === 'unread' ? 'border-left:3px solid var(--wine);' : 'opacity:.85;' ?>">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                  <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
                      <b style="font-size:15px;"><?= htmlspecialchars($cm['first_name'] . ' ' . $cm['last_name']) ?></b>
                      <a href="mailto:<?= htmlspecialchars($cm['email']) ?>" style="color:var(--ink-3);font-size:13px;"><?= htmlspecialchars($cm['email']) ?></a>
                      <?php if ($cm['status'] === 'unread'): ?><span class="pill warn" style="font-size:11px;">Unread</span><?php endif; ?>
                      <?php if ($cm['status'] === 'archived'): ?><span class="pill" style="font-size:11px;">Archived</span><?php endif; ?>
                    </div>
                    <div style="font-weight:600;font-size:13.5px;color:var(--ink-2);margin-bottom:8px;"><?= htmlspecialchars($cm['subject']) ?></div>
                    <p style="margin:0;color:var(--ink-2);font-size:13.5px;line-height:1.6;white-space:pre-wrap;"><?= htmlspecialchars($cm['message']) ?></p>
                    <div class="muted" style="font-size:12px;margin-top:10px;"><?= date('d M Y, H:i', strtotime($cm['created_at'])) ?></div>
                  </div>
                  <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
                    <?php if ($cm['status'] !== 'read'): ?>
                      <form method="post"><input type="hidden" name="action" value="mark_message"><input type="hidden" name="from_tab" value="messages"><input type="hidden" name="message_id" value="<?= $cm['id'] ?>"><input type="hidden" name="status" value="read"><button class="btn btn-ghost btn-sm" style="width:100%;">Mark read</button></form>
                    <?php endif; ?>
                    <?php if ($cm['status'] !== 'archived'): ?>
                      <form method="post"><input type="hidden" name="action" value="mark_message"><input type="hidden" name="from_tab" value="messages"><input type="hidden" name="message_id" value="<?= $cm['id'] ?>"><input type="hidden" name="status" value="archived"><button class="btn btn-ghost btn-sm" style="width:100%;">Archive</button></form>
                    <?php endif; ?>
                    <form method="post" onsubmit="return confirm('Delete this message?');"><input type="hidden" name="action" value="delete_message"><input type="hidden" name="from_tab" value="messages"><input type="hidden" name="message_id" value="<?= $cm['id'] ?>"><button class="btn btn-ghost btn-sm" style="width:100%;color:var(--coral);">Delete</button></form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'settings'): ?>
        <h1 class="h1" style="margin:0 0 6px;">Platform settings</h1>
        <p class="muted" style="margin:0 0 22px;">Changes take effect immediately.</p>
        <?php if (($_GET['ok'] ?? '') === 'commission'): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:18px;">Commission rates saved.</div>
        <?php elseif (($_GET['ok'] ?? '') === 'regions'): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:18px;">Operating regions saved.</div>
        <?php endif; ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">

          <form method="post">
            <input type="hidden" name="action" value="save_commission">
            <input type="hidden" name="from_tab" value="settings">
            <div class="card" style="padding:24px;">
              <h3 class="h3" style="margin-top:0;">Commission rates</h3>
              <p class="muted" style="font-size:13.5px;">Fee charged on every completed order.</p>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:14px;">
                <div class="field"><label>Standard %</label><input type="number" name="commission_standard" step="0.1" min="0" max="100" value="<?= htmlspecialchars($platform_settings['commission_standard'] ?? '8.5') ?>"></div>
                <div class="field"><label>New seller %</label><input type="number" name="commission_new_seller" step="0.1" min="0" max="100" value="<?= htmlspecialchars($platform_settings['commission_new_seller'] ?? '5.0') ?>"></div>
                <div class="field"><label>Featured boost $/wk</label><input type="number" name="commission_featured_boost" step="0.5" min="0" value="<?= htmlspecialchars($platform_settings['commission_featured_boost'] ?? '24.00') ?>"></div>
              </div>
              <div style="display:flex;justify-content:flex-end;margin-top:14px;"><button class="btn btn-primary btn-sm" type="submit">Save</button></div>
            </div>
          </form>

          <div class="card" style="padding:24px;">
            <h3 class="h3" style="margin-top:0;">Categories</h3>
            <p class="muted" style="font-size:13.5px;">Top-level categories shown in navigation.</p>
            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px;">
              <?php foreach ($CATEGORIES as $c): if ($c['id'] === 'all') continue; ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface-2);">
                  <span style="width:10px;height:10px;border-radius:3px;flex-shrink:0;background:<?= $c['color'] ?>;"></span>
                  <span style="flex:1;font-weight:600;font-size:13.5px;"><?= $c['label'] ?></span>
                  <span class="pill success" style="font-size:11px;">Active</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <?php
          $saved_regions = array_filter(array_map('trim', explode(',', $platform_settings['regions'] ?? 'Portland,Brooklyn,Seattle,Austin,Denver,Oakland')));
          ?>
          <form method="post" style="grid-column:1/-1;">
            <input type="hidden" name="action" value="save_regions">
            <input type="hidden" name="from_tab" value="settings">
            <div class="card" style="padding:24px;">
              <h3 class="h3" style="margin-top:0;">Operating regions</h3>
              <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:14px;">
                <?php foreach (['Portland', 'Brooklyn', 'Seattle', 'Austin', 'Chicago', 'Denver', 'Boston', 'Oakland'] as $city): ?>
                  <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface-2);cursor:pointer;">
                    <input type="checkbox" name="region_<?= strtolower($city) ?>" <?= in_array($city, $saved_regions) ? 'checked' : '' ?>>
                    <span style="font-weight:600;font-size:13.5px;"><?= $city ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <div style="display:flex;justify-content:flex-end;margin-top:14px;"><button class="btn btn-primary btn-sm" type="submit">Save regions</button></div>
            </div>
          </form>

        </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<div id="user-manage-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:var(--surface);border-radius:var(--r-lg);padding:28px;width:400px;max-width:calc(100vw - 40px);box-shadow:0 20px 60px rgba(0,0,0,.2);">
    <h3 class="h3" style="margin:0 0 4px;" id="um-name"></h3>
    <p class="muted" style="margin:0 0 22px;font-size:13px;" id="um-meta"></p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
      <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="suspend_user">
        <input type="hidden" name="user_id" class="um-id">
        <input type="hidden" name="from_tab" class="um-tab">
        <button id="um-btn-suspend" type="submit" class="btn btn-ghost btn-sm" style="color:var(--coral);">Suspend</button>
      </form>
      <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="activate_user">
        <input type="hidden" name="user_id" class="um-id">
        <input type="hidden" name="from_tab" class="um-tab">
        <button id="um-btn-activate" type="submit" class="btn btn-ghost btn-sm" style="color:var(--teal);">Activate</button>
      </form>
      <form method="post" style="display:inline;" onsubmit="return confirm('Permanently delete this user?')">
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="user_id" class="um-id">
        <input type="hidden" name="from_tab" class="um-tab">
        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--wine);">Delete</button>
      </form>
      <button type="button" class="btn btn-ghost btn-sm" style="margin-left:auto;" onclick="closeModal('user-manage-modal')">Close</button>
    </div>
  </div>
</div>

<div id="product-edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:var(--surface);border-radius:var(--r-lg);padding:28px;width:480px;max-width:calc(100vw - 40px);box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto;">
    <h3 class="h3" style="margin:0 0 20px;">Edit product</h3>
    <form method="post">
      <input type="hidden" name="action" value="edit_product">
      <input type="hidden" name="product_id" id="pe-id">
      <div class="field" style="margin-bottom:12px;"><label>Name</label><input name="name" id="pe-name" required></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
        <div class="field"><label>Price ($)</label><input name="price" id="pe-price" type="number" step="0.01" min="0" required></div>
        <div class="field"><label>Quantity</label><input name="quantity" id="pe-qty" type="number" min="0" required></div>
        <div class="field">
          <label>Category</label>
          <select name="category" id="pe-cat" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
            <?php foreach (['food', 'clothing', 'electronics', 'education', 'beauty'] as $c): ?>
              <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Status</label>
          <select name="pstatus" id="pe-status" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
            <option value="live">Live</option>
            <option value="draft">Draft</option>
            <option value="archived">Archived</option>
          </select>
        </div>
      </div>
      <div class="field" style="margin-bottom:4px;"><label>Description</label>
        <textarea name="description" id="pe-desc" rows="3" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);font-family:inherit;font-size:14px;resize:vertical;box-sizing:border-box;"></textarea>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
        <button type="button" class="btn btn-ghost" onclick="closeModal('product-edit-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(id) {
    document.getElementById(id).style.display = 'flex';
  }

  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
  }

  function openManageUser(id, name, role, status, fromTab) {
    document.getElementById('um-name').textContent = name;
    document.getElementById('um-meta').textContent = role + ' · ' + status;
    document.querySelectorAll('.um-id').forEach(function(el) {
      el.value = id;
    });
    document.querySelectorAll('.um-tab').forEach(function(el) {
      el.value = fromTab;
    });
    document.getElementById('um-btn-suspend').style.display = status === 'active' ? '' : 'none';
    document.getElementById('um-btn-activate').style.display = status !== 'active' ? '' : 'none';
    openModal('user-manage-modal');
  }

  function openEditProduct(data) {
    document.getElementById('pe-id').value = data.id;
    document.getElementById('pe-name').value = data.name;
    document.getElementById('pe-price').value = data.price;
    document.getElementById('pe-qty').value = data.qty;
    document.getElementById('pe-cat').value = data.cat;
    document.getElementById('pe-status').value = data.status;
    document.getElementById('pe-desc').value = data.desc;
    openModal('product-edit-modal');
  }

  document.addEventListener('click', function(e) {
    ['user-manage-modal', 'product-edit-modal'].forEach(function(id) {
      var m = document.getElementById(id);
      if (m && e.target === m) m.style.display = 'none';
    });
  });
</script>

<div id="docs-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1100;align-items:center;justify-content:center;">
  <div style="background:var(--surface);border-radius:var(--r-lg);padding:28px;width:520px;max-width:calc(100vw - 40px);box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:88vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
      <div>
        <div class="eyebrow">Submitted documents</div>
        <h3 class="h3" style="margin:4px 0 0;" id="docs-name"></h3>
      </div>
      <button class="icon-btn" onclick="closeModal('docs-modal')">✕</button>
    </div>
    <div id="docs-list" style="display:flex;flex-direction:column;gap:10px;"></div>
  </div>
</div>

<script>
  function openDocsModal(uid, name, docs) {
    document.getElementById('docs-name').textContent = name;
    var list = document.getElementById('docs-list');
    list.innerHTML = docs.length === 0 ?
      '<p class="muted" style="text-align:center;padding:20px;">No documents uploaded.</p>' :
      docs.map(function(d) {
        var ext = d.file_path.split('.').pop().toLowerCase();
        var isImg = ['jpg', 'jpeg', 'png', 'webp', 'gif'].indexOf(ext) !== -1;
        var url = '/limedime/assets/docs/' + d.file_path;
        return '<div style="display:flex;align-items:center;gap:14px;padding:14px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface-2);">' +
          (isImg ?
            '<img src="' + url + '" style="width:60px;height:60px;object-fit:cover;border-radius:8px;flex-shrink:0;">' :
            '<div style="width:60px;height:60px;border-radius:8px;background:var(--bg-2);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--ink-3);flex-shrink:0;">' + ext.toUpperCase() + '</div>') +
          '<div style="flex:1;min-width:0;">' +
          '<div style="font-weight:600;font-size:14px;">' + d.label + '</div>' +
          '<div class="muted" style="font-size:12px;margin-top:2px;">' + d.file_path + ' · ' + d.uploaded_at + '</div>' +
          '</div>' +
          '<a href="' + url + '" target="_blank" class="btn btn-ghost btn-sm">View</a>' +
          '</div>';
      }).join('');
    document.getElementById('docs-modal').style.display = 'flex';
  }
</script>

<?php include '_partials/footer.php'; ?>
