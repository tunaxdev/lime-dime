<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

$u = current_user();

$user_row = [];
if ($u) {
    $uid = (int)$u['id'];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $uid");
    if ($result) {
        $user_row = mysqli_fetch_assoc($result) ?: [];
    }
}

$name_parts = explode(' ', $u['name'] ?? '', 2);
$pre_first  = htmlspecialchars($name_parts[0] ?? '');
$pre_last   = htmlspecialchars($name_parts[1] ?? '');
$pre_email  = htmlspecialchars($u['email'] ?? '');
$pre_phone  = htmlspecialchars($user_row['phone'] ?? '');
$pre_city   = htmlspecialchars($user_row['city']  ?? '');

if (isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!$u) {
        header('Location: /limedime/login.php?redirect=checkout');
        exit;
    }

    $cart = json_decode($_POST['cart_json'] ?? '{}', true) ?: [];

    $line_items = [];
    $subtotal   = 0.0;

    foreach ($cart as $pid_str => $qty) {
        $qty = max(1, (int)$qty);
        $pid = (int)ltrim($pid_str, 'p');
        if ($pid <= 0) continue;

        $pr = mysqli_query($conn, "SELECT id, price FROM products WHERE id = $pid AND status = 'live' AND quantity > 0");
        if ($pr && ($row = mysqli_fetch_assoc($pr))) {
            $line_items[] = ['pid' => (int)$row['id'], 'qty' => $qty, 'price' => (float)$row['price']];
            $subtotal += (float)$row['price'] * $qty;
        }
    }

    if (empty($line_items)) {
        header('Location: /limedime/cart.php');
        exit;
    }

    $delivery_opt = $_POST['delivery'] ?? 'standard';
    if ($delivery_opt === 'express') {
        $shipping = 8.50;
    } elseif ($delivery_opt === 'sameday') {
        $shipping = 14.00;
    } else {
        $shipping = $subtotal >= 40 ? 0.0 : 4.90;
    }
    $total = round($subtotal + $shipping, 2);

    $addr_str = mysqli_real_escape_string($conn, trim(implode(', ', array_filter([
        trim($_POST['address'] ?? ''),
        trim($_POST['city']    ?? ''),
        trim($_POST['zip']     ?? ''),
    ]))));

    $cid = (int)$u['id'];
    if (mysqli_query($conn, "INSERT INTO orders (customer_id, status, order_date, total, delivery_address) VALUES ($cid, 'pending', NOW(), $total, '$addr_str')")) {
        $order_id = mysqli_insert_id($conn);

        foreach ($line_items as $li) {
            $line_total = round($li['price'] * $li['qty'], 2);
            $lpid = $li['pid'];
            $lqty = $li['qty'];
            mysqli_query($conn, "INSERT INTO order_line (order_id, product_id, amount, total_price) VALUES ($order_id, $lpid, $lqty, $line_total)");
            mysqli_query($conn, "UPDATE products SET quantity = GREATEST(0, quantity - $lqty) WHERE id = $lpid");
        }

        $cr = mysqli_query($conn, "SELECT id FROM users WHERE role = 'courier' AND is_online = 1 ORDER BY RAND() LIMIT 1");
        if ($cr && ($crow = mysqli_fetch_assoc($cr))) {
            $cour_id = (int)$crow['id'];
            $addr_for_del = $addr_str ?: 'N/A';
            mysqli_query($conn, "INSERT INTO deliveries (order_id, courier_id, status, dropoff_address, payout, distance_km, eta_minutes, assigned_at) VALUES ($order_id, $cour_id, 'assigned', '$addr_for_del', 5.00, 0, 30, NOW())");
        }

        header('Location: /limedime/order-success.php?id=' . $order_id);
        exit;
    } else {
        header('Location: /limedime/cart.php?err=order');
        exit;
    }
}

$page_title   = 'Checkout';
$active_route = 'checkout';
$show_catbar  = false;
include '_partials/header.php';
?>

<div class="container container-narrow page">
    <h1 class="h1" style="margin-bottom:8px;">Checkout</h1>
    <p class="muted" id="checkout-summary" style="margin-bottom:20px;">Loading cart…</p>

    <div class="tracker" style="margin-bottom:32px;" id="step-tracker">
        <?php foreach (['Address', 'Delivery', 'Payment'] as $i => $s): ?>
        <div class="tracker-step" id="tracker-<?= $i ?>">
            <div class="dot"><?= $i + 1 ?></div>
            <div class="lbl"><?= $s ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card" style="padding:28px;">

        <div id="step-0">
            <h3 class="h3" style="margin-top:0;">Where should we send it?</h3>
            <?php if (!$u): ?>
            <div style="padding:14px;background:rgba(200,168,122,.12);border:1px solid var(--coral);border-radius:var(--r);margin-bottom:18px;font-size:13.5px;">
                <a href="/limedime/login.php?redirect=checkout" style="color:var(--wine);font-weight:700;">Sign in</a> to pre-fill your details.
            </div>
            <?php endif; ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px;">
                <div class="field"><label>First name</label><input name="fname" value="<?= $pre_first ?>" placeholder="Jane"></div>
                <div class="field"><label>Last name</label><input name="lname" value="<?= $pre_last ?>" placeholder="Smith"></div>
                <div class="field" style="grid-column:1/-1;"><label>Email</label><input type="email" name="email" value="<?= $pre_email ?>" placeholder="you@example.com"></div>
                <div class="field" style="grid-column:1/-1;"><label>Phone</label><input name="phone" value="<?= $pre_phone ?>" placeholder="+1 415 555 0100"></div>
                <div class="field" style="grid-column:1/-1;"><label>Street address</label><input name="address" placeholder="37 Almond Street, Apt 4B"></div>
                <div class="field"><label>City</label><input name="city" value="<?= $pre_city ?>" placeholder="Portland"></div>
                <div class="field"><label>ZIP</label><input name="zip" placeholder="97214"></div>
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:22px;">
                <button class="btn btn-primary" onclick="goStep(1)">Continue to delivery <?= icon_chev(14) ?></button>
            </div>
        </div>

        <div id="step-1" style="display:none;">
            <h3 class="h3" style="margin-top:0;">How should we deliver it?</h3>
            <div style="display:flex;flex-direction:column;gap:10px;margin-top:16px;">
                <?php
                $opts = [
                    ['standard', 'Standard · 3–5 days',  'Free',   'Tracked, signature optional',                  true],
                    ['express',  'Express · 1–2 days',    '$8.50',  'Courier hand-off, real-time tracking',         false],
                    ['sameday',  'Same-day · within 4h',  '$14.00', 'Available for selected sellers in your city',  false],
                ];
                foreach ($opts as [$val, $name, $price, $sub, $sel]):
                ?>
                <label style="display:flex;align-items:center;gap:14px;padding:14px;border:1.5px solid <?= $sel ? 'var(--wine)' : 'var(--line)' ?>;border-radius:var(--r);background:<?= $sel ? 'var(--surface-2)' : 'var(--surface)' ?>;cursor:pointer;">
                    <input type="radio" name="delivery" value="<?= $val ?>" <?= $sel ? 'checked' : '' ?> style="accent-color:var(--wine);" onchange="updateDeliveryTotal()">
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:14px;"><?= $name ?></div>
                        <div class="muted" style="font-size:12.5px;"><?= $sub ?></div>
                    </div>
                    <b style="font-family:Fraunces,serif;font-size:17px;color:var(--wine);"><?= $price ?></b>
                </label>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:22px;">
                <button class="btn btn-ghost" onclick="goStep(0)">Back</button>
                <button class="btn btn-primary" onclick="goStep(2)">Continue to payment <?= icon_chev(14) ?></button>
            </div>
        </div>

        <div id="step-2" style="display:none;">
            <h3 class="h3" style="margin-top:0;">Payment</h3>
            <div style="padding:18px;background:rgba(200,168,122,.12);border:1.5px solid var(--coral);border-radius:var(--r);margin:16px 0 22px;display:flex;align-items:flex-start;gap:14px;">
                <span style="width:38px;height:38px;border-radius:10px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?= icon_package(18) ?></span>
                <div>
                    <b style="font-size:14px;">Cash on delivery — only option available</b>
                    <p class="muted" style="margin:4px 0 0;font-size:13px;line-height:1.5;">Pay in cash when your order arrives. No extra charges.</p>
                </div>
            </div>
            <label style="display:flex;align-items:center;gap:14px;padding:16px;border:1.5px solid var(--wine);border-radius:var(--r);background:var(--surface-2);">
                <input type="radio" name="payment" value="cod" checked style="accent-color:var(--wine);">
                <div>
                    <div style="font-weight:700;font-size:14px;">Pay on delivery</div>
                    <div class="muted" style="font-size:12.5px;">Cash accepted. Have the exact amount ready.</div>
                </div>
            </label>
            <div style="margin-top:22px;padding:16px;background:var(--surface-2);border-radius:var(--r);display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div class="muted" style="font-size:12px;">You'll pay on delivery</div>
                    <div id="pay-total" style="font-family:Fraunces,serif;font-size:28px;font-weight:700;color:var(--wine);">—</div>
                </div>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-ghost" onclick="goStep(1)">Back</button>
                    <button class="btn btn-primary btn-lg" onclick="placeOrder()"><?php if (!$u): ?>Sign in to order<?php else: ?>Place order<?php endif; ?></button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    var currentStep  = 0;
    var cartSubtotal = 0;
    var shippingCosts = { standard: -1, express: 8.50, sameday: 14.00 };

    function calcTotal() {
        var delivery = document.querySelector('[name="delivery"]:checked');
        var opt = delivery ? delivery.value : 'standard';
        var ship = shippingCosts[opt] < 0 ? (cartSubtotal >= 40 ? 0 : 4.90) : shippingCosts[opt];
        return cartSubtotal + ship;
    }

    function updateDeliveryTotal() {
        var t = calcTotal();
        var el = document.getElementById('pay-total');
        if (el) el.textContent = '$' + t.toFixed(2);
    }

    function goStep(n) {
        document.getElementById('step-' + currentStep).style.display = 'none';
        currentStep = n;
        document.getElementById('step-' + n).style.display = '';
        for (var i = 0; i < 3; i++) {
            var el = document.getElementById('tracker-' + i);
            el.classList.remove('done', 'now');
            if (i < n) el.classList.add('done');
            else if (i === n) el.classList.add('now');
            el.querySelector('.dot').textContent = i < n ? '✓' : (i + 1);
        }
        if (n === 2) updateDeliveryTotal();
    }

    function placeOrder() {
        <?php if (!$u): ?>
        window.location.href = '/limedime/login.php?redirect=checkout';
        return;
        <?php endif; ?>

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/limedime/checkout.php';

        function addHidden(name, value) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        addHidden('action', 'place_order');
        addHidden('cart_json', localStorage.getItem('ld_cart') || '{}');

        ['fname', 'lname', 'email', 'phone', 'address', 'city', 'zip'].forEach(function(f) {
            var el = document.querySelector('[name="' + f + '"]');
            if (el) addHidden(f, el.value);
        });

        var del = document.querySelector('[name="delivery"]:checked');
        addHidden('delivery', del ? del.value : 'standard');

        localStorage.removeItem('ld_cart');
        localStorage.removeItem('ld_cart_meta');
        document.cookie = 'ld_cart_count=0;path=/;max-age=31536000';
        if (window.updateNavCount) window.updateNavCount();

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        var cart = JSON.parse(localStorage.getItem('ld_cart') || '{}');
        var meta = JSON.parse(localStorage.getItem('ld_cart_meta') || '{}');
        var count = 0;
        cartSubtotal = 0;
        Object.keys(cart).forEach(function(pid) {
            var q = parseInt(cart[pid]) || 0;
            var m = meta[pid];
            if (m && q > 0) {
                cartSubtotal += parseFloat(m.price) * q;
                count += q;
            }
        });
        var displayTotal = calcTotal();
        document.getElementById('checkout-summary').textContent = count + ' item' + (count !== 1 ? 's' : '') + ' · $' + displayTotal.toFixed(2) + ' total';
        document.getElementById('pay-total').textContent = '$' + displayTotal.toFixed(2);
        document.getElementById('tracker-0').classList.add('now');
    });
</script>

<?php include '_partials/footer.php'; ?>
