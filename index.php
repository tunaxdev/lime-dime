<?php
include '_partials/data.php';
include '_partials/icons.php';
include '_partials/illustrations.php';

$page_title  = 'A warm little marketplace';
$active_route = 'home';
$show_catbar  = true;
$active_cat   = 'all';
include '_partials/header.php';
?>

<div class="container page">

    <div class="hero-c" style="background:linear-gradient(135deg,var(--wine) 0%,var(--wine-deep) 100%);margin-bottom:36px;">
        <div style="position:absolute;inset:0;overflow:hidden;">
            <div style="position:absolute;width:320px;height:320px;border-radius:50%;background:var(--lime);opacity:.12;filter:blur(48px);top:-60px;left:10%;animation:float 18s ease-in-out infinite;"></div>
            <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:var(--coral);opacity:.18;filter:blur(36px);top:20px;right:15%;animation:float 13s ease-in-out infinite 2s;"></div>
            <div style="position:absolute;width:260px;height:260px;border-radius:50%;background:var(--lime-soft);opacity:.1;filter:blur(44px);bottom:-40px;left:40%;animation:float 22s ease-in-out infinite 4s;"></div>
        </div>
        <h1 style="position:relative;z-index:1;color:var(--lime);">Spend the change.<br><em style="font-style:italic;color:var(--coral-soft);">Find your flavour.</em></h1>
    </div>

    <section style="margin-bottom:44px;">
        <div class="section-head">
            <div>
                <div class="eyebrow">This week · Paid placement</div>
                <h2 class="h2">Featured sellers</h2>
            </div>
            <a href="/limedime/sellers.php">See all sellers <?= icon_chev(14) ?></a>
        </div>
        <div class="grid grid-sellers">
            <?php foreach ($SELLERS as $s): ?>
            <a href="/limedime/seller.php?id=<?= $s['id'] ?>" class="seller-card" style="text-decoration:none;">
                <div class="seller-cover" style="<?= !empty($s['cover_image']) ? 'background:#111;' : '' ?>">
                    <?php if (!empty($s['cover_image'])): ?>
                    <img src="/limedime/assets/img/shops/<?= htmlspecialchars($s['cover_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                    <?php else: ?>
                    <?= seller_cover($s['accent'], $s['pattern']) ?>
                    <?php endif; ?>
                </div>
                <div class="seller-body">
                    <div class="seller-avatar" style="background:<?= $s['accent'] ?>;color:var(--lime);"><?= $s['avatar'] ?></div>
                    <div class="seller-name"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="seller-sub"><?= htmlspecialchars($s['tag']) ?></div>
                    <div class="seller-stats">
                        <span><b><?= $s['products'] ?></b> products</span>
                        <span><b><?= $s['followers'] ?></b> followers</span>
                        <span style="display:inline-flex;gap:4px;align-items:center;"><?= icon_star(11) ?> <b><?= $s['rating'] ?></b></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section style="margin-bottom:44px;">
        <div class="section-head">
            <div>
                <div class="eyebrow">From every shelf</div>
                <h2 class="h2">Picked at random</h2>
            </div>
            <a href="/limedime/category.php?cat=all">Shop all products <?= icon_chev(14) ?></a>
        </div>
        <div class="grid grid-products">
            <?php foreach ($PRODUCTS as $p):
                $cat = find_category($p['cat'], $CATEGORIES);
                $cat_color = $cat ? $cat['color'] : '#2A1A1D';
            ?>
            <a href="/limedime/product.php?id=<?= $p['id'] ?>" class="product-card card" style="text-decoration:none;">
                <div class="product-thumb">
                    <?= product_thumb($p) ?>
                    <?php if ($p['quantity'] <= 0): ?><span class="product-tag" style="background:var(--ink-3);">Sold out</span><?php elseif ($p['tag']): ?><span class="product-tag"><?= $p['tag'] ?></span><?php endif; ?>
                    <button class="product-fav" data-fav="<?= $p['id'] ?>" onclick="event.preventDefault();toggleFav('<?= $p['id'] ?>')" aria-label="Add to favourites"><?= icon_heart(16) ?></button>
                </div>
                <div class="product-body">
                    <div class="product-seller">
                        <span style="width:6px;height:6px;border-radius:999px;background:<?= $cat_color ?>;"></span>
                        <span><?= htmlspecialchars($p['seller']) ?></span>
                    </div>
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="product-meta">
                        <span class="product-price">$<?= number_format($p['price'], 2) ?></span>
                        <span class="product-rating"><?= icon_star(12) ?> <?= $p['rating'] ?> <span style="color:var(--ink-4);font-weight:500;">(<?= $p['reviews'] ?>)</span></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

</div>

<?php include '_partials/footer.php'; ?>
