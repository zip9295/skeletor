<?php

const PORTRAIT_300x410 = 'portrait_300x410';
const PORTRAIT_600x820 = 'portrait_600x820';
const LANDSCAPE_900x600 = 'landscape_900x600';
const LANDSCAPE_1800x1200 = 'landscape_1800x1200';
const LANDSCAPE_600x400 = 'landscape_600x400';
const SLIDER_1920x644 = 'slider_1920x644';
const SLIDER_1400x469 = 'slider_1400x469';

return array(
    'baseUrl' => 'http://skeleton.local',
    'adminUrl' => 'http://skeleton.local',
    'redirectUri' => '/user/view/',
    'adminPath' => '',  // use only in local config
    // Which ways in this application has. See Skeletor\Core\Security\AuthPolicy.
    // Anything not listed here is switched off: its endpoints 404, and credentials for it are
    // refused by the authenticator registry even if a route or an ACL entry lets them past.
    'auth' => [
        'methods'   => ['password', 'magic_link'],
        'default'   => 'password',
        'twoFactor' => false,
    ],
    'twoFactor' => [
        'issuer' => 'Skeletor',
        // Only read when auth.twoFactor is on. Put the real value in config-local.php;
        // SecretCipher refuses anything shorter than 16 characters.
        'encryptionKey' => '',
    ],
    'ignoreTrailingSlash' => true,
    'db' => [
        'host' => 'localhost',
        'name' => 'skeleton',
        'user' => 'root',
        'pass' => 'rootpass'
    ],
    'middleware' => [
        0 => \Skeletor\Core\Middleware\MiddlewareInterface::class
    ],
    'mailer' => [
        'generalError' => 'djavolak@mailbox.org',
        'from' => 'djavolak@mailbox.org',
        'server' => []
    ],
    'passwordValidation' => 'loose', // 'loose' = min 10 chars | 'strict' = 10 chars + uppercase + lowercase + number + special char
    'captcha' => [
        'siteKey' => ''
    ],
    'cropSizes' => [
        PORTRAIT_300x410 => [300, 410, true],
        PORTRAIT_600x820 => [600, 820, true],

        LANDSCAPE_900x600 => [900, 600, true],
        LANDSCAPE_1800x1200 => [1800, 1200, true],
        LANDSCAPE_600x400 => [600, 400, true],

        SLIDER_1920x644 => [1920, 644, true],
        SLIDER_1400x469 => [1400, 469, true],
    ],
    'versionString' => '0.0.1'
);