<?php
include '_partials/icons.php';
include '_partials/session.php';
$page_title  = 'Privacy policy';
$active_route = '';
$show_catbar  = false;
include '_partials/header.php';
?>

<div class="container page" style="max-width:720px;">

  <div class="eyebrow" style="margin-bottom:8px;">
    <a href="/limedime/index.php" style="color:var(--ink-3);">Home</a> · Privacy policy
  </div>
  <h1 class="h1" style="margin:0 0 6px;">Privacy policy</h1>
  <p class="muted" style="margin:0 0 36px;">Last updated: January 1, 2026</p>

  <?php
  $sections = [
    ['What we collect', 'We collect information you give us directly — name, email, shipping address, and payment method — when you create an account or place an order. We also collect browsing data (pages visited, time on site) to improve the platform.'],
    ['How we use it', 'Your data is used to process orders, send transactional emails (order confirmations, shipping updates), and improve the platform. We do not sell your personal information to third parties — ever.'],
    ['Cookies', 'Lime&Dime uses cookies to keep you signed in, remember your cart, and measure site performance. You can clear cookies at any time through your browser settings.'],
    ['Sharing your data', 'We share your name and delivery address with the seller who fulfilled your order, and with the courier handling delivery. We share anonymised, aggregate data with analytics providers.'],
    ['Data retention', 'Account data is retained while your account is active. You may request deletion of your account and associated data at any time by contacting support@limedime.com.'],
    ['Your rights', 'You have the right to access, correct, or delete the personal data we hold about you. To exercise these rights, email us at privacy@limedime.com.'],
    ['Security', 'All data is transmitted over HTTPS. Payment card details are never stored on our servers — they are processed by our payment provider and tokenised immediately.'],
    ['Changes to this policy', 'We may update this policy from time to time. We\'ll notify registered users by email if the changes are material. Continued use of the platform constitutes acceptance.'],
    ['Contact', 'Questions about privacy? Email us at privacy@limedime.com or write to: Lime&Dime Ltd, 123 Market Street, Portland OR 97201.'],
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