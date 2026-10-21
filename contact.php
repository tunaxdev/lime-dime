<?php
include '_partials/icons.php';
include '_partials/session.php';
include '_partials/connect.php';
$page_title  = 'Contact';
$active_route = '';
$show_catbar  = false;
$sent = ($_GET['sent'] ?? '') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first   = trim($_POST['first_name'] ?? '');
  $last    = trim($_POST['last_name']  ?? '');
  $email   = trim($_POST['email']      ?? '');
  $subject = trim($_POST['subject']    ?? '');
  $msg     = trim($_POST['message']    ?? '');
    if ($first && $email && $subject && $msg) {
        $sf = mysqli_real_escape_string($conn, $first);
        $sl = mysqli_real_escape_string($conn, $last);
        $se = mysqli_real_escape_string($conn, $email);
        $ss = mysqli_real_escape_string($conn, $subject);
        $sm = mysqli_real_escape_string($conn, $msg);
        mysqli_query($conn, "INSERT INTO contact_messages (first_name, last_name, email, subject, message) VALUES ('$sf', '$sl', '$se', '$ss', '$sm')");
    }
  header('Location: /limedime/contact.php?sent=1');
  exit;
}
include '_partials/header.php';
?>

<div class="container page" style="max-width:780px;">

  <div class="eyebrow" style="margin-bottom:8px;">
    <a href="/limedime/index.php" style="color:var(--ink-3);">Home</a> · Contact
  </div>
  <h1 class="h1" style="margin:0 0 6px;">Get in touch</h1>
  <p class="muted" style="margin:0 0 36px;font-size:15px;">We usually reply within one business day.</p>

  <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:40px;">

    <!-- Form -->
    <div>
      <?php if ($sent): ?>
        <div style="background:rgba(80,160,60,.1);border:1px solid #60b040;border-radius:var(--r);padding:16px 20px;color:#3a7a28;margin-bottom:24px;">
          Message sent! We'll get back to you shortly.
        </div>
      <?php endif; ?>
      <form method="post" action="/limedime/contact.php">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
          <div class="field"><label>First name</label><input name="first_name" placeholder="Jules" required></div>
          <div class="field"><label>Last name</label><input name="last_name" placeholder="Rapp"></div>
        </div>
        <div class="field" style="margin-bottom:14px;"><label>Email</label><input type="email" name="email" placeholder="jules@email.com" required></div>
        <div class="field" style="margin-bottom:14px;">
          <label>Subject</label>
          <select name="subject" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);background:var(--surface);font-size:14px;color:var(--ink);">
            <option>Order issue</option>
            <option>Seller inquiry</option>
            <option>Courier support</option>
            <option>Account help</option>
            <option>Partnership</option>
            <option>Other</option>
          </select>
        </div>
        <div class="field" style="margin-bottom:18px;">
          <label>Message</label>
          <textarea name="message" rows="5" placeholder="Tell us what's on your mind…" style="width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--r);font-family:inherit;font-size:14px;resize:vertical;box-sizing:border-box;" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;display:flex;justify-content:center;">Send message</button>
      </form>
    </div>

    <!-- Contact info -->
    <div style="display:flex;flex-direction:column;gap:14px;">
      <?php foreach (
        [
          ['Email',   'support@limedime.com',     icon_check(18)],
          ['Phone',   '+90 (542) 867-01-92',         icon_check(18)],
          ['Hours',   'Mon–Fri, 9am–6pm PT',       icon_clock(18)],
          ['Address', '123, Famagusta ', icon_pin(18)],
        ] as [$label, $val, $icon]
      ): ?>
        <div class="card" style="padding:18px;display:flex;align-items:flex-start;gap:14px;">
          <span style="width:36px;height:36px;border-radius:10px;background:var(--lime);color:var(--wine);display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?= $icon ?></span>
          <div>
            <div class="eyebrow" style="font-size:10px;margin-bottom:3px;"><?= $label ?></div>
            <div style="font-weight:600;font-size:14px;"><?= $val ?></div>
          </div>
        </div>
      <?php endforeach; ?>

      <div style="padding:18px;background:var(--surface-2);border-radius:var(--r);border:1px dashed var(--line);font-size:13.5px;color:var(--ink-2);line-height:1.6;">
        For seller or courier account issues, please include your registered email and shop/account name so we can help faster.
      </div>
    </div>

  </div>
</div>

<?php include '_partials/footer.php'; ?>