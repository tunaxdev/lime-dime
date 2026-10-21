<?php
if (!function_exists('current_user')) include __DIR__ . '/session.php';
$_nav_user  = current_user();
$cart_count = isset($_COOKIE['ld_cart_count']) ? (int)$_COOKIE['ld_cart_count'] : 0;
$show_catbar = isset($show_catbar) ? $show_catbar : false;
$active_cat  = isset($active_cat)  ? $active_cat  : 'all';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — Lime&amp;Dime' : 'Lime&amp;Dime — A warm little marketplace' ?></title>
    <link rel="stylesheet" href="/limedime/assets/css/styles.css">
</head>
<body>
<div class="app">

<header class="nav">
    <div class="container nav-inner">
        <a href="/limedime/index.php" style="display:inline-flex;">
            <div style="display:flex;align-items:center;gap:10px;">
                <img src="/limedime/assets/img/logo.png" width="36" height="36" alt="Lime&amp;Dime" style="object-fit:contain;">
                <div style="font-family:Fraunces,serif;font-weight:700;font-size:20px;letter-spacing:-0.03em;color:var(--ink);">
                    Lime<span style="color:var(--wine);">&amp;</span>Dime
                </div>
            </div>
        </a>
        <div class="nav-search">
            <?= icon_search(18) ?>
            <form action="/limedime/category.php" method="get" style="margin:0;">
                <input name="q" placeholder="Search products, sellers, categories…" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
            </form>
        </div>
        <div class="nav-icons">
            <a href="/limedime/orders.php" class="icon-btn <?= (isset($active_route) && $active_route === 'orders') ? 'active' : '' ?>" title="Orders">
                <?= icon_package(20) ?>
            </a>
            <?php if ($_nav_user && $_nav_user['role'] === 'seller'): ?>
            <a href="/limedime/manager.php" class="icon-btn <?= (isset($active_route) && $active_route === 'manager') ? 'active' : '' ?>" title="Manage shop">
                <?= icon_store(20) ?>
            </a>
            <?php elseif ($_nav_user && $_nav_user['role'] === 'courier'): ?>
            <a href="/limedime/courier.php" class="icon-btn <?= (isset($active_route) && $active_route === 'courier') ? 'active' : '' ?>" title="Courier dashboard">
                <?= icon_bike(20) ?>
            </a>
            <?php elseif ($_nav_user && $_nav_user['role'] === 'admin'): ?>
            <a href="/limedime/admin.php" class="btn btn-ghost btn-sm" style="font-weight:700;font-size:12.5px;color:var(--wine);padding:6px 14px;" title="Admin panel">Admin</a>
            <?php endif; ?>
            <a href="<?= $_nav_user ? '/limedime/profile.php' : '/limedime/login.php' ?>" class="icon-btn <?= (isset($active_route) && $active_route === 'profile') ? 'active' : '' ?>" title="<?= $_nav_user ? 'Profile' : 'Sign in' ?>">
                <?= icon_user(20) ?>
            </a>
            <a href="/limedime/cart.php" class="icon-btn <?= (isset($active_route) && $active_route === 'cart') ? 'active' : '' ?>" title="Cart" id="nav-cart-btn" style="position:relative;">
                <?= icon_bag(20) ?>
                <span class="badge-dot" id="nav-cart-count" style="display:none;"></span>
            </a>
        </div>
    </div>
</header>

<?php if ($show_catbar): ?>
<div class="catbar">
    <div class="container">
        <div class="catbar-inner">
            <?php
            $cats = [
                ['id' => 'all',         'label' => 'All',         'color' => '#2A1A1D'],
                ['id' => 'food',        'label' => 'Food',        'color' => '#E0777D'],
                ['id' => 'clothing',    'label' => 'Clothing',    'color' => '#8E3B46'],
                ['id' => 'electronics', 'label' => 'Electronics', 'color' => '#4C86A8'],
                ['id' => 'education',   'label' => 'Education',   'color' => '#477890'],
                ['id' => 'beauty',      'label' => 'Beauty',      'color' => '#C8A87A'],
            ];
            foreach ($cats as $cat):
                $is_active = ($active_cat === $cat['id']);
                $dot_style = $is_active
                    ? 'background:rgba(225,221,143,.25);color:var(--lime);'
                    : 'background:' . $cat['color'] . '22;color:' . $cat['color'] . ';';
            ?>
            <a href="/limedime/category.php?cat=<?= $cat['id'] ?>" class="cat-chip <?= $is_active ? 'active' : '' ?>">
                <?php if ($cat['id'] !== 'all'): ?>
                <span class="cat-dot" style="<?= $dot_style ?>"><?= cat_icon($cat['id'], 13) ?></span>
                <?php endif; ?>
                <span><?= $cat['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    var uid = '<?= $_nav_user ? (int)$_nav_user['id'] : 0 ?>';
    var stored = localStorage.getItem('ld_cart_uid') || '0';
    if (uid !== '0' && stored !== uid) {
        localStorage.removeItem('ld_cart');
        localStorage.removeItem('ld_cart_meta');
        localStorage.setItem('ld_cart_uid', uid);
        document.cookie = 'ld_cart_count=0;path=/;max-age=31536000';
    } else if (uid === '0' && stored !== '0') {
        localStorage.removeItem('ld_cart');
        localStorage.removeItem('ld_cart_meta');
        localStorage.removeItem('ld_cart_uid');
        document.cookie = 'ld_cart_count=0;path=/;max-age=31536000';
    }
    function updateNavCount() {
        var c = JSON.parse(localStorage.getItem('ld_cart') || '{}');
        var n = Object.values(c).reduce(function(a, b) { return a + (parseInt(b) || 0); }, 0);
        var el = document.getElementById('nav-cart-count');
        if (el) { el.style.display = n > 0 ? '' : 'none'; el.textContent = n > 9 ? '9+' : (n || ''); }
        document.cookie = 'ld_cart_count=' + n + ';path=/;max-age=31536000';
    }
    window.updateNavCount = updateNavCount;
    document.addEventListener('DOMContentLoaded', updateNavCount);
})();
</script>
