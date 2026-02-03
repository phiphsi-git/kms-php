<section class="login-box" style="max-width:350px; margin:50px auto; text-align:center; padding:30px; background:#fff; border:1px solid #ddd; border-radius:8px;">
    <h2>2-Faktor-Schutz</h2>
    <p>Bitte geben Sie den Code aus Ihrer Authenticator-App ein.</p>
    
    <form method="post" action="?route=login_2fa_check">
        <input type="hidden" name="csrf" value="<?= \App\Csrf::token() ?>">
        <input type="text" name="code" placeholder="123 456" required autofocus autocomplete="off" style="font-size:1.5em; text-align:center; width:100%; letter-spacing:5px; padding:10px; margin-bottom:20px;">
        
        <button type="submit" class="btn-primary" style="width:100%; padding:10px;">Verifizieren</button>
    </form>
    <br>
    <a href="?route=logout" style="font-size:0.9em; color:#888;">Abbrechen</a>
</section>