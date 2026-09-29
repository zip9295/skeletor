<?php

/**
 * Define routes here.
 *
 * Routes follow the format:
 *
 * [METHOD, ROUTE, CALLABLE]
 *
 * Routes can use optional segments and regular expressions. See nikic/fastroute
 */

use Skeletor\File\Controller\FileController;
use Skeletor\Image\Controller\ImageController;
use Skeletor\Core\Login\Controller\LoginController;
use Skeletor\ThemeSettings\Controller\ThemeSettingsController;
use Skeletor\ThemeSettings\Navigation\Controller\NavigationController;
use Skeletor\ThemeSettings\SocialLinks\Controller\SocialLinksController;
use Skeletor\Blog\Controller\CategoryController;
use Skeletor\Blog\Controller\PostController;
use Skeletor\Blog\Controller\TagController;
use Skeletor\Author\Controller\AuthorController;
use Skeletor\Lead\Controller\LeadController;
use Skeletor\Page\Controller\PageController;
use Skeletor\Reference\Controller\ReferenceController;
use Skeletor\User\Controller\UserController;

return [
    [['GET', 'POST'], '/', [LoginController::class, 'loginForm']],
    [['GET', 'POST'], '/login/user/{action}/', LoginController::class],
    [['GET', 'POST'], '/user/{action}/[{id}/]', UserController::class],
    [['GET', 'POST'], '/image/{action}/[{id}/]', ImageController::class],
    [['GET', 'POST'], '/page/{action}/[{id}/]', PageController::class],
    [['GET', 'POST'], '/activity/{action}/[{id}/]', \Skeletor\Core\Activity\Controller\ActivityController::class],
    [['GET', 'POST'], '/translator/{action}/[{id}/]', \Skeletor\Translator\Controller\TranslatorController::class],

    [['GET', 'POST'], '/file/{action}/[{id}/]', FileController::class],
    [['GET', 'POST'], '/theme/{action}/', ThemeSettingsController::class],
    [['POST', 'GET'], '/navigation/{action}/[{id}/]', NavigationController::class],
    [['POST', 'GET'], '/social/{action}/[{id}/]', SocialLinksController::class],
    [['POST', 'GET'], '/post/{action}/[{id}/]', PostController::class],
    [['POST', 'GET'], '/category/{action}/[{id}/]', CategoryController::class],
    [['POST', 'GET'], '/tag/{action}/[{id}/]', TagController::class],
    [['POST', 'GET'], '/reference/{action}/[{id}/]', ReferenceController::class],
    [['POST', 'GET'], '/lead/{action}/[{id}/]', LeadController::class],
    [['POST', 'GET'], '/author/{action}/[{id}/]', AuthorController::class],
];
