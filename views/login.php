<?php use App\Csrf; use App\Config; ?>
<section class="auth-card">
  <div class="logo-wrap">
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSMQGtAX5lSAr05-o7uYqfqE38PyfAz1AmLjA&s" alt="KMS Logo">
    <h1>KMS Login</h1>
  </div>

  <?php if (!empty($flash['error'])): ?>
    <div class="alert error"><?= htmlspecialchars($flash['error']) ?></div>
  <?php endif; ?>
  <?php if (!empty($flash['success'])): ?>
    <div class="alert success"><?= htmlspecialchars($flash['success']) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= htmlspecialchars(Config::BASE_URL) ?>?route=login_post" class="form">
    <?= Csrf::field() ?>
    <label>E-Mail
      <input type="email" name="email" required placeholder="name@bernauer.ch">
    </label>
    <label>Passwort
      <input type="password" name="password" required placeholder="••••••••••••">
    </label>
    <button class="btn primary" type="submit">Anmelden</button>
  </form>
</section>
