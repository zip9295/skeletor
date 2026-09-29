<?php $this->layout('layout::login') ?>
<h1>Two-factor authentication</h1>
<?php
/**
 * Nothing here identifies the account. The pending login lives in the session, and the
 * controller reads it from there — a hidden field naming the user would let whoever posts
 * this form choose which account the code is checked against.
 */
?>
<form id="loginForm" action="/login/<?=$this->e($data['entityType'])?>/twoFactor/" method="post">
    <?php if (isset($messages) && $messages !== ''): ?>
        <div id="messageContainer"><?=$messages?></div>
    <?php endif; ?>
    <p>Enter the six-digit code from your authenticator app.</p>
    <div class="inputContainer">
        <input class="input" data-required="true" data-required-text="The code is required."
               aria-label="Authentication code" type="text" name="code" autofocus
               inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" placeholder="000000">
    </div>
    <?=$this->formToken()?>
    <button class="btn primary fullWidth" type="submit">Verify</button>
</form>
