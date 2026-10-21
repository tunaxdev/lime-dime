<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

$cat_filter   = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$allowed_cats = ['all', 'food', 'clothing', 'electronics', 'education', 'beauty'];
if (!in_array($cat_filter, $allowed_cats)) $cat_filter = 'all';

if ($cat_filter === 'all') {
    $filtered = $SELLERS;
} else {
    $filtered = [];
    foreach ($SELLERS as $s) {
        if ($s['cat'] === $cat_filter) $filtered[] = $s;
    }
}

$page_title   = 'Sellers — Lime&Dime';
$active_route = 'sellers';
$show_catbar  = false;
include '_partials/header.php';
?>

<div class="container page">

    <div class="eyebrow" style="margin-bottom:8px;">
        <a href="/limedime/index.php" style="color:var(--ink-3);">Home</a> · Sellers
    </div>

    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
        <div>
            <h1 class="h1" style="margin:0;">All sellers</h1>
            <p class="muted" style="margin-top:6px;margin-bottom:0;"><?= count($filtered) ?> shops · curated local businesses</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php foreach ($CATEGORIES as $c): ?>
            <a href="/limedime/sellers.php?cat=<?= $c['id'] ?>"
               class="cat-chip <?= $cat_filter === $c['id'] ? 'active' : '' ?>"
               style="<?= $cat_filter === $c['id'] ? '' : 'background:var(--surface);' ?>">
                <?php if ($c['id'] !== 'all'): ?>
                <span class="cat-dot" style="background:<?= $c['color'] ?>22;color:<?= $c['color'] ?>;"><?= cat_icon($c['id'], 13) ?></span>
                <?php endif; ?>
                <span><?= $c['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (count($filtered) > 0): ?>
    <div class="grid grid-sellers">
        <?php foreach ($filtered as $s): ?>
        <article class="seller-card" onclick="location.href='/limedime/seller.php?id=<?= $s['id'] ?>';" style="cursor:pointer;">
            <div class="seller-cover" style="<?= !empty($s['cover_image']) ? 'background:#111;' : '' ?>">
                <?php if (!empty($s['cover_image'])): ?>
                <img src="/limedime/assets/img/shops/<?= htmlspecialchars($s['cover_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                <?php else: ?>
                <?= seller_cover($s['accent'], $s['pattern']) ?>
                <?php endif; ?>
            </div>
            <div class="seller-body">
                <div class="seller-avatar" style="background:<?= $s['accent'] ?>;color:var(--lime);"><?= $s['avatar'] ?></div>
                <div class="seller-name"><?= $s['name'] ?></div>
                <div class="seller-sub"><?= $s['tag'] ?></div>
                <div class="seller-stats">
                    <span><b><?= $s['products'] ?></b> products</span>
                    <span><b><?= $s['followers'] ?></b> followers</span>
                    <span style="display:inline-flex;gap:4px;align-items:center;"><?= icon_star(11) ?> <b><?= $s['rating'] ?></b></span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <h3 class="h3" style="margin:0 0 6px;">No sellers in this category yet</h3>
        <p class="muted" style="margin:0;">New sellers join every week — check back soon.</p>
        <a href="/limedime/sellers.php" class="btn btn-primary" style="margin-top:16px;">See all sellers</a>
    </div>
    <?php endif; ?>

    <div style="margin-top:56px;padding:36px;background:linear-gradient(120deg,var(--wine) 0%,var(--coral) 100%);border-radius:var(--r-lg);text-align:center;color:var(--lime);">
        <div class="eyebrow" style="color:var(--lime-soft);">Are you a maker?</div>
        <h2 style="font-family:Fraunces,serif;font-size:32px;font-weight:700;margin:8px 0 12px;color:var(--lime);">Open your shop on Lime&amp;Dime</h2>
        <p style="color:rgba(238,234,181,.8);max-width:480px;margin:0 auto 22px;font-size:15px;">Join 1,240 sellers. 8.5% platform fee, no monthly cost. Start selling in minutes.</p>
        <a href="/limedime/manager-register.php" class="btn btn-lg" style="background:var(--lime);color:var(--wine-deep);font-weight:700;">Open a shop</a>
    </div>

</div>

<?php include '_partials/footer.php'; ?>
