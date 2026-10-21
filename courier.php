<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

require_login('/limedime/login.php');
require_role('courier', '/limedime/index.php');

$u = current_user();
$uid = (int)$u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act      = $_POST['action']   ?? '';
  $from_tab = $_POST['from_tab'] ?? 'dashboard';

  if ($act === 'accept_order') {
    $oid    = (int)($_POST['order_id'] ?? 0);
    $addr   = trim($_POST['addr']    ?? '');
    $payout = (float)($_POST['payout'] ?? 5.00);
    if ($oid > 0) {
      $existing = mysqli_query($conn, "SELECT id FROM deliveries WHERE order_id=$oid LIMIT 1");
      if ($ex = ($existing ? mysqli_fetch_assoc($existing) : null)) {
        mysqli_query($conn, "UPDATE deliveries SET courier_id=$uid, status='assigned', assigned_at=NOW() WHERE id=" . (int)$ex['id']);
      } else {
        $sa = mysqli_real_escape_string($conn, $addr ?: 'N/A');
        mysqli_query($conn, "INSERT INTO deliveries (order_id, courier_id, status, dropoff_address, payout, distance_km, eta_minutes, assigned_at) VALUES ($oid, $uid, 'assigned', '$sa', $payout, 0, 30, NOW())");
      }
      mysqli_query($conn, "UPDATE orders SET status='accepted' WHERE id=$oid AND status='pending'");
    }
    header('Location: /limedime/courier.php?tab=deliveries&ok=accepted');
    exit;
  } elseif ($act === 'update_delivery') {
    $did    = (int)($_POST['delivery_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed_statuses = ['assigned', 'picked_up', 'delivered'];
    if ($did > 0 && in_array($status, $allowed_statuses)) {
      if ($status === 'delivered') {
        mysqli_query($conn, "UPDATE deliveries SET status='delivered', delivered_at=NOW() WHERE id=$did AND courier_id=$uid");
        mysqli_query($conn, "UPDATE orders o JOIN deliveries d ON d.order_id=o.id SET o.status='delivered' WHERE d.id=$did AND d.courier_id=$uid");
      } elseif ($status === 'picked_up') {
        mysqli_query($conn, "UPDATE deliveries SET status='picked_up' WHERE id=$did AND courier_id=$uid");
        mysqli_query($conn, "UPDATE orders o JOIN deliveries d ON d.order_id=o.id SET o.status='out_for_delivery' WHERE d.id=$did AND d.courier_id=$uid");
      } else {
        $ss = mysqli_real_escape_string($conn, $status);
        mysqli_query($conn, "UPDATE deliveries SET status='$ss' WHERE id=$did AND courier_id=$uid");
      }
    }
    header('Location: /limedime/courier.php?tab=deliveries&ok=updated');
    exit;
  } elseif ($act === 'toggle_online') {
    mysqli_query($conn, "UPDATE users SET is_online = 1 - COALESCE(is_online,0) WHERE id=$uid");
    header('Location: /limedime/courier.php?tab=' . $from_tab);
    exit;
  } elseif ($act === 'save_profile') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city  = trim($_POST['city']  ?? '');
    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL)) {
      if ($email !== $u['email']) {
        $se = mysqli_real_escape_string($conn, $email);
        $c = mysqli_query($conn, "SELECT id FROM users WHERE email='$se' AND id!=$uid");
        if ($c && mysqli_fetch_assoc($c)) {
          header('Location: /limedime/courier.php?tab=profile&err=email');
          exit;
        }
      }
      $sn = mysqli_real_escape_string($conn, $name);
      $se = mysqli_real_escape_string($conn, $email);
      $sp = mysqli_real_escape_string($conn, $phone);
      $sc = mysqli_real_escape_string($conn, $city);
      mysqli_query($conn, "UPDATE users SET name='$sn', email='$se', phone='$sp', city='$sc' WHERE id=$uid");
      $_SESSION['user']['name']  = $name;
      $_SESSION['user']['email'] = $email;
    }
    header('Location: /limedime/courier.php?tab=profile&ok=saved');
    exit;
  }
}


$is_online = false;
$sq = mysqli_query($conn, "SELECT is_online FROM users WHERE id=$uid");
if ($sq && ($sr = mysqli_fetch_assoc($sq))) $is_online = (bool)$sr['is_online'];


$courier_row = [];
$cq = mysqli_query($conn, "SELECT * FROM users WHERE id=$uid");
if ($cq) $courier_row = mysqli_fetch_assoc($cq) ?: [];


$today_deliveries = 0;
$today_earned     = 0.0;
$tq = mysqli_query($conn, "SELECT COUNT(*) AS cnt, COALESCE(SUM(payout),0) AS earned FROM deliveries WHERE courier_id=$uid AND status='delivered' AND DATE(delivered_at)=CURDATE()");
if ($tq && ($tr = mysqli_fetch_assoc($tq))) {
  $today_deliveries = (int)$tr['cnt'];
  $today_earned     = (float)$tr['earned'];
}


$avg_rating = 0.0;
$rq = mysqli_query($conn, "SELECT ROUND(AVG(rating),1) AS avg_r FROM deliveries WHERE courier_id=$uid AND rating IS NOT NULL AND delivered_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)");
if ($rq && ($rr = mysqli_fetch_assoc($rq))) $avg_rating = (float)($rr['avg_r'] ?: 0);


$weekly_by_day = array_fill(0, 7, 0.0);
$weekly_total  = 0.0;
$wq = mysqli_query($conn, "SELECT WEEKDAY(delivered_at) AS dow, ROUND(SUM(payout),2) AS total FROM deliveries WHERE courier_id=$uid AND status='delivered' AND YEARWEEK(delivered_at,1) = YEARWEEK(CURDATE(),1) GROUP BY WEEKDAY(delivered_at)");
if ($wq) {
  while ($wr = mysqli_fetch_assoc($wq)) $weekly_by_day[(int)$wr['dow']] = (float)$wr['total'];
}
$weekly_total = round(array_sum($weekly_by_day), 2);


$monthly_earned = 0.0;
$mq = mysqli_query($conn, "SELECT COALESCE(SUM(payout),0) AS earned FROM deliveries WHERE courier_id=$uid AND status='delivered' AND MONTH(delivered_at)=MONTH(CURDATE()) AND YEAR(delivered_at)=YEAR(CURDATE())");
if ($mq && ($mr = mysqli_fetch_assoc($mq))) $monthly_earned = (float)$mr['earned'];


$twelve_week = array_fill(0, 12, 0.0);
$twq = mysqli_query($conn, "SELECT FLOOR(DATEDIFF(CURDATE(), DATE(delivered_at)) / 7) AS wago, ROUND(SUM(payout),2) AS total FROM deliveries WHERE courier_id=$uid AND status='delivered' AND delivered_at >= DATE_SUB(CURDATE(), INTERVAL 84 DAY) GROUP BY wago");
if ($twq) {
  while ($twr = mysqli_fetch_assoc($twq)) {
    $wa = (int)$twr['wago'];
    if ($wa < 12) $twelve_week[11 - $wa] = (float)$twr['total'];
  }
}


$active_del = null;
foreach ($COURIER_DELIVERIES as $d) {
  if (in_array($d['status'], ['assigned', 'picked_up', 'out_for_delivery'])) {
    $active_del = $d;
    break;
  }
}


$ini = '';
$name_parts = array_slice(explode(' ', $u['name']), 0, 2);
foreach ($name_parts as $w) $ini .= strtoupper($w[0]);
$today_dow = (int)date('N') - 1;
$max_day   = max(array_merge($weekly_by_day, [1]));

function courier_line_chart($vals)
{
  $w = 600;
  $h = 180;
  $pad = 14;
  $n = count($vals);
  $max = max(array_merge($vals, [0.01]));
  $coords = [];
  foreach ($vals as $i => $v) {
    $x = round($w * $i / max($n - 1, 1), 1);
    $y = round($h - $pad - ($v / $max) * ($h - 2 * $pad), 1);
    $coords[] = "$x $y";
  }
  $line = 'M ' . implode(' L ', $coords);
  [$lx] = explode(' ', end($coords));
  $area = "$line L $lx $h L 0 $h Z";
  $grid = '';
  for ($i = 0; $i < 4; $i++) {
    $y = $i * 45;
    $grid .= "<line x1=\"0\" x2=\"600\" y1=\"{$y}\" y2=\"{$y}\" stroke=\"var(--line)\" stroke-width=\"1\"/>";
  }
  $dots = '';
  foreach ([0, 3, 6, 9, 11] as $i) {
    if (!isset($coords[$i])) continue;
    [$cx, $cy] = explode(' ', $coords[$i]);
    $dots .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"4\" fill=\"var(--lime)\" stroke=\"var(--wine)\" stroke-width=\"2\"/>";
  }
  return "<defs><linearGradient id=\"ecg\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0%\" stop-color=\"var(--wine)\" stop-opacity=\".25\"/><stop offset=\"100%\" stop-color=\"var(--wine)\" stop-opacity=\"0\"/></linearGradient></defs>{$grid}<path d=\"{$area}\" fill=\"url(#ecg)\"/><path d=\"{$line}\" fill=\"none\" stroke=\"var(--wine)\" stroke-width=\"3\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/>{$dots}";
}

$tab = $_GET['tab'] ?? 'dashboard';
$allowed = ['dashboard', 'deliveries', 'history', 'earnings', 'profile'];
if (!in_array($tab, $allowed)) $tab = 'dashboard';

$active_count = 0;
foreach ($COURIER_DELIVERIES as $d) {
  if (in_array($d['status'], ['assigned', 'picked_up', 'out_for_delivery'])) $active_count++;
}
$available_count  = count($AVAILABLE_ORDERS);
$deliveries_badge = $active_count + $available_count ?: null;

$page_title   = 'Courier panel';
$active_route = 'courier';
$show_catbar  = false;
include '_partials/header.php';

$sidebar_items = [
  ['id' => 'dashboard', 'label' => 'Dashboard',  'icon' => icon_home(18)],
  ['id' => 'deliveries', 'label' => 'Deliveries', 'icon' => icon_package(18), 'count' => $deliveries_badge],
  ['id' => 'history',   'label' => 'History',    'icon' => icon_clock(18), 'count' => count($COURIER_HISTORY) ?: null],
  ['id' => 'earnings',  'label' => 'Earnings',   'icon' => icon_cash(18)],
  ['id' => 'profile',   'label' => 'Profile',    'icon' => icon_user(18)],
];
?>

<div class="container">
  <div class="sidebar-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div style="width:32px;height:32px;border-radius:999px;background:var(--teal);color:var(--lime);display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;"><?= $ini ?></div>
        <div style="line-height:1.1;"><b><?= htmlspecialchars($u['name']) ?></b>
          <div class="role">Courier</div>
        </div>
      </div>
      <nav>
        <?php foreach ($sidebar_items as $item): ?>
          <a href="/limedime/courier.php?tab=<?= $item['id'] ?>" class="side-link <?= $tab === $item['id'] ? 'active' : '' ?>">
            <?= $item['icon'] ?> <span><?= $item['label'] ?></span>
            <?php if (!empty($item['count'])): ?><span class="count"><?= $item['count'] ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div style="margin-top:18px;padding-top:14px;border-top:1px dashed var(--line);">
        <form method="post" style="padding:6px 12px 10px;">
          <input type="hidden" name="action" value="toggle_online">
          <input type="hidden" name="from_tab" value="<?= $tab ?>">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <div style="font-size:11.5px;color:var(--ink-3);font-weight:600;letter-spacing:.04em;text-transform:uppercase;">Status</div>
              <div style="font-weight:700;color:<?= $is_online ? 'var(--teal)' : 'var(--ink-3)' ?>;"><?= $is_online ? '● Online' : '○ Offline' ?></div>
            </div>
            <button type="submit"
              style="width:42px;height:24px;border-radius:999px;border:0;background:<?= $is_online ? 'var(--teal)' : 'var(--line)' ?>;position:relative;cursor:pointer;flex-shrink:0;transition:background .2s;"
              title="<?= $is_online ? 'Go offline' : 'Go online' ?>">
              <span style="position:absolute;top:4px;<?= $is_online ? 'left:21px' : 'left:4px' ?>;width:16px;height:16px;border-radius:999px;background:white;transition:left .2s;"></span>
            </button>
          </div>
        </form>
        <a href="/limedime/index.php" class="side-link"><?= icon_logout(18) ?> <span>Back to shop</span></a>
      </div>
    </aside>

    <main>

      <?php
      $ok  = $_GET['ok']  ?? '';
      $err = $_GET['err'] ?? '';
      if ($ok === 'saved'): ?>
        <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Profile saved.</div>
      <?php elseif ($err === 'email'): ?>
        <div style="background:rgba(142,59,70,.1);border:1px solid var(--wine);border-radius:var(--r);padding:12px 16px;color:var(--wine);font-size:13.5px;margin-bottom:16px;">That email is already in use.</div>
      <?php endif; ?>

      <?php if ($tab === 'dashboard'): ?>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px;">
          <div>
            <div class="eyebrow">Good <?= date('G') < 12 ? 'morning' : (date('G') < 18 ? 'afternoon' : 'evening') ?>, <?= htmlspecialchars(explode(' ', $u['name'])[0]) ?></div>
            <h1 class="h1" style="margin:4px 0 0;"><?= date('l') ?>'s shift</h1>
          </div>
          <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="toggle_online">
            <input type="hidden" name="from_tab" value="dashboard">
            <button type="submit" class="btn <?= $is_online ? 'btn-primary' : 'btn-ghost' ?>"
              style="<?= !$is_online ? 'color:var(--teal);border-color:var(--teal);' : '' ?>">
              <?= $is_online ? 'Go offline' : 'Go online' ?>
            </button>
          </form>
        </div>

        <div class="stat-grid">
          <div class="stat">
            <div class="eyebrow">Deliveries today</div>
            <div class="stat-val"><?= $today_deliveries ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Earned today</div>
            <div class="stat-val">$<?= number_format($today_earned, 2) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Rating (30d)</div>
            <div class="stat-val"><?= $avg_rating > 0 ? $avg_rating : '—' ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Status</div>
            <div class="stat-val" style="font-size:22px;color:<?= $is_online ? 'var(--teal)' : 'var(--ink-3)' ?>;"><?= $is_online ? 'Online' : 'Offline' ?></div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;">
          <div class="card" style="padding:0;overflow:hidden;">
            <?php if ($active_del): ?>
              <div class="map" style="height:280px;border-radius:0;border:none;border-bottom:1px solid var(--line);position:relative;">
                <svg viewBox="0 0 600 280" style="position:absolute;inset:0;width:100%;height:100%;">
                  <path d="M 80 220 Q 200 190 300 140 Q 400 100 520 60" fill="none" stroke="var(--wine)" stroke-width="4" stroke-linecap="round" />
                  <path d="M 80 220 Q 200 190 300 140" fill="none" stroke="var(--coral)" stroke-width="4" stroke-linecap="round" />
                  <g transform="translate(80 220)">
                    <circle r="14" fill="var(--lime)" stroke="var(--wine)" stroke-width="3" /><text text-anchor="middle" y="5" font-family="Fraunces,serif" font-weight="700" font-size="14" fill="var(--wine)">A</text>
                  </g>
                  <g transform="translate(300 140)">
                    <circle r="22" fill="rgba(142,59,70,.18)" />
                    <circle r="12" fill="var(--wine)" stroke="var(--lime)" stroke-width="3" />
                  </g>
                  <g transform="translate(520 60)">
                    <circle r="14" fill="var(--coral)" stroke="var(--wine)" stroke-width="3" /><text text-anchor="middle" y="5" font-family="Fraunces,serif" font-weight="700" font-size="14" fill="var(--surface)">B</text>
                  </g>
                </svg>
                <div style="position:absolute;top:16px;left:16px;background:var(--surface);padding:10px 14px;border-radius:var(--r);border:1px solid var(--line);display:flex;gap:14px;align-items:center;">
                  <div style="width:8px;height:8px;border-radius:999px;background:var(--coral);"></div>
                  <div>
                    <div style="font-size:11px;color:var(--ink-3);font-weight:600;">Active delivery</div>
                    <div style="font-weight:700;font-size:14px;"><?= $active_del['id'] ?></div>
                  </div>
                </div>
              </div>
              <div style="padding:18px;display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:14px;align-items:center;">
                <div>
                  <div class="eyebrow">Drop-off</div>
                  <div class="muted" style="font-size:13px;margin-top:2px;"><?= htmlspecialchars($active_del['addr']) ?></div>
                </div>
                <div>
                  <div class="eyebrow">Distance</div><b><?= $active_del['dist'] ?></b>
                </div>
                <div>
                  <div class="eyebrow">ETA</div><b style="font-family:Fraunces,serif;font-size:22px;"><?= $active_del['eta'] ?></b>
                </div>
                <div>
                  <div class="eyebrow">Payout</div><b style="font-family:Fraunces,serif;font-size:22px;color:var(--wine);">$<?= number_format($active_del['payout'], 2) ?></b>
                </div>
                <a href="/limedime/courier.php?tab=deliveries" class="btn btn-primary">Open</a>
              </div>
            <?php else: ?>
              <div style="padding:48px;text-align:center;color:var(--ink-3);">
                <div style="font-size:40px;margin-bottom:12px;">📦</div>
                <b style="font-size:15px;">No active delivery</b>
                <p class="muted" style="margin:6px 0 0;font-size:13px;"><?= $is_online ? 'Waiting for the next order…' : 'Go online to start receiving deliveries.' ?></p>
              </div>
            <?php endif; ?>
          </div>

          <div class="card" style="padding:22px;">
            <div class="eyebrow">This week · Mon → Sun</div>
            <?php $week_goal = 500; ?>
            <div style="font-family:Fraunces,serif;font-size:44px;font-weight:700;color:var(--wine);letter-spacing:-0.02em;line-height:1;margin:6px 0;">$<?= number_format($weekly_total, 2) ?></div>
            <div class="muted" style="font-size:13px;">Goal $<?= $week_goal ?> · payout Friday</div>
            <div style="height:8px;background:var(--bg-2);border-radius:999px;margin-top:14px;overflow:hidden;">
              <div style="width:<?= min(100, round($weekly_total / $week_goal * 100)) ?>%;height:100%;background:linear-gradient(90deg,var(--coral) 0%,var(--wine) 100%);"></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-top:22px;align-items:end;height:80px;">
              <?php foreach ($weekly_by_day as $i => $v):
                $bar_h = $max_day > 0 ? max(2, round($v / $max_day * 100)) : 2; ?>
                <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                  <div style="width:100%;height:<?= $bar_h ?>%;background:<?= $v > 0 ? 'var(--wine)' : 'var(--bg-2)' ?>;border-radius:6px;opacity:<?= $i === $today_dow ? 1 : .8 ?>;"></div>
                  <div style="font-size:11px;color:var(--ink-3);font-weight:600;"><?= ['M', 'T', 'W', 'T', 'F', 'S', 'S'][$i] ?></div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="divider"></div>
            <div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--ink-3);">
              <span>Total deliveries today</span><b style="color:var(--ink);"><?= $today_deliveries ?></b>
            </div>
          </div>
        </div>

        <?php if (!empty($COURIER_DELIVERIES)): ?>
          <div style="margin-top:24px;">
            <div class="section-head">
              <h2 class="h2" style="margin:0;font-size:24px;">Up next</h2><a href="/limedime/courier.php?tab=deliveries" style="color:var(--wine);font-weight:700;font-size:13.5px;">See all</a>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
              <?php
              $status_colors = ['assigned' => ['var(--ink-4)', 'Assigned'], 'picked_up' => ['var(--coral)', 'Picked up'], 'out_for_delivery' => ['var(--teal)', 'On the way']];
              foreach ($COURIER_DELIVERIES as $d):
                [$c, $label] = $status_colors[$d['status']] ?? ['var(--ink-4)', 'Active']; ?>
                <div class="card" style="padding:18px;">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                    <span class="pill" style="background:<?= $c ?>;color:var(--surface);"><?= $label ?></span>
                    <span class="muted" style="font-size:12px;"><?= $d['time'] ?></span>
                  </div>
                  <b style="font-family:Fraunces,serif;font-size:18px;"><?= $d['id'] ?></b>
                  <div class="muted" style="font-size:13px;margin-top:4px;"><?= htmlspecialchars($d['addr']) ?></div>
                  <div class="divider"></div>
                  <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span class="muted" style="font-size:12.5px;"><?= $d['dist'] ?> · ETA <?= $d['eta'] ?></span>
                    <b style="font-family:Fraunces,serif;font-size:18px;color:var(--wine);">$<?= number_format($d['payout'], 2) ?></b>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="card" style="padding:32px;text-align:center;color:var(--ink-3);margin-top:24px;">
            <b>Queue is empty.</b>
            <p class="muted" style="margin:4px 0 0;font-size:13px;"><?= $is_online ? 'New orders will appear here.' : 'Go online to receive deliveries.' ?></p>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'deliveries'): ?>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:8px;">
          <div>
            <h1 class="h1" style="margin:0;">Deliveries</h1>
            <p class="muted" style="margin-top:6px;margin-bottom:0;"><?= count($COURIER_DELIVERIES) ?> active · <?= $today_deliveries ?> delivered today</p>
          </div>
        </div>

        <?php if (!empty($ok) && $ok === 'accepted'): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:18px;">Order accepted — it's now in your active deliveries.</div>
        <?php elseif (!empty($ok) && $ok === 'updated'): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:18px;">Delivery status updated.</div>
        <?php endif; ?>

        <?php if (!empty($AVAILABLE_ORDERS)): ?>
          <div style="margin-bottom:28px;">
            <div class="section-head" style="margin-bottom:14px;">
              <h2 class="h2" style="margin:0;font-size:20px;">Available orders <span style="font-size:14px;font-family:sans-serif;font-weight:600;background:var(--coral);color:var(--surface);border-radius:999px;padding:2px 9px;vertical-align:middle;"><?= count($AVAILABLE_ORDERS) ?></span></h2>
              <p class="muted" style="margin:4px 0 0;font-size:13px;">Pending orders waiting for a courier to accept.</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
              <?php foreach ($AVAILABLE_ORDERS as $ao): ?>
                <div class="card" style="padding:20px;border-color:var(--coral);">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                    <b style="font-family:Fraunces,serif;font-size:18px;"><?= $ao['id'] ?></b>
                    <span class="pill warn">Pending</span>
                  </div>
                  <div class="muted" style="font-size:13px;margin-bottom:4px;"><?= htmlspecialchars($ao['addr']) ?></div>
                  <div class="muted" style="font-size:12px;margin-bottom:14px;"><?= $ao['time'] ?> · <?= $ao['items'] ?> item<?= $ao['items'] !== 1 ? 's' : '' ?></div>
                  <div class="divider"></div>
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;">
                    <div>
                      <div class="eyebrow" style="font-size:10.5px;">Your payout</div>
                      <b style="font-family:Fraunces,serif;font-size:24px;color:var(--wine);">$<?= number_format($ao['payout'], 2) ?></b>
                    </div>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="action" value="accept_order">
                      <input type="hidden" name="order_id" value="<?= $ao['db_id'] ?>">
                      <input type="hidden" name="addr" value="<?= htmlspecialchars($ao['addr']) ?>">
                      <input type="hidden" name="payout" value="<?= $ao['payout'] ?>">
                      <button type="submit" class="btn btn-primary">Accept order</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div>
          <h2 class="h2" style="font-size:20px;margin-bottom:14px;">Your active deliveries</h2>
          <?php if (empty($COURIER_DELIVERIES)): ?>
            <div class="card" style="padding:36px;text-align:center;color:var(--ink-3);">
              <b>No active deliveries.</b>
              <p class="muted" style="margin:4px 0 0;font-size:13px;">Accept an order above to get started.</p>
            </div>
          <?php else: ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Address</th>
                    <th>Distance</th>
                    <th>ETA</th>
                    <th>Payout</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($COURIER_DELIVERIES as $d):
                    if ($d['status'] === 'picked_up') {
                      $spl = 'warn';
                      $label = 'Picked up';
                    } elseif ($d['status'] === 'out_for_delivery') {
                      $spl = 'info';
                      $label = 'On the way';
                    } else {
                      $spl = '';
                      $label = 'Assigned';
                    }
                  ?>
                    <tr>
                      <td><b style="font-family:Fraunces,serif;"><?= $d['id'] ?></b></td>
                      <td class="muted"><?= htmlspecialchars($d['addr']) ?></td>
                      <td><?= $d['dist'] ?></td>
                      <td><?= $d['eta'] ?></td>
                      <td><b style="color:var(--wine);">$<?= number_format($d['payout'], 2) ?></b></td>
                      <td><span class="pill <?= $spl ?>"><?= $label ?></span></td>
                      <td style="text-align:right;">
                        <form method="post" style="display:inline-flex;gap:6px;align-items:center;">
                          <input type="hidden" name="action" value="update_delivery">
                          <input type="hidden" name="delivery_id" value="<?= $d['db_id'] ?>">
                          <select name="status" style="font-size:12.5px;padding:5px 10px;border:1.5px solid var(--line);border-radius:var(--r);background:var(--surface);color:var(--ink);cursor:pointer;">
                            <option value="assigned" <?= $d['status'] === 'assigned'  ? 'selected' : '' ?>>Assigned</option>
                            <option value="picked_up" <?= $d['status'] === 'picked_up' ? 'selected' : '' ?>>Picked up</option>
                            <option value="delivered" <?= $d['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                          </select>
                          <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      <?php elseif ($tab === 'history'): ?>
        <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px;">
          <div>
            <h1 class="h1" style="margin:0;">History</h1>
            <p class="muted" style="margin-top:6px;"><?= count($COURIER_HISTORY) ?> delivered</p>
          </div>
        </div>
        <?php if (empty($COURIER_HISTORY)): ?>
          <div class="card" style="padding:40px;text-align:center;color:var(--ink-3);">No completed deliveries yet.</div>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Delivery</th>
                  <th>Date</th>
                  <th>Payout</th>
                  <th>Rating</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($COURIER_HISTORY as $h): ?>
                  <tr>
                    <td><b style="font-family:Fraunces,serif;"><?= $h['id'] ?></b></td>
                    <td class="muted"><?= $h['date'] ?></td>
                    <td><b style="color:var(--wine);">$<?= number_format($h['payout'], 2) ?></b></td>
                    <td><span style="display:inline-flex;gap:2px;color:var(--coral);"><?php for ($j = 0; $j < min(5, $h['rating']); $j++) echo icon_star(12); ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'earnings'): ?>
        <h1 class="h1" style="margin:0 0 22px;">Earnings</h1>
        <div class="stat-grid">
          <div class="stat">
            <div class="eyebrow">This week</div>
            <div class="stat-val">$<?= number_format($weekly_total, 2) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">This month</div>
            <div class="stat-val">$<?= number_format($monthly_earned, 2) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Deliveries today</div>
            <div class="stat-val"><?= $today_deliveries ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Rating (30d)</div>
            <div class="stat-val"><?= $avg_rating > 0 ? $avg_rating : '—' ?></div>
          </div>
        </div>

        <div class="card" style="padding:24px;margin-bottom:18px;">
          <h3 class="h3" style="margin-top:0;">Earnings, last 12 weeks</h3>
          <svg viewBox="0 0 600 180" style="width:100%;height:180px;margin-top:8px;">
            <?= courier_line_chart($twelve_week) ?>
          </svg>
          <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:var(--ink-4);">
            <span>12w ago</span><span>8w ago</span><span>4w ago</span><span>This week</span>
          </div>
        </div>

        <div class="card" style="padding:22px;">
          <h3 class="h3" style="margin-top:0;">This week, day by day</h3>
          <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;align-items:end;height:100px;margin-top:16px;">
            <?php foreach ($weekly_by_day as $i => $v):
              $bar_h = $max_day > 0 ? max(2, round($v / $max_day * 100)) : 2; ?>
              <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                <div style="font-size:11px;color:var(--wine);font-weight:700;"><?= $v > 0 ? '$' . number_format($v, 0) : '' ?></div>
                <div style="width:100%;flex:1;display:flex;flex-direction:column;justify-content:flex-end;">
                  <div style="width:100%;height:<?= $bar_h ?>%;background:<?= $v > 0 ? 'var(--wine)' : 'var(--bg-2)' ?>;border-radius:6px;opacity:<?= $i === $today_dow ? 1 : .8 ?>;min-height:4px;"></div>
                </div>
                <div style="font-size:12px;color:var(--ink-3);font-weight:600;"><?= ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$i] ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      <?php elseif ($tab === 'profile'): ?>
        <h1 class="h1" style="margin:0 0 22px;">Profile</h1>
        <div style="display:flex;gap:18px;align-items:center;margin-bottom:24px;">
          <div class="seller-avatar" style="width:80px;height:80px;font-size:30px;margin-bottom:0;border-width:0;background:var(--teal);color:var(--lime);"><?= $ini ?></div>
          <div>
            <h2 class="h2" style="margin:0;"><?= htmlspecialchars($u['name']) ?></h2>
            <p class="muted" style="margin:4px 0;">Courier · <?= ucfirst($courier_row['status'] ?? 'active') ?></p>
            <div style="display:flex;gap:6px;margin-top:6px;">
              <?php if (($courier_row['status'] ?? '') !== 'pending'): ?><span class="pill success">Verified</span><?php endif; ?>
              <?php if (!empty($courier_row['city'])): ?><span class="pill info"><?= htmlspecialchars($courier_row['city']) ?></span><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="card" style="padding:24px;">
          <h3 class="h3" style="margin-top:0;">Personal info</h3>
          <form method="post">
            <input type="hidden" name="action" value="save_profile">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
              <div class="field"><label>Full name</label><input name="name" value="<?= htmlspecialchars($courier_row['name'] ?? $u['name']) ?>" required></div>
              <div class="field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($courier_row['email'] ?? $u['email']) ?>" required></div>
              <div class="field"><label>Phone</label><input name="phone" value="<?= htmlspecialchars($courier_row['phone'] ?? '') ?>" placeholder="+1 415 …"></div>
              <div class="field"><label>City</label><input name="city" value="<?= htmlspecialchars($courier_row['city'] ?? '') ?>" placeholder="Portland, OR"></div>
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:18px;">
              <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php include '_partials/footer.php'; ?>
