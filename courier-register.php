<?php
include '_partials/session.php';
include '_partials/connect.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['full_name']       ?? '');
    $email   = trim($_POST['email']           ?? '');
    $phone   = trim($_POST['phone']           ?? '');
    $dob     = trim($_POST['dob']             ?? '');
    $city    = trim($_POST['city']            ?? '');
    $pass    = $_POST['password']             ?? '';
    $confirm = $_POST['confirm_password']     ?? '';

    if (!$name || !$email || !$pass) {
        $error = 'Full name, email, and password are required.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$safe_email'");

        if (mysqli_num_rows($check) > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hashed     = password_hash($pass, PASSWORD_DEFAULT);
            $safe_name  = mysqli_real_escape_string($conn, $name);
            $safe_hash  = mysqli_real_escape_string($conn, $hashed);
            $safe_phone = mysqli_real_escape_string($conn, $phone);
            $safe_dob   = mysqli_real_escape_string($conn, $dob);
            $safe_city  = mysqli_real_escape_string($conn, $city);

            $dob_val = $safe_dob ? "'$safe_dob'" : 'NULL';
            $sql = "INSERT INTO users (name, email, password, phone, dob, city, role, status) VALUES ('$safe_name', '$safe_email', '$safe_hash', '$safe_phone', $dob_val, '$safe_city', 'courier', 'pending')";

            if (mysqli_query($conn, $sql)) {
                $uid = mysqli_insert_id($conn);

                $doc_fields = [
                    'doc_gov_id'    => 'Government ID',
                    'doc_license'   => 'Driver / cyclist license',
                    'doc_insurance' => 'Proof of insurance',
                    'doc_tax'       => 'Tax form (W-9)',
                ];
                $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
                $doc_dir = __DIR__ . '/assets/docs/' . $uid . '/';
                if (!is_dir($doc_dir)) mkdir($doc_dir, 0755, true);

                foreach ($doc_fields as $key => $label) {
                    if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES[$key];
                        $mime = mime_content_type($file['tmp_name']);
                        $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
                        if (isset($ext_map[$mime]) && $file['size'] <= 5 * 1024 * 1024) {
                            $fname = $key . '.' . $ext_map[$mime];
                            move_uploaded_file($file['tmp_name'], $doc_dir . $fname);
                            $safe_label = mysqli_real_escape_string($conn, $label);
                            $safe_path  = mysqli_real_escape_string($conn, $uid . '/' . $fname);
                            mysqli_query($conn, "INSERT INTO documents (user_id, label, file_path) VALUES ($uid, '$safe_label', '$safe_path')");
                        }
                    }
                }

                header('Location: /limedime/courier-register.php?submitted=1');
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
    <title>Become a courier — Lime&amp;Dime</title>
    <link rel="stylesheet" href="/limedime/assets/css/styles.css">
</head>

<body>
    <div class="auth-shell">
        <div class="auth-art" style="background:linear-gradient(160deg,var(--teal) 0%,var(--teal-deep) 100%);background-image:url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=900&q=80');background-size:cover;background-position:center;">
            <div style="position:absolute;inset:0;background:linear-gradient(160deg,rgba(71,120,144,.88) 0%,rgba(52,90,110,.93) 100%);"></div>
            <div style="position:relative;z-index:1;">
                <a href="/limedime/index.php" style="text-decoration:none;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="/limedime/assets/img/logo.png" width="44" height="44" alt="" style="object-fit:contain;">
                        <div style="font-family:Fraunces,serif;font-weight:700;font-size:24px;letter-spacing:-0.03em;color:var(--lime);">Lime<span style="color:var(--coral);">&amp;</span>Dime</div>
                    </div>
                </a>
            </div>
            <div style="position:relative;z-index:1;">
                <div class="eyebrow" style="color:var(--lime-soft);">Deliver with Lime&amp;Dime</div>
                <h2>Earn on your own schedule.</h2>
                <p>Pick up orders from small shops near you, drop them off with the people who ordered them. Get paid weekly, keep 100% of tips.</p>
            </div>
            <div style="position:relative;z-index:1;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;color:rgba(238,234,181,.85);">
                <div>
                    <div style="font-family:Fraunces,serif;font-size:24px;font-weight:700;color:var(--lime);">$22/h</div>
                    <div style="font-size:12px;color:rgba(238,234,181,.7);">Avg. earnings</div>
                </div>
                <div>
                    <div style="font-family:Fraunces,serif;font-size:24px;font-weight:700;color:var(--lime);">2,400+</div>
                    <div style="font-size:12px;color:rgba(238,234,181,.7);">Couriers in 14 cities</div>
                </div>
                <div>
                    <div style="font-family:Fraunces,serif;font-size:24px;font-weight:700;color:var(--lime);">Weekly</div>
                    <div style="font-size:12px;color:rgba(238,234,181,.7);">Direct deposit</div>
                </div>
                <div>
                    <div style="font-family:Fraunces,serif;font-size:24px;font-weight:700;color:var(--lime);">Bike · Car</div>
                    <div style="font-size:12px;color:rgba(238,234,181,.7);">Or scooter, your call</div>
                </div>
            </div>
        </div>

        <div class="auth-form" style="align-items:flex-start;">
            <div class="auth-form-inner" style="max-width:480px;padding-top:24px;">
                <h1 class="h1" style="margin:0 0 4px;font-size:32px;">Become a courier</h1>
                <p class="muted" id="step-desc" style="margin:0 0 22px;">Step 1 of 4 · About you</p>

                <?php if ($error): ?>
                <div style="background:rgba(142,59,70,.1);border:1px solid var(--wine);border-radius:var(--r);padding:12px 16px;color:var(--wine);font-size:13.5px;margin-bottom:18px;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="tracker" style="margin-bottom:28px;" id="cr-tracker">
                    <?php foreach (['About you', 'Your ride', 'Documents', 'Done'] as $i => $step): ?>
                    <div class="tracker-step <?= $i === 0 ? 'now' : '' ?>" id="cr-tracker-<?= $i ?>">
                        <div class="dot"><?= $i + 1 ?></div>
                        <div class="lbl"><?= $step ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form id="cr-form" method="post" action="/limedime/courier-register.php" enctype="multipart/form-data">

                    <div id="cr-step-0">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="field" style="grid-column:1/-1;"><label>Full name</label><input name="full_name" placeholder="Jules Rapp" required></div>
                            <div class="field" style="grid-column:1/-1;"><label>Email</label><input type="email" name="email" placeholder="jules@email.com" required></div>
                            <div class="field"><label>Phone</label><input name="phone" placeholder="+1 415 …"></div>
                            <div class="field"><label>Date of birth</label><input type="date" name="dob"></div>
                            <div class="field" style="grid-column:1/-1;"><label>City</label><input name="city" placeholder="Portland, OR"></div>
                            <div class="field" style="grid-column:1/-1;"><label>Password</label><input type="password" name="password" id="cr-password" placeholder="At least 8 characters" required></div>
                            <div class="field" style="grid-column:1/-1;"><label>Confirm password</label><input type="password" name="confirm_password" id="cr-confirm" placeholder="Repeat password" required></div>
                            <div id="cr-pass-error" style="display:none;background:rgba(142,59,70,.1);border:1px solid var(--wine);border-radius:var(--r);padding:10px 14px;color:var(--wine);font-size:13px;grid-column:1/-1;"></div>
                        </div>
                    </div>

                    <div id="cr-step-1" style="display:none;">
                        <p style="font-weight:600;margin-bottom:10px;">How will you deliver?</p>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px;" id="vehicle-grid">
                            <?php foreach ([['Bike', '🚲'], ['Scooter', '🛵'], ['Car', '🚗']] as $i => [$n, $e]): ?>
                            <label class="vehicle-option" style="padding:16px;border:1.5px solid <?= $i === 0 ? 'var(--wine)' : 'var(--line)' ?>;border-radius:var(--r);text-align:center;background:<?= $i === 0 ? 'var(--surface-2)' : 'var(--surface)' ?>;cursor:pointer;">
                                <input type="radio" name="vehicle" value="<?= $n ?>" <?= $i === 0 ? 'checked' : '' ?> style="display:none;">
                                <div style="font-size:28px;margin-bottom:6px;"><?= $e ?></div>
                                <div style="font-weight:700;font-size:13px;"><?= $n ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="field" style="margin-bottom:12px;"><label>Vehicle model / make</label><input name="vehicle_model" placeholder="Brompton M3R"></div>
                        <div class="field"><label>Plate number (if applicable)</label><input name="plate" placeholder="—"></div>
                    </div>

                    <div id="cr-step-2" style="display:none;">
                        <p class="muted" style="font-size:13px;margin-bottom:14px;">Upload your documents. PDF or photo, max 5 MB each.</p>
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <?php foreach ([['Government ID', 'doc_gov_id'], ['Driver / cyclist license', 'doc_license'], ['Proof of insurance', 'doc_insurance'], ['Tax form (W-9)', 'doc_tax']] as [$label, $key]): ?>
                            <label style="display:flex;align-items:center;gap:12px;padding:14px;border:1px dashed var(--line);border-radius:var(--r);background:var(--surface);cursor:pointer;">
                                <span style="width:34px;height:34px;border-radius:8px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?= icon_plus(16) ?></span>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:600;font-size:14px;"><?= $label ?></div>
                                    <div class="muted" style="font-size:12px;" id="<?= $key ?>-lbl">PDF or photo · up to 5 MB</div>
                                </div>
                                <input type="file" name="<?= $key ?>" accept=".pdf,.jpg,.jpeg,.png,.webp" style="display:none;"
                                    onchange="document.getElementById('<?= $key ?>-lbl').textContent = this.files[0] ? this.files[0].name : 'PDF or photo · up to 5 MB'; this.closest('label').style.borderColor = this.files[0] ? 'var(--teal)' : 'var(--line)';">
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </form>

                <div id="cr-step-3" style="display:none;text-align:center;padding:12px 0;">
                    <div style="width:80px;height:80px;border-radius:999px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;margin-bottom:18px;"><?= icon_check(36) ?></div>
                    <h3 class="h3" style="margin:0 0 8px;">You're in the queue.</h3>
                    <p class="muted" style="margin-bottom:22px;">We review applications within 48 hours and will email you once approved.</p>
                    <a href="/limedime/login.php" class="btn btn-primary">Sign in</a>
                </div>

                <div id="cr-nav" style="display:flex;justify-content:space-between;margin-top:20px;">
                    <button class="btn btn-ghost" id="cr-back" onclick="crStep(-1)" style="opacity:.4;pointer-events:none;">Back</button>
                    <button class="btn btn-primary" id="cr-next" onclick="crStep(1)">Continue <?= icon_chev(14) ?></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        var crCurrent = 0;
        var crLabels = ['About you', 'Your ride', 'Documents', 'Done'];
        var crCheckIcon = '<?= addslashes(icon_check(13)) ?>';

        document.querySelectorAll('.vehicle-option input[type="radio"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.vehicle-option').forEach(function(lbl) {
                    var checked = lbl.querySelector('input').checked;
                    lbl.style.borderColor = checked ? 'var(--wine)' : 'var(--line)';
                    lbl.style.background = checked ? 'var(--surface-2)' : 'var(--surface)';
                });
            });
        });

        function crStep(d) {
            var next = crCurrent + d;
            if (next < 0 || next > 3) return;

            if (crCurrent === 0 && d === 1) {
                var pass = document.getElementById('cr-password').value;
                var conf = document.getElementById('cr-confirm').value;
                var errEl = document.getElementById('cr-pass-error');
                if (pass.length < 8) {
                    errEl.textContent = 'Password must be at least 8 characters.';
                    errEl.style.display = '';
                    return;
                }
                if (pass !== conf) {
                    errEl.textContent = 'Passwords do not match.';
                    errEl.style.display = '';
                    return;
                }
                errEl.style.display = 'none';
            }

            if (crCurrent === 2 && d === 1) {
                document.getElementById('cr-form').submit();
                return;
            }
            document.getElementById('cr-step-' + crCurrent).style.display = 'none';
            crCurrent = next;
            document.getElementById('cr-step-' + crCurrent).style.display = '';
            document.getElementById('step-desc').textContent = 'Step ' + (crCurrent + 1) + ' of 4 · ' + crLabels[crCurrent];
            var back = document.getElementById('cr-back');
            back.style.opacity = crCurrent === 0 ? '.4' : '1';
            back.style.pointerEvents = crCurrent === 0 ? 'none' : 'auto';
            var nextBtn = document.getElementById('cr-next');
            nextBtn.textContent = crCurrent === 2 ? 'Submit application' : 'Continue ›';
            if (crCurrent === 3) document.getElementById('cr-nav').style.display = 'none';
            for (var i = 0; i < 4; i++) {
                var el = document.getElementById('cr-tracker-' + i);
                el.classList.remove('done', 'now');
                if (i < crCurrent) {
                    el.classList.add('done');
                    el.querySelector('.dot').innerHTML = crCheckIcon;
                } else if (i === crCurrent) {
                    el.classList.add('now');
                    el.querySelector('.dot').textContent = i + 1;
                } else {
                    el.querySelector('.dot').textContent = i + 1;
                }
            }
        }

        <?php if (isset($_GET['submitted'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('cr-step-0').style.display = 'none';
            document.getElementById('cr-step-3').style.display = '';
            document.getElementById('cr-nav').style.display = 'none';
            document.getElementById('step-desc').textContent = 'Step 4 of 4 · Done';
            for (var i = 0; i < 4; i++) {
                var el = document.getElementById('cr-tracker-' + i);
                el.classList.remove('done', 'now');
                if (i < 3) {
                    el.classList.add('done');
                    el.querySelector('.dot').innerHTML = crCheckIcon;
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
