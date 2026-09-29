<?php $this->layout('layout::standard') ?>
<!-- Top bar start -->
<div id="mainTop">
    <h1>Theme Settings</h1>
</div>
<!-- Top bar end -->
<div id="themeSettingsContainer">
    <ul id="themeSettingsNavigation">
        <li data-type="navigation" data-endpoint="/navigation/view/">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                <path d="M0 96C0 78.3 14.3 64 32 64l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 128C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32L32 448c-17.7 0-32-14.3-32-32s14.3-32 32-32l384 0c17.7 0 32 14.3 32 32z"/>
            </svg>
            Navigation
        </li>
        <li data-type="social" data-endpoint="/social/view/">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                <path d="M352 224c53 0 96-43 96-96s-43-96-96-96s-96 43-96 96c0 4 .2 8 .7 11.9l-94.1 47C145.4 170.2 121.9 160 96 160c-53 0-96 43-96 96s43 96 96 96c25.9 0 49.4-10.2 66.6-26.9l94.1 47c-.5 3.9-.7 7.8-.7 11.9c0 53 43 96 96 96s96-43 96-96s-43-96-96-96c-25.9 0-49.4 10.2-66.6 26.9l-94.1-47c.5-3.9 .7-7.8 .7-11.9s-.2-8-.7-11.9l94.1-47C302.6 213.8 326.1 224 352 224z"/>
            </svg>
            Social
        </li>
    </ul>
    <div id="themeSettingsContent">

    </div>
</div>


<script type="module" src="<?=ADMIN_ASSET_URL . '/js/themeSettings.js'?>"></script>
