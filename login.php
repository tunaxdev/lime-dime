<?php
include '_partials/session.php';
include '_partials/connect.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

if (is_logged_in()) {
    header('Location: /limedime/index.php');
    exit;
}

$error = '';

if (isset($_POST['submit'])) {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);
        $result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$safe_email' LIMIT 1");
        $user = mysqli_fetch_assoc($result);

        if ($user && $user['status'] === 'suspended') {
            $error = 'This account has been suspended. Please contact support.';
        } elseif ($user && $user['status'] === 'pending') {
            $error = 'Your account has not yet been verified. Please wait for approval.';
        } elseif ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];
            if ($user['role'] == 'admin') {
                $dest = '/limedime/admin.php';
            } elseif ($user['role'] == 'seller') {
                $dest = '/limedime/manager.php';
            } elseif ($user['role'] == 'courier') {
                $dest = '/limedime/courier.php';
            } else {
                $dest = '/limedime/index.php';
            }
            header('Location: ' . $dest);
            exit;
        } else {
            $error = 'The email address or password is incorrect.';
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign in — Lime&amp;Dime</title>
    <link rel="stylesheet" href="/limedime/assets/css/styles.css">
</head>

<body>
    <div class="auth-shell">
        <div class="auth-art" style="background-image:url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=900&q=80');background-size:cover;background-position:center;">
            <div style="position:absolute;inset:0;background:linear-gradient(160deg,rgba(142,59,70,.88) 0%,rgba(110,42,52,.92) 100%);"></div>
            <div style="position:relative;z-index:1;">
                <a href="/limedime/index.php" style="text-decoration:none;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="/limedime/assets/img/logo.png" width="44" height="44" alt="" style="object-fit:contain;">
                        <div style="font-family:Fraunces,serif;font-weight:700;font-size:24px;letter-spacing:-0.03em;color:var(--lime);">Lime<span style="color:var(--coral);">&amp;</span>Dime</div>
                    </div>
                </a>
            </div>
            <div style="position:relative;z-index:1;">
                <div class="eyebrow" style="color:var(--coral-soft);">Welcome back</div>
                <h2>The good stuff is waiting for you.</h2>
                <p>Sign in to pick up where you left off — saved favourites, pending orders, and shops you've been keeping an eye on.</p>
            </div>
            <div style="position:relative;z-index:1;display:flex;gap:28px;font-size:12.5px;color:rgba(238,234,181,.7);">
                <span>★ Trusted by 84k shoppers</span>
                <span>★ 1,240 small sellers</span>
            </div>
        </div>

        <div class="auth-form">
            <div class="auth-form-inner">
                <h1 class="h1" style="margin:0 0 8px;font-size:36px;">Sign in</h1>
                <p class="muted" style="margin:0 0 28px;">Use your email to continue.</p>

                <?php if ($error): ?>
                <div style="background:rgba(142,59,70,.1);border:1px solid var(--wine);border-radius:var(--r);padding:12px 16px;color:var(--wine);font-size:13.5px;margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="/limedime/login.php">
                    <div class="field" style="margin-bottom:14px;">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="you@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="field" style="margin-bottom:22px;">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary btn-lg btn-block" style="display:flex;justify-content:center;width:100%;">Sign in</button>
                </form>

                <p class="muted" style="text-align:center;font-size:13px;margin-top:20px;">
                    New to Lime&amp;Dime? <a href="/limedime/register.php" style="color:var(--wine);font-weight:700;">Create an account</a>
                </p>
                <p style="text-align:center;font-size:12px;color:var(--ink-4);margin-top:18px;">
                    Want to deliver? <a href="/limedime/courier-register.php" style="color:var(--teal);font-weight:700;">Apply as a courier</a>
                    · <a href="/limedime/manager-register.php" style="color:var(--wine);font-weight:700;">Open a shop</a>
                </p>
            </div>
        </div>
    </div>
    <script src="/limedime/assets/js/app.js"></script>
</body>

</html>
