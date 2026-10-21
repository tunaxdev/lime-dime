<?php
include '_partials/session.php';
include '_partials/connect.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first     = trim($_POST['first_name']    ?? '');
    $last      = trim($_POST['last_name']     ?? '');
    $email     = trim($_POST['email']         ?? '');
    $phone     = trim($_POST['phone']         ?? '');
    $password  = $_POST['password']           ?? '';
    $shop_name = trim($_POST['shop_name']     ?? '');
    $category  = trim($_POST['category']      ?? 'food');
    $btype     = trim($_POST['business_type'] ?? '');
    $desc      = trim($_POST['description']   ?? '');
    $city      = trim($_POST['city']          ?? '');
    $zip       = trim($_POST['zip']           ?? '');

    if (!$first || !$email || !$password || !$shop_name) {
        $error = 'First name, email, password and shop name are required.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$safe_email'");

        if (mysqli_num_rows($check) > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hash       = password_hash($password, PASSWORD_DEFAULT);
            $name_str   = trim($first . ' ' . $last);
            $safe_name  = mysqli_real_escape_string($conn, $name_str);
            $safe_hash  = mysqli_real_escape_string($conn, $hash);
            $safe_phone = mysqli_real_escape_string($conn, $phone);
            $safe_city  = mysqli_real_escape_string($conn, $city);

            $sql = "INSERT INTO users (name, email, password, phone, city, role, status) VALUES ('$safe_name', '$safe_email', '$safe_hash', '$safe_phone', '$safe_city', 'seller', 'pending')";

            if (mysqli_query($conn, $sql)) {
                $user_id  = mysqli_insert_id($conn);
                $avatar   = strtoupper(substr($first, 0, 1) . substr($shop_name, 0, 1));

                $loc_str  = trim($city . ($zip ? ' ' . $zip : ''));
                $s_name   = mysqli_real_escape_string($conn, $shop_name);
                $s_cat    = mysqli_real_escape_string($conn, $category);
                $s_loc    = mysqli_real_escape_string($conn, $loc_str);
                $s_avatar = mysqli_real_escape_string($conn, $avatar);
                $s_desc   = mysqli_real_escape_string($conn, $desc);
                $s_btype  = mysqli_real_escape_string($conn, $btype);

                mysqli_query($conn, "INSERT INTO shops (name, type, location, manager_id, avatar, status, description, business_type) VALUES ('$s_name', '$s_cat', '$s_loc', $user_id, '$s_avatar', 'pending', '$s_desc', '$s_btype')");

                $doc_fields = [
                    'doc_gov_id'   => 'Government ID',
                    'doc_license'  => 'Business license',
                    'doc_tax'      => 'Tax form',
                    'doc_bank'     => 'Bank account',
                ];
                $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
                $doc_dir = __DIR__ . '/assets/docs/' . $user_id . '/';
                if (!is_dir($doc_dir)) mkdir($doc_dir, 0755, true);

                foreach ($doc_fields as $key => $label) {
                    if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES[$key];
                        $mime = mime_content_type($file['tmp_name']);
                        if (isset($ext_map[$mime]) && $file['size'] <= 5 * 1024 * 1024) {
                            $fname = $key . '.' . $ext_map[$mime];
                            move_uploaded_file($file['tmp_name'], $doc_dir . $fname);
                            $safe_label = mysqli_real_escape_string($conn, $label);
                            $safe_path  = mysqli_real_escape_string($conn, $user_id . '/' . $fname);
                            mysqli_query($conn, "INSERT INTO documents (user_id, label, file_path) VALUES ($user_id, '$safe_label', '$safe_path')");
                        }
                    }
                }

                header('Location: /limedime/manager-register.php?submitted=1');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Open a shop — Lime&amp;Dime</title>
    <link rel="stylesheet" href="/limedime/assets/css/styles.css">
</head>

<body>
    <div class="auth-shell">
        <div class="auth-art" style="background:linear-gradient(160deg,var(--lime-deep) 0%,var(--wine) 100%);background-image:url('https://images.unsplash.com/photo-1556909114-44e3e70034e2?w=900&q=80');background-size:cover;background-position:center;">
            <div style="position:absolute;inset:0;background:linear-gradient(160deg,rgba(200,195,106,.7) 0%,rgba(142,59,70,.92) 100%);"></div>
            <div style="position:relative;z-index:1;">
                <a href="/limedime/index.php" style="text-decoration:none;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="/limedime/assets/img/logo.png" width="44" height="44" alt="" style="object-fit:contain;">
                        <div style="font-family:Fraunces,serif;font-weight:700;font-size:24px;letter-spacing:-0.03em;color:var(--lime);">Lime<span style="color:var(--coral);">&amp;</span>Dime</div>
                    </div>
                </a>
            </div>
            <div style="position:relative;z-index:1;">
                <div class="eyebrow" style="color:var(--lime-soft);">Are you an owner?</div>
                <h2>Open your shop on Lime&amp;Dime.</h2>
                <p>Reach thousands of shoppers who care about small businesses. Start selling in minutes, manage everything from your dashboard.</p>
            </div>
            <div style="position:relative;z-index:1;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(238,234,181,.85);">
                <span>✓ 8.5% platform fee only — no monthly cost</span>
                <span>✓ Your own dashboard for orders &amp; deliveries</span>
                <span>✓ Featured placement available for sellers</span>
            </div>
        </div>

        <div class="auth-form" style="align-items:flex-start;">
            <div class="auth-form-inner" style="max-width:480px;padding-top:24px;">
                <h1 class="h1" style="margin:0 0 4px;font-size:32px;">Open a shop</h1>
                <p class="muted" id="mr-desc" style="margin:0 0 22px;">Step 1 of 4 · About you</p>

                <?php if ($error): ?>
                <div style="background:rgba(142,59,70,.1);border:1px solid var(--wine);border-radius:var(--r);padding:12px 16px;color:var(--wine);font-size:13.5px;margin-bottom:18px;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="tracker" style="margin-bottom:28px;">
                    <?php foreach (['About you', 'Your shop', 'Documents', 'Done'] as $i => $s): ?>
                    <div class="tracker-step <?= $i === 0 ? 'now' : '' ?>" id="mr-tracker-<?= $i ?>">
                        <div class="dot"><?= $i + 1 ?></div>
                        <div class="lbl"><?= $s ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form id="mr-form" method="post" action="/limedime/manager-register.php" enctype="multipart/form-data">

                    <div id="mr-step-0">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="field"><label>First name</label><input name="first_name" placeholder="Sunny" required></div>
                            <div class="field"><label>Last name</label><input name="last_name" placeholder="Citrus"></div>
                            <div class="field" style="grid-column:1/-1;"><label>Email</label><input type="email" name="email" placeholder="you@shop.co" required></div>
                            <div class="field"><label>Phone</label><input name="phone" placeholder="+1 …"></div>
                            <div class="field"><label>Date of birth</label><input type="date" name="dob"></div>
                            <div class="field" style="grid-column:1/-1;"><label>Password</label><input type="password" name="password" placeholder="At least 8 characters" required></div>
                        </div>
                    </div>

                    <div id="mr-step-1" style="display:none;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="field" style="grid-column:1/-1;"><label>Shop name</label><input name="shop_name" placeholder="Sunny Citrus Co." required></div>
                            <div class="field" style="grid-column:1/-1;"><label>Shop URL handle</label><input name="shop_handle" placeholder="sunny-citrus"></div>
                            <div class="field"><label>Category</label>
                                <select name="category">
                                    <option value="food">Food</option>
                                    <option value="clothing">Clothing</option>
                                    <option value="electronics">Electronics</option>
                                    <option value="education">Education</option>
                                    <option value="beauty">Beauty</option>
                                </select>
                            </div>
                            <div class="field"><label>Business type</label>
                                <select name="business_type">
                                    <option>Sole trader</option>
                                    <option>LLC</option>
                                    <option>Corporation</option>
                                </select>
                            </div>
                            <div class="field" style="grid-column:1/-1;"><label>Shop description</label><textarea name="description" rows="3" placeholder="Tell shoppers what makes your shop special…"></textarea></div>
                            <div class="field"><label>City</label><input name="city" placeholder="Portland"></div>
                            <div class="field"><label>ZIP code</label><input name="zip" placeholder="97214"></div>
                        </div>
                    </div>

                    <div id="mr-step-2" style="display:none;">
                        <p class="muted" style="font-size:13px;margin-bottom:14px;">Upload your documents. PDF or photo, max 5 MB each.</p>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <?php
                            $mr_docs = [
                                ['Government ID',    'doc_gov_id'],
                                ['Business license', 'doc_license'],
                                ['Tax form',         'doc_tax'],
                                ['Bank account',     'doc_bank'],
                            ];
                            foreach ($mr_docs as $doc_pair):
                                $doc_label = $doc_pair[0];
                                $doc_key   = $doc_pair[1];
                            ?>
                            <label style="display:flex;align-items:center;gap:12px;padding:14px;border:1px dashed var(--line);border-radius:var(--r);background:var(--surface);cursor:pointer;">
                                <span style="width:34px;height:34px;border-radius:8px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?= icon_plus(16) ?></span>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:600;font-size:14px;"><?= $doc_label ?></div>
                                    <div class="muted" style="font-size:12px;" id="<?= $doc_key ?>-lbl">PDF or photo · up to 5 MB</div>
                                </div>
                                <input type="file" name="<?= $doc_key ?>" accept=".pdf,.jpg,.jpeg,.png,.webp" style="display:none;"
                                    onchange="document.getElementById('<?= $doc_key ?>-lbl').textContent = this.files[0] ? this.files[0].name : 'PDF or photo · up to 5 MB'; this.closest('label').style.borderColor = this.files[0] ? 'var(--wine)' : 'var(--line)';">
                            </label>
                            <?php endforeach; ?>
                            <label style="display:flex;align-items:flex-start;gap:10px;margin-top:8px;font-size:13px;cursor:pointer;">
                                <input type="checkbox" name="agree_terms" style="margin-top:3px;" required>
                                <span>I agree to the <a href="/limedime/terms.php" target="_blank" style="color:var(--wine);font-weight:700;">Seller Terms</a> and <a href="/limedime/privacy.php" target="_blank" style="color:var(--wine);font-weight:700;">Marketplace Policy</a>.</span>
                            </label>
                        </div>
                    </div>

                </form>

                <div id="mr-step-3" style="display:none;text-align:center;padding:12px 0;">
                    <div style="width:80px;height:80px;border-radius:999px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;margin-bottom:18px;"><?= icon_check(36) ?></div>
                    <h3 class="h3" style="margin:0 0 8px;">Application received!</h3>
                    <p class="muted" style="margin-bottom:22px;">We review applications within 48 hours. Once approved, you'll have access to your Manager Dashboard to start listing products.</p>
                    <a href="/limedime/login.php" class="btn btn-primary">Sign in</a>
                </div>

                <div id="mr-nav" style="display:flex;justify-content:space-between;margin-top:20px;">
                    <button class="btn btn-ghost" id="mr-back" onclick="mrStep(-1)" style="opacity:.4;pointer-events:none;">Back</button>
                    <button class="btn btn-primary" id="mr-next" onclick="mrStep(1)">Continue <?= icon_chev(14) ?></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        var mrCurrent = 0;
        var mrLabels = ['About you', 'Your shop', 'Documents', 'Done'];
        var mrCheckIcon = '<?= addslashes(icon_check(13)) ?>';

        function mrStep(d) {
            var next = mrCurrent + d;
            if (next < 0 || next > 3) return;
            if (mrCurrent === 2 && d === 1) {
                document.getElementById('mr-form').submit();
                return;
            }
            document.getElementById('mr-step-' + mrCurrent).style.display = 'none';
            mrCurrent = next;
            document.getElementById('mr-step-' + mrCurrent).style.display = '';
            document.getElementById('mr-desc').textContent = 'Step ' + (mrCurrent + 1) + ' of 4 · ' + mrLabels[mrCurrent];
            var back = document.getElementById('mr-back');
            back.style.opacity = mrCurrent === 0 ? '.4' : '1';
            back.style.pointerEvents = mrCurrent === 0 ? 'none' : 'auto';
            document.getElementById('mr-next').textContent = mrCurrent === 2 ? 'Submit application' : 'Continue ›';
            if (mrCurrent === 3) document.getElementById('mr-nav').style.display = 'none';
            for (var i = 0; i < 4; i++) {
                var el = document.getElementById('mr-tracker-' + i);
                el.classList.remove('done', 'now');
                if (i < mrCurrent) {
                    el.classList.add('done');
                    el.querySelector('.dot').innerHTML = mrCheckIcon;
                } else if (i === mrCurrent) {
                    el.classList.add('now');
                    el.querySelector('.dot').textContent = i + 1;
                } else {
                    el.querySelector('.dot').textContent = i + 1;
                }
            }
        }

        <?php if (isset($_GET['submitted'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('mr-step-0').style.display = 'none';
            document.getElementById('mr-step-3').style.display = '';
            document.getElementById('mr-nav').style.display = 'none';
            document.getElementById('mr-desc').textContent = 'Step 4 of 4 · Done';
            for (var i = 0; i < 4; i++) {
                var el = document.getElementById('mr-tracker-' + i);
                el.classList.remove('done', 'now');
                if (i < 3) {
                    el.classList.add('done');
                    el.querySelector('.dot').innerHTML = mrCheckIcon;
                } else {
                    el.classList.add('now');
                    el.querySelector('.dot').textContent = 4;
                }
            }
        });
        <?php endif; ?>
    </script>
    <script src="/limedime/assets/js/app.js"></script>
</body>

</html>
