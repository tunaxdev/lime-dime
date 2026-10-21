<?php
include '_partials/session.php';
include '_partials/connect.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

if (is_logged_in()) {
    header('Location: /limedime/index.php');
    exit;
}

$error  = '';
$values = [];

if (isset($_POST['submit'])) {
    $first   = trim($_POST['first_name']      ?? '');
    $last    = trim($_POST['last_name']       ?? '');
    $email   = trim($_POST['email']           ?? '');
    $pass    = $_POST['password']             ?? '';
    $confirm = $_POST['confirm_password']     ?? '';
    $values  = compact('first', 'last', 'email');

    if (!$first || !$last || !$email || !$pass) {
        $error = 'All fields are required.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$safe_email'");

        if (mysqli_num_rows($check) > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $name = trim($first . ' ' . $last);
            $safe_name  = mysqli_real_escape_string($conn, $name);
            $safe_hash  = mysqli_real_escape_string($conn, $hash);

            $sql = "INSERT INTO users (name, email, password, role, status) VALUES ('$safe_name', '$safe_email', '$safe_hash', 'customer', 'active')";

            if (mysqli_query($conn, $sql)) {
                $uid = mysqli_insert_id($conn);
                $_SESSION['user'] = ['id' => $uid, 'name' => $name, 'email' => $email, 'role' => 'customer'];
                header('Location: /limedime/index.php');
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
    <title>Create account — Lime&amp;Dime</title>
    <link rel="stylesheet" href="/limedime/assets/css/styles.css">
</head>

<body>
    <div class="auth-shell">
        <div class="auth-art" style="background:linear-gradient(160deg,var(--coral) 0%,var(--wine) 100%);background-image:url('https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=900&q=80');background-size:cover;background-position:center;">
            <div style="position:absolute;inset:0;background:linear-gradient(160deg,rgba(224,119,125,.85) 0%,rgba(142,59,70,.9) 100%);"></div>
            <div style="position:relative;z-index:1;">
                <a href="/limedime/index.php" style="text-decoration:none;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="/limedime/assets/img/logo.png" width="44" height="44" alt="" style="object-fit:contain;">
                        <div style="font-family:Fraunces,serif;font-weight:700;font-size:24px;letter-spacing:-0.03em;color:var(--lime);">Lime<span style="color:var(--lime-soft);">&amp;</span>Dime</div>
                    </div>
                </a>
            </div>
            <div style="position:relative;z-index:1;">
                <div class="eyebrow" style="color:var(--lime-soft);">New here?</div>
                <h2>Find a few shops worth coming back for.</h2>
                <p>Free to join. Get $5 off your first order over $40, plus a weekly digest of finds from sellers we think you'll love.</p>
            </div>
            <div style="position:relative;z-index:1;display:flex;flex-direction:column;gap:8px;font-size:13px;color:rgba(238,234,181,.85);">
                <span>✓ No setup fees, no monthly fees</span>
                <span>✓ Track orders + favourites in one place</span>
                <span>✓ Support small businesses near you</span>
            </div>
        </div>

        <div class="auth-form">
            <div class="auth-form-inner">
                <h1 class="h1" style="margin:0 0 8px;font-size:36px;">Create account</h1>
                <p class="muted" style="margin:0 0 28px;">It takes about 30 seconds.</p>

                <?php if ($error): ?>
                <div style="background:rgba(142,59,70,.1);border:1px solid var(--wine);border-radius:var(--r);padding:12px 16px;color:var(--wine);font-size:13.5px;margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="/limedime/register.php">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="field">
                            <label>First name</label>
                            <input name="first_name" placeholder="Asha" value="<?= htmlspecialchars($values['first'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label>Last name</label>
                            <input name="last_name" placeholder="Niemi" value="<?= htmlspecialchars($values['last'] ?? '') ?>" required>
                        </div>
                        <div class="field" style="grid-column:1/-1;">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="you@email.com" value="<?= htmlspecialchars($values['email'] ?? '') ?>" required>
                        </div>
                        <div class="field" style="grid-column:1/-1;">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="At least 8 characters" required>
                        </div>
                        <div class="field" style="grid-column:1/-1;">
                            <label>Confirm password</label>
                            <input type="password" name="confirm_password" placeholder="Repeat password" required>
                        </div>
                    </div>
                    <label style="display:flex;align-items:flex-start;gap:10px;margin-top:16px;font-size:13px;color:var(--ink-2);">
                        <input type="checkbox" name="newsletter" style="margin-top:3px;" checked>
                        <span>I agree to the <a href="/limedime/terms.php" target="_blank" style="color:var(--wine);font-weight:700;">Terms</a> and <a href="/limedime/privacy.php" target="_blank" style="color:var(--wine);font-weight:700;">Privacy Policy</a>. Send me the weekly digest.</span>
                    </label>
                    <button type="submit" name="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:20px;display:flex;justify-content:center;width:100%;">Create account</button>
                </form>

                <p class="muted" style="text-align:center;font-size:13px;margin-top:20px;">
                    Already have one? <a href="/limedime/login.php" style="color:var(--wine);font-weight:700;">Sign in</a>
                </p>
            </div>
        </div>
    </div>
    <script src="/limedime/assets/js/app.js"></script>
</body>

</html>
