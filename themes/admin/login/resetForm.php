<?php $this->layout('layout::login') ?>
<h1>Reset Password</h1>
<script src="https://www.google.com/recaptcha/api.js"></script>
<script>
    function onSubmit(token) {
        document.getElementById("loginForm").submit();
    }
</script>
<form id="loginForm" action="/login/resetPassword/<?=$data['token']?>/" method="post">
    <?php if((isset($messages) && $messages !== '') || isset($data['error'])):?>
        <div id="messageContainer">
            <?php if ($data['error']): ?>
                <div class="message error">
                    <?=$data['error']?>
                </div>
            <?php endif;?>
            <?=$messages ?? ''?>
        </div>
    <?php endif;?>
    <div class="inputContainer">
        <input class="input" data-required="true" data-required-text="Password is required" aria-label="Password" type="password" name="password" autofocus placeholder="Password">
    </div>
    <div class="inputContainer">
        <input class="input" data-required="true" data-required-text="Repeated password is required" aria-label="Password" type="password" name="password2" autofocus placeholder="Repeat Password">
    </div>
    <?=$this->formToken()?>
    <button class="g-recaptcha btn primary fullWidth"
            data-sitekey="<?=$captchaSiteKey?>"
            data-callback='onSubmit'
            data-action='submit'><?=$this->t('Send')?></button>
</form>