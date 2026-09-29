<?php $this->layout('layout::login') ?>
<h1>Set up two-factor authentication</h1>
<form id="loginForm" action="/login/<?=$this->e($data['entityType'])?>/twoFactorSetupConfirm/" method="post">
    <?php if (isset($messages) && $messages !== ''): ?>
        <div id="messageContainer"><?=$messages?></div>
    <?php endif; ?>

    <p>Scan this with your authenticator app, then enter the code it shows.</p>

    <?php if (!empty($data['qrImage'])): ?>
        <div class="inputContainer">
            <img src="<?=$this->e($data['qrImage'])?>" alt="Two-factor QR code">
        </div>
    <?php endif; ?>

    <?php
    /**
     * Always offered, not only as a fallback: the framework ships no QR renderer, and every
     * authenticator app accepts a typed secret. See QrCodeRendererInterface.
     */
    ?>
    <p>Or enter this code by hand:</p>
    <p><code><?=$this->e(implode(' ', str_split($data['secret'], 4)))?></code></p>

    <div class="inputContainer">
        <input class="input" data-required="true" data-required-text="The code is required."
               aria-label="Authentication code" type="text" name="code" autofocus
               inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" placeholder="000000">
    </div>
    <?=$this->formToken()?>
    <button class="btn primary fullWidth" type="submit">Confirm</button>
</form>
