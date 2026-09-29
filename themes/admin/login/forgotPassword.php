<?php $this->layout('layout::login') ?>
<h1>Forgot Password</h1>
<script src="https://www.google.com/recaptcha/api.js"></script>
<script>
    function onSubmit(token) {
        document.getElementById("loginForm").submit();
    }
</script>
<form id="loginForm" action="/login/forgotPassword/" method="post">
    <?php if((isset($messages) && $messages !== '') || (isset($data['sent']) && $data['sent'])):?>
        <div id="messageContainer">
            <?php if ($data['sent']): ?>
                <div class="message success">
                    <?=$this->t('Email with instruction how to reset password has been sent to your email address. Please check spam folders.')?>
                </div>
            <?php endif;?>
            <?=$messages ?? ''?>
        </div>
    <?php endif;?>
    <div class="inputContainer">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
            <path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/>
        </svg>
        <input class="input" data-required="true" data-required-text="Email is a required field." data-validation-strategy="email" data-validation-strategy-message="Invalid email provided" aria-label="Email" type="text" name="email" autofocus placeholder="Email">
    </div>
    <?=$this->formToken()?>
    <button class="g-recaptcha btn primary fullWidth"
            data-sitekey="<?=$captchaSiteKey?>"
            data-callback='onSubmit'
            data-action='submit'><?=$this->t('Send')?></button>
</form>