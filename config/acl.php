<?php

$guest = [
    '/',
    '/login/user/*',
    '/login/login',
    '/login/loginForm',
    '/login/forgotPassword*',
    '/login/resetPassword',
    '/login/index',
    '/user/recover',
    '/git/bbHook'
];

$level2 = array_merge($guest, [
    '/login/user/logout/',
    '/theme/*',
    '/navigation/*',
    '/social/*'
]);

//can also see everything level2 can see
$level1 = array_merge($level2, [
    '/login/user/logout/',
    '/user/*',
    '/image/*',
    '/file/*',
    '/',
]);

return [
    0 => $guest,
    1 => $level1,
    5 => $level2
];