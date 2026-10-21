<?php
include '_partials/icons.php';
include '_partials/session.php';
$page_title  = 'Terms of service';
$active_route = '';
$show_catbar  = false;
include '_partials/header.php';
?>

<div class="container page" style="max-width:720px;">

  <div class="eyebrow" style="margin-bottom:8px;">
    <a href="/limedime/index.php" style="color:var(--ink-3);">Home</a> · Terms of service
  </div>
  <h1 class="h1" style="margin:0 0 6px;">Terms of service</h1>
  <p class="muted" style="margin:0 0 36px;">Last updated: January 1, 2026. By using Lime&amp;Dime you agree to these terms.</p>

  <?php
  $sections = [
    ['Using the platform', 'Lime&Dime is a marketplace connecting buyers with independent sellers. You must be 18 or older to create an account. You are responsible for maintaining the confidentiality of your login credentials.'],
    ['Buyer responsibilities', 'When you place an order, you enter a contract with the seller — not with Lime&Dime. We facilitate the transaction but are not party to it. Ensure your delivery address and payment details are accurate.'],
    ['Seller responsibilities', 'Sellers are independent businesses. They are responsible for the accuracy of their listings, the quality of their products, and fulfilling orders within the stated timeframe. Lime&Dime reserves the right to remove listings that violate platform policies.'],
    ['Payments', 'Payments are processed securely. Lime&Dime charges a platform fee on each completed order. Sellers receive payouts within 3–5 business days of confirmed delivery.'],
    ['Returns and refunds', 'Each seller sets their own return policy, visible on their shop page. In cases of non-delivery or significant item mismatch, buyers may raise a dispute through Lime&Dime support within 14 days of the expected delivery date.'],
    ['Prohibited content', 'Users may not list or purchase illegal items, counterfeit goods, hazardous materials, or content that violates intellectual property rights. Violations result in immediate suspension.'],
    ['Limitation of liability', 'Lime&Dime is not liable for the quality of products sold by third-party sellers, delays caused by couriers, or losses arising from unauthorised account access due to user negligence.'],
    ['Termination', 'We reserve the right to suspend or terminate accounts that violate these terms, at our sole discretion and without prior notice. You may close your account at any time.'],
    ['Governing law', 'These terms are governed by the laws of the State of Oregon, USA. Any disputes shall be resolved in the courts of Portland, Oregon.'],
  ];
  foreach ($sections as $i => [$title, $body]):
  ?>
    <div style="padding:22px 0;border-top:1px <?= $i === 0 ? 'solid' : 'dashed' ?> var(--line);">
      <h3 style="margin:0 0 10px;font-size:17px;"><?= ($i + 1) ?>. <?= $title ?></h3>
      <p style="margin:0;color:var(--ink-2);line-height:1.75;font-size:14.5px;"><?= $body ?></p>
    </div>
  <?php endforeach; ?>

</div>

<?php include '_partials/footer.php'; ?>