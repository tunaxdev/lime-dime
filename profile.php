<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

require_login('/limedime/login.php');

$u   = current_user();
$uid = (int)$u['id'];

$tab = $_GET['tab'] ?? 'account';
$allowed_tabs = ['account', 'addresses', 'payment', 'favorites'];
if (!in_array($tab, $allowed_tabs)) $tab = 'account';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_profile') {
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name']  ?? '');
        $email = trim($_POST['email']      ?? '');
        $phone = trim($_POST['phone']      ?? '');
        if (!$first || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: /limedime/profile.php?tab=account&err=input');
            exit;
        }
        if ($email !== $u['email']) {
            $se = mysqli_real_escape_string($conn, $email);
            $c  = mysqli_query($conn, "SELECT id FROM users WHERE email='$se' AND id!=$uid");
            if ($c && mysqli_fetch_assoc($c)) {
                header('Location: /limedime/profile.php?tab=account&err=email');
                exit;
            }
        }
        $name        = trim("$first $last");
        $safe_name   = mysqli_real_escape_string($conn, $name);
        $safe_email  = mysqli_real_escape_string($conn, $email);
        $safe_phone  = mysqli_real_escape_string($conn, $phone);
        mysqli_query($conn, "UPDATE users SET name='$safe_name', email='$safe_email', phone='$safe_phone' WHERE id=$uid");
        $_SESSION['user']['name']  = $name;
        $_SESSION['user']['email'] = $email;
        header('Location: /limedime/profile.php?tab=account&ok=saved');
        exit;
    } elseif ($action === 'upload_avatar') {
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['avatar_file'];
            $mime    = mime_content_type($file['tmp_name']);
            $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            if (isset($ext_map[$mime]) && $file['size'] <= 2 * 1024 * 1024) {
                $dir = __DIR__ . '/assets/img/avatars/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fn = $uid . '.' . $ext_map[$mime];
                move_uploaded_file($file['tmp_name'], $dir . $fn);
                $safe_fn = mysqli_real_escape_string($conn, $fn);
                mysqli_query($conn, "UPDATE users SET avatar='$safe_fn' WHERE id=$uid");
            }
        }
        header('Location: /limedime/profile.php?tab=account&ok=avatar');
        exit;
    } elseif ($action === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $pr       = mysqli_query($conn, "SELECT password FROM users WHERE id=$uid");
        $hash     = $pr ? (mysqli_fetch_assoc($pr)['password'] ?? '') : '';
        if (!password_verify($current, $hash)) {
            header('Location: /limedime/profile.php?tab=account&err=badpw');
            exit;
        }
        if (strlen($new_pass) < 8) {
            header('Location: /limedime/profile.php?tab=account&err=short');
            exit;
        }
        if ($new_pass !== $confirm) {
            header('Location: /limedime/profile.php?tab=account&err=mismatch');
            exit;
        }
        $new_hash  = password_hash($new_pass, PASSWORD_BCRYPT);
        $safe_hash = mysqli_real_escape_string($conn, $new_hash);
        mysqli_query($conn, "UPDATE users SET password='$safe_hash' WHERE id=$uid");
        header('Location: /limedime/profile.php?tab=account&ok=pwchanged');
        exit;
    } elseif ($action === 'add_address') {
        $label = trim($_POST['label'] ?? 'Home');
        $line1 = trim($_POST['line1'] ?? '');
        $city  = trim($_POST['city']  ?? '');
        $state = trim($_POST['state'] ?? '');
        $zip   = trim($_POST['zip']   ?? '');
        if ($line1 && $city) {
            $cnt_r   = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM addresses WHERE user_id=$uid");
            $cnt_row = $cnt_r ? mysqli_fetch_assoc($cnt_r) : ['cnt' => 1];
            $def     = (int)$cnt_row['cnt'] == 0 ? 1 : 0;
            $sl      = mysqli_real_escape_string($conn, $label);
            $s1      = mysqli_real_escape_string($conn, $line1);
            $sc      = mysqli_real_escape_string($conn, $city);
            $ss      = mysqli_real_escape_string($conn, $state);
            $sz      = mysqli_real_escape_string($conn, $zip);
            mysqli_query($conn, "INSERT INTO addresses (user_id, label, line1, city, state, zip, is_default) VALUES ($uid, '$sl', '$s1', '$sc', '$ss', '$sz', $def)");
        }
        header('Location: /limedime/profile.php?tab=addresses&ok=1');
        exit;
    } elseif ($action === 'delete_address') {
        $aid = (int)($_POST['address_id'] ?? 0);
        mysqli_query($conn, "DELETE FROM addresses WHERE id=$aid AND user_id=$uid");
        header('Location: /limedime/profile.php?tab=addresses');
        exit;
    } elseif ($action === 'set_default_address') {
        $aid = (int)($_POST['address_id'] ?? 0);
        mysqli_query($conn, "UPDATE addresses SET is_default=0 WHERE user_id=$uid");
        mysqli_query($conn, "UPDATE addresses SET is_default=1 WHERE id=$aid AND user_id=$uid");
        header('Location: /limedime/profile.php?tab=addresses');
        exit;
    } elseif ($action === 'add_card') {
        $brand   = in_array($_POST['brand'] ?? '', ['Visa', 'Mastercard', 'Amex']) ? $_POST['brand'] : 'Visa';
        $number  = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
        $expiry  = trim($_POST['expiry'] ?? '');
        $name_on = trim($_POST['name_on_card'] ?? '');
        $last4   = strlen($number) >= 4 ? substr($number, -4) : '';
        $parts   = array_map('intval', array_pad(explode('/', $expiry), 2, 0));
        $em      = $parts[0];
        $ey      = $parts[1] < 100 ? $parts[1] + 2000 : $parts[1];
        if ($last4 && $em >= 1 && $em <= 12 && $ey >= (int)date('Y')) {
            $cnt_r   = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM payment_cards WHERE user_id=$uid");
            $cnt_row = $cnt_r ? mysqli_fetch_assoc($cnt_r) : ['cnt' => 1];
            $def     = (int)$cnt_row['cnt'] == 0 ? 1 : 0;
            $sb      = mysqli_real_escape_string($conn, $brand);
            $sl4     = mysqli_real_escape_string($conn, $last4);
            $sno     = mysqli_real_escape_string($conn, $name_on);
            mysqli_query($conn, "INSERT INTO payment_cards (user_id, brand, last4, exp_month, exp_year, name_on_card, is_default) VALUES ($uid, '$sb', '$sl4', $em, $ey, '$sno', $def)");
        }
        header('Location: /limedime/profile.php?tab=payment&ok=1');
        exit;
    } elseif ($action === 'delete_card') {
        $cid = (int)($_POST['card_id'] ?? 0);
        mysqli_query($conn, "DELETE FROM payment_cards WHERE id=$cid AND user_id=$uid");
        header('Location: /limedime/profile.php?tab=payment');
        exit;
    } elseif ($action === 'set_default_card') {
        $cid = (int)($_POST['card_id'] ?? 0);
        mysqli_query($conn, "UPDATE payment_cards SET is_default=0 WHERE user_id=$uid");
        mysqli_query($conn, "UPDATE payment_cards SET is_default=1 WHERE id=$cid AND user_id=$uid");
        header('Location: /limedime/profile.php?tab=payment');
        exit;
    }
}

$name_parts  = explode(' ', $u['name'], 2);
$first_name  = $name_parts[0];
$last_name   = $name_parts[1] ?? '';
$initials    = strtoupper(mb_substr($first_name, 0, 1) . mb_substr($last_name, 0, 1));

$s = mysqli_query($conn, "SELECT created_at, phone, avatar FROM users WHERE id=$uid");
$user_row    = $s ? mysqli_fetch_assoc($s) : null;
$user_phone  = $user_row['phone']  ?? '';
$user_avatar = $user_row['avatar'] ?? null;
$member_since = $user_row ? date('M Y', strtotime($user_row['created_at'])) : '';
$order_count  = count($ORDERS);
$total_spent  = array_sum(array_column($ORDERS, 'total'));

$addresses = [];
$ar = mysqli_query($conn, "SELECT * FROM addresses WHERE user_id=$uid ORDER BY is_default DESC, id ASC");
if ($ar) {
    while ($row = mysqli_fetch_assoc($ar)) $addresses[] = $row;
}

$cards = [];
$cr = mysqli_query($conn, "SELECT * FROM payment_cards WHERE user_id=$uid ORDER BY is_default DESC, id ASC");
if ($cr) {
    while ($row = mysqli_fetch_assoc($cr)) $cards[] = $row;
}

$page_title   = 'Profile';
$active_route = 'profile';
$show_catbar  = false;
include '_partials/header.php';

$sidebar_items = [
    ['id' => 'account',   'label' => 'Account',    'icon' => icon_user(18)],
    ['id' => 'addresses', 'label' => 'Addresses',  'icon' => icon_pin(18)],
    ['id' => 'payment',   'label' => 'Payment',    'icon' => icon_card(18)],
    ['id' => 'orders',    'label' => 'Orders',     'icon' => icon_package(18), 'count' => (string)$order_count, 'href' => '/limedime/orders.php'],
    ['id' => 'favorites', 'label' => 'Favourites', 'icon' => icon_heart(18)],
];
?>

<div class="container">
  <div class="sidebar-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <?php if ($user_avatar): ?>
          <img src="/limedime/assets/img/avatars/<?= htmlspecialchars($user_avatar) ?>" style="width:32px;height:32px;border-radius:999px;object-fit:cover;flex-shrink:0;">
        <?php else: ?>
          <div style="width:32px;height:32px;border-radius:999px;background:var(--coral);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--surface);flex-shrink:0;"><?= htmlspecialchars($initials) ?></div>
        <?php endif; ?>
        <div style="line-height:1.1;min-width:0;">
          <b style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($u['name']) ?></b>
          <div class="role"><?= ucfirst($u['role']) ?><?= $member_since ? ' · since ' . $member_since : '' ?></div>
        </div>
      </div>
      <nav>
        <?php foreach ($sidebar_items as $item):
          $href = $item['href'] ?? '/limedime/profile.php?tab=' . $item['id'];
          $is_active = (!isset($item['href']) && $tab === $item['id']);
        ?>
          <a href="<?= $href ?>" class="side-link <?= $is_active ? 'active' : '' ?>">
            <?= $item['icon'] ?><span><?= $item['label'] ?></span>
            <?php if (isset($item['count'])): ?><span class="count"><?= $item['count'] ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div style="margin-top:18px;padding-top:14px;border-top:1px dashed var(--line);">
        <a href="/limedime/logout.php" class="side-link"><?= icon_logout(18) ?> <span>Sign out</span></a>
      </div>
    </aside>

    <main>
      <?php
      $ok  = $_GET['ok']  ?? '';
      $err = $_GET['err'] ?? '';
      ?>

      <?php if ($tab === 'account'): ?>
        <div style="display:flex;align-items:center;gap:18px;margin-bottom:28px;">
          <?php if ($user_avatar): ?>
            <img src="/limedime/assets/img/avatars/<?= htmlspecialchars($user_avatar) ?>" style="width:72px;height:72px;border-radius:999px;object-fit:cover;flex-shrink:0;">
          <?php else: ?>
            <div class="seller-avatar" style="width:72px;height:72px;font-size:28px;margin-bottom:0;border-width:0;background:var(--coral);color:var(--surface);"><?= htmlspecialchars($initials) ?></div>
          <?php endif; ?>
          <div>
            <h1 class="h1" style="margin:0;font-size:36px;"><?= htmlspecialchars($u['name']) ?></h1>
            <p class="muted" style="margin:4px 0 0;"><?= htmlspecialchars($u['email']) ?><?= $member_since ? ' · Member since ' . $member_since : '' ?></p>
          </div>
          <form method="post" enctype="multipart/form-data" id="avatar-form" style="margin-left:auto;">
            <input type="hidden" name="action" value="upload_avatar">
            <input type="file" name="avatar_file" id="avatar-input" accept="image/*" style="display:none;" onchange="this.form.submit();">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('avatar-input').click();">Edit profile picture</button>
          </form>
        </div>

        <?php
        $pw_errors = ['badpw' => 'Current password is incorrect.', 'short' => 'New password must be at least 6 characters.', 'mismatch' => 'Passwords do not match.'];
        ?>
        <?php if ($ok === 'saved'): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Profile updated successfully.</div>
        <?php elseif ($ok === 'avatar'): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Profile picture updated.</div>
        <?php elseif ($ok === 'pwchanged'): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Password changed successfully.</div>
        <?php elseif ($err && isset($pw_errors[$err])): ?>
          <div style="background:rgba(142,59,70,.1);border:1px solid var(--wine);border-radius:var(--r);padding:12px 16px;color:var(--wine);font-size:13.5px;margin-bottom:16px;"><?= $pw_errors[$err] ?></div>
        <?php elseif ($err): ?>
          <div style="background:rgba(142,59,70,.1);border:1px solid var(--wine);border-radius:var(--r);padding:12px 16px;color:var(--wine);font-size:13.5px;margin-bottom:16px;">
            <?= $err === 'email' ? 'That email is already in use by another account.' : 'Please fill in all required fields correctly.' ?>
          </div>
        <?php endif; ?>

        <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);">
          <div class="stat">
            <div class="eyebrow">Lifetime orders</div>
            <div class="stat-val"><?= $order_count ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Addresses saved</div>
            <div class="stat-val"><?= count($addresses) ?></div>
          </div>
          <div class="stat">
            <div class="eyebrow">Total spent</div>
            <div class="stat-val">$<?= number_format($total_spent, 2) ?></div>
          </div>
        </div>

        <div class="card" style="padding:24px;margin-top:24px;">
          <h3 class="h3" style="margin-top:0;">Account details</h3>
          <form method="post">
            <input type="hidden" name="action" value="save_profile">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
              <div class="field"><label>First name</label><input name="first_name" value="<?= htmlspecialchars($first_name) ?>" required></div>
              <div class="field"><label>Last name</label><input name="last_name" value="<?= htmlspecialchars($last_name) ?>"></div>
              <div class="field" style="grid-column:1/-1;"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required></div>
              <div class="field" style="grid-column:1/-1;"><label>Phone</label><input name="phone" value="<?= htmlspecialchars($user_phone) ?>" placeholder="Not set"></div>
              <div class="field"><label>Preferred currency</label><input value="USD ($)" disabled></div>
              <div class="field"><label>Language</label><input value="English (US)" disabled></div>
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:18px;">
              <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
          </form>
        </div>

        <div class="card" style="padding:24px;margin-top:18px;">
          <h3 class="h3" style="margin-top:0;">Change password</h3>
          <form method="post">
            <input type="hidden" name="action" value="change_password">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
              <div class="field" style="grid-column:1/-1;"><label>Current password</label><input type="password" name="current_password" placeholder="••••••••" required></div>
              <div class="field"><label>New password</label><input type="password" name="new_password" placeholder="At least 8 characters" required minlength="8"></div>
              <div class="field"><label>Confirm new password</label><input type="password" name="confirm_password" placeholder="Repeat new password" required></div>
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:18px;">
              <button type="submit" class="btn btn-primary">Update password</button>
            </div>
          </form>
        </div>

      <?php elseif ($tab === 'addresses'): ?>
        <div class="section-head">
          <h2 class="h2" style="margin:0;">Addresses</h2>
          <button class="btn btn-primary btn-sm" onclick="openModal('addr-modal')"><?= icon_plus(14) ?> Add address</button>
        </div>
        <?php if (isset($_GET['ok'])): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Address saved.</div>
        <?php endif; ?>

        <?php if (empty($addresses)): ?>
          <div class="card" style="padding:40px;text-align:center;color:var(--ink-3);">
            <p style="margin:0 0 16px;">No addresses saved yet.</p>
            <button class="btn btn-primary btn-sm" onclick="openModal('addr-modal')">Add your first address</button>
          </div>
        <?php else: ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <?php foreach ($addresses as $addr): ?>
              <div class="card" style="padding:20px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                  <b><?= htmlspecialchars($addr['label']) ?></b>
                  <?php if ($addr['is_default']): ?><span class="pill warn">Default</span><?php endif; ?>
                </div>
                <p class="muted" style="margin:4px 0 14px;line-height:1.5;font-size:13.5px;">
                  <?= htmlspecialchars($addr['line1']) ?><br>
                  <?= htmlspecialchars(trim($addr['city'] . ', ' . $addr['state'] . ' ' . $addr['zip'])) ?>
                </p>
                <div style="display:flex;gap:8px;">
                  <?php if (!$addr['is_default']): ?>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="action" value="set_default_address">
                      <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                      <button class="btn btn-ghost btn-sm">Make default</button>
                    </form>
                  <?php endif; ?>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Remove this address?')">
                    <input type="hidden" name="action" value="delete_address">
                    <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                    <button class="btn btn-ghost btn-sm" style="color:var(--wine);">Remove</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div id="addr-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
          <div style="background:var(--surface);border-radius:var(--r-lg);padding:28px;width:480px;max-width:calc(100vw - 40px);box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <h3 class="h3" style="margin:0 0 20px;">Add address</h3>
            <form method="post">
              <input type="hidden" name="action" value="add_address">
              <div class="field" style="margin-bottom:12px;">
                <label>Label</label>
                <select name="label" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
                  <option>Home</option>
                  <option>Work</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="field" style="margin-bottom:12px;"><label>Address line 1</label><input name="line1" placeholder="37 Almond Street, Apt 4B" required></div>
              <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:10px;margin-bottom:4px;">
                <div class="field"><label>City</label><input name="city" placeholder="Portland" required></div>
                <div class="field"><label>State</label><input name="state" placeholder="OR"></div>
                <div class="field"><label>ZIP</label><input name="zip" placeholder="97214"></div>
              </div>
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addr-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save address</button>
              </div>
            </form>
          </div>
        </div>

      <?php elseif ($tab === 'payment'): ?>
        <div class="section-head">
          <h2 class="h2" style="margin:0;">Payment methods</h2>
          <button class="btn btn-primary btn-sm" onclick="openModal('card-modal')"><?= icon_plus(14) ?> Add card</button>
        </div>
        <?php if (isset($_GET['ok'])): ?>
          <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:12px 16px;color:#3a7a28;font-size:13.5px;margin-bottom:16px;">Card added.</div>
        <?php endif; ?>

        <?php if (empty($cards)): ?>
          <div class="card" style="padding:40px;text-align:center;color:var(--ink-3);">
            <p style="margin:0 0 16px;">No payment methods saved yet.</p>
            <button class="btn btn-primary btn-sm" onclick="openModal('card-modal')">Add a card</button>
          </div>
        <?php else: ?>
          <?php $brand_colors = ['Visa' => 'var(--wine)', 'Mastercard' => 'var(--teal)', 'Amex' => '#4C86A8']; ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <?php foreach ($cards as $card):
              $bcolor = $brand_colors[$card['brand']] ?? 'var(--wine)';
            ?>
              <div style="background:<?= $bcolor ?>;border-radius:var(--r-lg);padding:22px;color:var(--lime-soft);position:relative;overflow:hidden;">
                <div style="position:absolute;right:-30px;bottom:-30px;width:130px;height:130px;border-radius:999px;background:rgba(225,221,143,.12);pointer-events:none;"></div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span style="font-family:Fraunces,serif;font-size:16px;font-weight:700;"><?= htmlspecialchars($card['brand']) ?></span>
                  <?php if ($card['is_default']): ?><span class="pill warn" style="background:var(--lime);">Default</span><?php endif; ?>
                </div>
                <div style="font-family:Fraunces,serif;font-size:22px;letter-spacing:0.04em;margin:22px 0 14px;font-weight:600;color:var(--lime);">•••• <?= htmlspecialchars($card['last4']) ?></div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:rgba(255,252,245,.7);">
                  <span><?= htmlspecialchars($card['name_on_card'] ?: $u['name']) ?></span>
                  <span>Exp <?= str_pad($card['exp_month'], 2, '0', STR_PAD_LEFT) ?>/<?= substr($card['exp_year'], 2) ?></span>
                </div>
                <div style="display:flex;gap:8px;margin-top:14px;">
                  <?php if (!$card['is_default']): ?>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="action" value="set_default_card">
                      <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                      <button class="btn btn-sm" style="background:rgba(255,252,245,.15);color:var(--lime-soft);border-color:rgba(255,252,245,.25);">Make default</button>
                    </form>
                  <?php endif; ?>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Remove this card?')">
                    <input type="hidden" name="action" value="delete_card">
                    <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                    <button class="btn btn-sm" style="background:rgba(255,252,245,.1);color:var(--coral-soft);border-color:rgba(255,252,245,.2);">Remove</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div id="card-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
          <div style="background:var(--surface);border-radius:var(--r-lg);padding:28px;width:420px;max-width:calc(100vw - 40px);box-shadow:0 20px 60px rgba(0,0,0,.2);">
            <h3 class="h3" style="margin:0 0 20px;">Add payment card</h3>
            <form method="post">
              <input type="hidden" name="action" value="add_card">
              <div class="field" style="margin-bottom:12px;">
                <label>Card brand</label>
                <select name="brand" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;">
                  <option>Visa</option>
                  <option>Mastercard</option>
                  <option>Amex</option>
                </select>
              </div>
              <div class="field" style="margin-bottom:12px;"><label>Card number</label><input name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" oninput="this.value=this.value.replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim()" required></div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;">
                <div class="field"><label>Expiry (MM/YY)</label><input name="expiry" placeholder="04/28" maxlength="5" oninput="if(this.value.length===2&&!this.value.includes('/'))this.value+='/'" required></div>
                <div class="field"><label>Name on card</label><input name="name_on_card" placeholder="Jane Doe" required></div>
              </div>
              <p class="muted" style="font-size:12px;margin:8px 0 16px;">Demo only — no real payment is processed.</p>
              <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" class="btn btn-ghost" onclick="closeModal('card-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add card</button>
              </div>
            </form>
          </div>
        </div>

      <?php elseif ($tab === 'favorites'): ?>
        <h2 class="h2" style="margin:0 0 18px;">Favourites</h2>
        <div id="fav-grid" class="grid grid-products"></div>
        <div id="fav-empty" class="empty-state" style="display:none;">
          <h3 class="h3" style="margin:0 0 6px;">No favourites yet</h3>
          <p class="muted" style="margin:0;">Tap the heart on any product to save it here.</p>
        </div>
        <script>
          <?php
          $fav_products_js = [];
          foreach ($PRODUCTS as $p) {
            $fav_products_js[] = [
              'id'     => $p['id'],
              'name'   => $p['name'],
              'seller' => $p['seller'],
              'cat'    => $p['cat'],
              'price'  => $p['price'],
              'rating' => $p['rating'],
              'tag'    => $p['tag'],
              'qty'    => $p['quantity'],
              'img'    => $p['product_image'] ?? null,
            ];
          }
          ?>
          var _FAV_CAT_COLORS = <?= json_encode(array_column($CATEGORIES, 'color', 'id')) ?>;
          var _FAV_PRODUCTS = <?= json_encode($fav_products_js) ?>;

          function renderFavorites() {
            var favs = JSON.parse(localStorage.getItem('ld_faves') || '[]');
            var grid = document.getElementById('fav-grid');
            var empty = document.getElementById('fav-empty');
            var items = _FAV_PRODUCTS.filter(function(p) {
              return favs.indexOf(p.id) !== -1;
            });
            if (items.length === 0) {
              grid.style.display = 'none';
              empty.style.display = '';
              return;
            }
            grid.style.display = '';
            empty.style.display = 'none';
            grid.innerHTML = items.map(function(p) {
              var color = _FAV_CAT_COLORS[p.cat] || '#2A1A1D';
              var badge = p.qty <= 0 ?
                '<span class="product-tag" style="background:var(--ink-3);">Sold out</span>' :
                (p.tag ? '<span class="product-tag">' + p.tag + '</span>' : '');
              var thumb = p.img ?
                '<img src="/limedime/assets/img/products/' + p.img + '" style="width:100%;height:100%;object-fit:cover;display:block;" alt="">' :
                '';
              return '<a href="/limedime/product.php?id=' + p.id + '" class="product-card card" style="text-decoration:none;">' +
                '<div class="product-thumb">' + thumb + badge +
                '<button class="product-fav on" data-fav="' + p.id + '" onclick="event.preventDefault();toggleFav(\'' + p.id + '\');renderFavorites();"><?= icon_heart(16) ?></button>' +
                '</div><div class="product-body">' +
                '<div class="product-seller"><span style="width:6px;height:6px;border-radius:999px;background:' + color + ';"></span><span>' + p.seller + '</span></div>' +
                '<div class="product-name">' + p.name + '</div>' +
                '<div class="product-meta"><span class="product-price">$' + p.price.toFixed(2) + '</span>' +
                '<span class="product-rating"><?= icon_star(12) ?> ' + p.rating + '</span></div>' +
                '</div></a>';
            }).join('');
          }
          renderFavorites();
        </script>
      <?php endif; ?>
    </main>
  </div>
</div>

<script>
  function openModal(id) {
    document.getElementById(id).style.display = 'flex';
  }

  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
  }
  document.addEventListener('click', function(e) {
    ['addr-modal', 'card-modal'].forEach(function(id) {
      const m = document.getElementById(id);
      if (m && e.target === m) m.style.display = 'none';
    });
  });
</script>

<?php include '_partials/footer.php'; ?>
