<?php $this->layout('layout::login') ?>
<h1>Log in</h1>
<?php
/**
 * The button, not the link, is what spends the token.
 *
 * Mail clients and in-app browsers prefetch URLs to build previews. When following the link
 * consumed the token, that prefetch burned it before the recipient ever tapped it — reliably
 * on phones, never on desktop. Prefetchers issue GET and never POST, so the confirmation
 * step is what makes this immune.
 */
?>
<form id="loginForm" action="/login/<?=$this->e($data['entityType'])?>/verifyMagicLink/" method="post">
    <?php if (isset($messages) && $messages !== ''): ?>
        <div id="messageContainer"><?=$messages?></div>
    <?php endif; ?>
    <p>Click below to finish signing in.</p>
    <input type="hidden" name="token" value="<?=$this->e($data['token'])?>">
    <button class="btn primary fullWidth" type="submit">Log in</button>
</form>
