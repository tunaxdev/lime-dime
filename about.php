<?php
include '_partials/icons.php';
include '_partials/session.php';
$page_title  = 'About us';
$active_route = '';
$show_catbar  = false;
include '_partials/header.php';
?>

<div class="container page" style="max-width:780px;">

  <div class="eyebrow" style="margin-bottom:8px;">
    <a href="/limedime/index.php" style="color:var(--ink-3);">Home</a> · About us
  </div>

  <h1 class="h1" style="margin:0 0 6px;">We're Lime&amp;Dime.</h1>
  <p class="muted" style="margin:0 0 40px;font-size:16px;line-height:1.6;">A warm little marketplace built for small sellers and curious shoppers.</p>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;margin-bottom:48px;">
    <div>
      <h2 class="h2" style="margin:0 0 14px;">Our story</h2>
      <p style="color:var(--ink-2);line-height:1.75;margin:0 0 14px;">
        Lime&amp;Dime started in 2022 with a simple idea: make it easy for talented makers, bakers, and creators to reach people who actually care about what they buy.
      </p>
      <p style="color:var(--ink-2);line-height:1.75;margin:0;">
        We were tired of marketplaces where small sellers get buried under algorithm-driven ads and rock-bottom pricing wars. We built something different — a place where craftsmanship is celebrated and every purchase feels personal.
      </p>
    </div>
    <div style="background:linear-gradient(135deg,var(--wine) 0%,var(--wine-deep) 100%);border-radius:var(--r-lg);padding:32px;color:var(--lime);display:flex;flex-direction:column;justify-content:center;">
      <div style="font-family:Fraunces,serif;font-size:48px;font-weight:700;line-height:1;margin-bottom:8px;">1,240</div>
      <div style="font-size:13px;opacity:.7;margin-bottom:24px;">independent sellers</div>
      <div style="font-family:Fraunces,serif;font-size:48px;font-weight:700;line-height:1;margin-bottom:8px;">84k</div>
      <div style="font-size:13px;opacity:.7;margin-bottom:24px;">happy shoppers</div>
      <div style="font-family:Fraunces,serif;font-size:48px;font-weight:700;line-height:1;margin-bottom:8px;">2022</div>
      <div style="font-size:13px;opacity:.7;">founded</div>
    </div>
  </div>

  <h2 class="h2" style="margin:0 0 18px;">What we believe</h2>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:48px;">
    <?php foreach (
      [
        ['Small is beautiful', 'Independent sellers bring personality, quality, and story to every product. We put them first — always.'],
        ['Fair for everyone',  'Transparent fees, no hidden costs, and a support team that actually picks up the phone.'],
        ['Local first',        'We prioritise sellers in your city and region so your purchase supports someone nearby.'],
      ] as [$title, $body]
    ): ?>
      <div class="card" style="padding:24px;">
        <div style="width:36px;height:36px;border-radius:10px;background:var(--lime);color:var(--wine);display:flex;align-items:center;justify-content:center;margin-bottom:14px;"><?= icon_check(18) ?></div>
        <h3 style="margin:0 0 8px;font-size:16px;"><?= $title ?></h3>
        <p class="muted" style="margin:0;font-size:13.5px;line-height:1.6;"><?= $body ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="padding:32px;background:var(--surface-2);border-radius:var(--r-lg);border:1px dashed var(--line);text-align:center;">
    <h3 class="h3" style="margin:0 0 10px;">Want to sell with us?</h3>
    <p class="muted" style="margin:0 0 18px;">Open your shop in minutes. No listing fees, no monthly subscriptions.</p>
    <a href="/limedime/manager-register.php" class="btn btn-primary">Open a shop</a>
  </div>

</div>

<?php include '_partials/footer.php'; ?>