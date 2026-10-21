<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_cancel') {
  if (!isset($u)) {
    header('Location: /limedime/login.php');
    exit;
  }
    $oid  = (int)($_POST['order_id'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    if ($oid > 0) {
        $uid = (int)$u['id'];
        $chk = mysqli_query($conn, "SELECT id FROM orders WHERE id=$oid AND customer_id=$uid AND status IN ('pending','accepted','packed')");
        if ($chk && mysqli_fetch_assoc($chk)) {
            $note_val = $note ? "'" . mysqli_real_escape_string($conn, $note) . "'" : 'NULL';
            mysqli_query($conn, "UPDATE orders SET status='cancel_requested', cancel_note=$note_val WHERE id=$oid");
        }
    }
  header('Location: /limedime/orders.php?ok=cancel_requested');
  exit;
}

$filter = $_GET['status'] ?? 'all';


$detail_order = null;
$detail_lines = [];
$detail_id    = (int)($_GET['detail'] ?? 0);
if ($detail_id > 0) {
    $dr = mysqli_query($conn, "SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON u.id=o.customer_id WHERE o.id=$detail_id");
    if ($dr) $detail_order = mysqli_fetch_assoc($dr) ?: null;

    $lr = mysqli_query($conn, "SELECT ol.*, p.name AS product_name, p.price AS unit_price, p.illu_type, p.palette_1, p.palette_2, p.product_image FROM order_line ol JOIN products p ON p.id = ol.product_id WHERE ol.order_id = $detail_id");
    if ($lr) {
        while ($lrow = mysqli_fetch_assoc($lr)) $detail_lines[] = $lrow;
    }
}

$page_title   = 'My orders';
$active_route = 'orders';
$show_catbar  = false;
include '_partials/header.php';

$filtered = [];
foreach ($ORDERS as $o) {
  if ($filter === 'all' || strtolower($o['status']) === $filter) $filtered[] = $o;
}

function status_pill($s)
{
  if ($s === 'Delivered')         return '<span class="pill success">' . icon_check(11) . ' ' . $s . '</span>';
  if ($s === 'Out For Delivery')  return '<span class="pill warn">' . icon_bike(11) . ' ' . $s . '</span>';
  if ($s === 'Cancelled')         return '<span class="pill danger">' . $s . '</span>';
  if ($s === 'Pending')           return '<span class="pill warn">' . $s . '</span>';
  if ($s === 'Cancel Requested')  return '<span class="pill danger" style="opacity:.8;">⏳ Awaiting approval</span>';
  return '<span class="pill">' . $s . '</span>';
}
?>

<div class="container page">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:16px;">
    <div>
      <h1 class="h1" style="margin:0;">My orders</h1>
      <p class="muted" style="margin-top:6px;margin-bottom:0;"><?= count($ORDERS) ?> orders</p>
    </div>
    <div class="tabs">
      <?php foreach (['all' => 'All', 'out for delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'cancel requested' => 'Cancel pending', 'cancelled' => 'Cancelled'] as $val => $label): ?>
        <a href="/limedime/orders.php?status=<?= urlencode($val) ?>"
          class="tab <?= $filter === $val ? 'active' : '' ?>" style="text-decoration:none;"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Active order tracker -->
  <?php
  $active_order = null;
  foreach ($ORDERS as $o) {
    if ($o['status'] === 'Out For Delivery') {
      $active_order = $o;
      break;
    }
  }
  if ($active_order && ($filter === 'all' || $filter === 'out for delivery')): ?>
    <div class="card" style="padding:24px;margin-bottom:24px;background:var(--surface-2);border-color:var(--lime-deep);">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
        <div>
          <div class="eyebrow">Out for delivery</div>
          <h3 class="h3" style="margin:4px 0 0;">Order <?= $active_order['id'] ?> from <?= htmlspecialchars($active_order['seller']) ?></h3>
        </div>
      </div>
      <div class="tracker" style="margin-bottom:0;">
        <?php
        $active_step = $active_order['step'] ?? 1;
        $step_labels = ['Placed', 'Accepted by shop', 'Courier assigned', 'In transit', 'Delivered'];
        foreach ($step_labels as $i => $lbl):
          $step_num = $i + 1;
          $done   = $step_num < $active_step;
          $is_now = $step_num == $active_step;
        ?>
          <div class="tracker-step <?= $done ? 'done' : ($is_now ? 'now' : '') ?>">
            <div class="dot"><?= $done ? icon_check(13) : $step_num ?></div>
            <div class="lbl"><?= $lbl ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (($_GET['ok'] ?? '') === 'cancel_requested'): ?>
    <div style="background:rgba(142,59,70,.08);border:1px solid var(--wine);border-radius:var(--r);padding:12px 16px;color:var(--wine);font-size:13.5px;margin-bottom:18px;">
      Cancellation request sent — the seller will review it shortly.
    </div>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Order</th>
          <th>Date</th>
          <th>Seller</th>
          <th>Items</th>
          <th>Total</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($filtered) === 0): ?>
          <tr>
            <td colspan="7" class="muted" style="text-align:center;padding:32px;">No orders match this filter.</td>
          </tr>
          <?php else: foreach ($filtered as $o): ?>
            <?php $raw_status = strtolower(str_replace(' ', '_', $o['status'])); ?>
            <tr>
              <td><b style="font-family:Fraunces,serif;"><?= $o['id'] ?></b></td>
              <td class="muted"><?= $o['date'] ?></td>
              <td><?= htmlspecialchars($o['seller']) ?></td>
              <td class="muted"><?= $o['items'] ?> <?= $o['items'] === 1 ? 'item' : 'items' ?></td>
              <td><b style="color:var(--wine);">$<?= number_format($o['total'], 2) ?></b></td>
              <td><?= status_pill($o['status']) ?></td>
              <td style="text-align:right;display:flex;gap:6px;justify-content:flex-end;">
                <a href="/limedime/orders.php?detail=<?= $o['db_id'] ?>&status=<?= urlencode($filter) ?>"
                  class="btn btn-ghost btn-sm">Details</a>
                <?php if (in_array($raw_status, ['pending', 'accepted', 'packed'])): ?>
                  <button class="btn btn-ghost btn-sm" style="color:var(--wine);border-color:var(--wine);"
                    onclick="openCancelModal(<?= $o['db_id'] ?>, '<?= $o['id'] ?>')">Cancel</button>
                <?php endif; ?>
              </td>
            </tr>
        <?php endforeach;
        endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($detail_order): ?>
  <!-- ── Order detail modal ───────────────────────────────────────────────── -->
  <div id="order-detail-modal" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;">
    <div style="background:var(--surface);border-radius:var(--r-lg);padding:28px;width:560px;max-width:calc(100vw - 32px);box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:88vh;overflow-y:auto;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div>
          <div class="eyebrow">Order details</div>
          <h3 class="h3" style="margin:4px 0 0;">LD-<?= str_pad($detail_order['id'], 4, '0', STR_PAD_LEFT) ?></h3>
        </div>
        <a href="/limedime/orders.php?status=<?= urlencode($filter) ?>" class="icon-btn">✕</a>
      </div>

      <!-- Status + date -->
      <div style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
        <?php
        $st  = $detail_order['status'];
        $spl = ['pending' => 'warn', 'accepted' => 'info', 'packed' => 'info', 'out_for_delivery' => 'warn', 'delivered' => 'success', 'cancelled' => 'danger', 'cancel_requested' => 'danger'][$st] ?? '';
        ?>
        <span class="pill <?= $spl ?>"><?= ucwords(str_replace('_', ' ', $st)) ?></span>
        <span class="pill" style="background:var(--bg-2);"><?= date('M j, Y · H:i', strtotime($detail_order['order_date'])) ?></span>
        <?php if (!empty($detail_order['delivery_address'])): ?>
          <span class="pill" style="background:var(--bg-2);"><?= htmlspecialchars($detail_order['delivery_address']) ?></span>
        <?php endif; ?>
      </div>

      <!-- Line items -->
      <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px;">
        <?php foreach ($detail_lines as $line):
          $p_img = $line['product_image'] ?? null;
        ?>
          <div style="display:flex;align-items:center;gap:14px;padding:12px;background:var(--surface-2);border-radius:var(--r);border:1px solid var(--line);">
            <div style="width:52px;height:52px;border-radius:10px;overflow:hidden;flex-shrink:0;background:var(--bg-2);">
              <?php if ($p_img): ?>
                <img src="/limedime/assets/img/products/<?= htmlspecialchars($p_img) ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                <?= product_illu($line['illu_type'] ?? 'jar', [$line['palette_1'] ?? '#E0777D', $line['palette_2'] ?? '#8E3B46']) ?>
              <?php endif; ?>
            </div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($line['product_name']) ?></div>
              <div class="muted" style="font-size:12.5px;">Qty <?= (int)$line['amount'] ?> · $<?= number_format((float)($line['unit_price'] ?? ($line['amount'] > 0 ? $line['total_price'] / $line['amount'] : 0)), 2) ?> each</div>
            </div>
            <b style="font-family:Fraunces,serif;font-size:18px;color:var(--wine);">$<?= number_format((float)$line['total_price'], 2) ?></b>
          </div>
        <?php endforeach; ?>
        <?php if (empty($detail_lines)): ?>
          <p class="muted" style="font-size:13px;">No line items found.</p>
        <?php endif; ?>
      </div>

      <!-- Total -->
      <div style="display:flex;justify-content:space-between;padding:14px;background:var(--surface-2);border-radius:var(--r);border:1px solid var(--line);">
        <b>Order total</b>
        <b style="font-family:Fraunces,serif;font-size:22px;color:var(--wine);">$<?= number_format((float)$detail_order['total'], 2) ?></b>
      </div>

      <div style="display:flex;justify-content:flex-end;margin-top:18px;">
        <a href="/limedime/orders.php?status=<?= urlencode($filter) ?>" class="btn btn-ghost">Close</a>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Cancel request modal -->
<div id="cancel-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
  <div style="background:var(--surface);border-radius:var(--r-lg);padding:28px;width:480px;max-width:calc(100vw - 32px);box-shadow:0 20px 60px rgba(0,0,0,.2);">
    <h3 class="h3" style="margin:0 0 6px;">Request cancellation</h3>
    <p class="muted" style="margin:0 0 20px;font-size:13.5px;">Your request will be sent to the seller. They need to approve it before the order is cancelled.</p>
    <form method="post" action="/limedime/orders.php">
      <input type="hidden" name="action" value="request_cancel">
      <input type="hidden" name="order_id" id="cancel-order-id">
      <div class="field" style="margin-bottom:18px;">
        <label>Reason <span class="muted">(optional)</span></label>
        <textarea name="note" rows="3" placeholder="Let the seller know why you want to cancel…" style="width:100%;resize:vertical;"></textarea>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" class="btn btn-ghost" onclick="closeCancelModal()">Keep order</button>
        <button type="submit" class="btn btn-primary" style="background:var(--wine);border-color:var(--wine);">Send request</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openCancelModal(dbId, label) {
    document.getElementById('cancel-order-id').value = dbId;
    var m = document.getElementById('cancel-modal');
    m.style.display = 'flex';
  }

  function closeCancelModal() {
    document.getElementById('cancel-modal').style.display = 'none';
  }
  document.getElementById('cancel-modal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
  });
</script>

<?php include '_partials/footer.php'; ?>