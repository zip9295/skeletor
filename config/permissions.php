<?php

use Skeletor\User\Entity\User;

return [
    'permissions' => [
        'user.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'user.view' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'user.create' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'user.edit' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'user.delete' => [User::ROLE_ADMIN, User::ROLE_STAFF],

        'page.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'page.create' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'page.edit' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'page.delete' => [User::ROLE_ADMIN, User::ROLE_STAFF],

        'activity.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],

        'image.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'image.manage' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'file.manage' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'theme.manage' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'translator.manage' => [User::ROLE_ADMIN, User::ROLE_STAFF],

        'post.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'post.create' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'post.edit' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'post.delete' => [User::ROLE_ADMIN, User::ROLE_STAFF],

        'category.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'category.create' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'category.edit' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'category.delete' => [User::ROLE_ADMIN, User::ROLE_STAFF],

        'tag.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'tag.create' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'tag.edit' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'tag.delete' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'tag.create_or_get' => [User::ROLE_ADMIN, User::ROLE_STAFF],

        'reference.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'reference.create' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'reference.edit' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'reference.delete' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'lead.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'lead.create' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'lead.edit' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'lead.delete' => [User::ROLE_ADMIN, User::ROLE_STAFF],

        'author.view_list' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'author.create' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'author.edit' => [User::ROLE_ADMIN, User::ROLE_STAFF],
        'author.delete' => [User::ROLE_ADMIN, User::ROLE_STAFF],
    ],

    'routes' => [
        '/user/view*' => 'user.view_list',
        '/user/tableHandler*' => 'user.view_list',
        '/user/create*' => 'user.create',
        '/user/update*' => 'user.edit',
        '/user/delete*' => 'user.delete',

        '/page/view*' => 'page.view_list',
        '/page/tableHandler*' => 'page.view_list',
        '/page/create*' => 'page.create',
        '/page/update*' => 'page.edit',
        '/page/delete*' => 'page.delete',

        '/activity/view*' => 'activity.view_list',
        '/activity/tableHandler*' => 'activity.view_list',


        '/image/*' => 'image.view_list',
        '/file/*' => 'file.manage',
        '/theme/*' => 'theme.manage',
        '/navigation/*' => 'theme.manage',
        '/social/*' => 'theme.manage',
        '/translator/switchLanguage' => null,  // Any logged-in user can switch language
        '/translator/*' => 'translator.manage',

        '/post/view*' => 'post.view_list',
        '/post/tableHandler*' => 'post.view_list',
        '/post/create*' => 'post.create',
        '/post/update*' => 'post.edit',
        '/post/delete*' => 'post.delete',

        '/category/view*' => 'category.view_list',
        '/category/tableHandler*' => 'category.view_list',
        '/category/create*' => 'category.create',
        '/category/update*' => 'category.edit',
        '/category/delete*' => 'category.delete',

        '/tag/view*' => 'tag.view_list',
        '/tag/tableHandler*' => 'tag.view_list',
        '/tag/create*' => 'tag.create',
        '/tag/update*' => 'tag.edit',
        '/tag/delete*' => 'tag.delete',
        '/tag/createOrGet*' => 'tag.create_or_get',

        '/reference/view*' => 'reference.view_list',
        '/reference/tableHandler*' => 'reference.view_list',
        '/reference/create*' => 'reference.create',
        '/reference/update*' => 'reference.edit',
        '/reference/delete*' => 'reference.delete',
        '/lead/view*' => 'lead.view_list',
        '/lead/tableHandler*' => 'lead.view_list',
        '/lead/create*' => 'lead.create',
        '/lead/update*' => 'lead.edit',
        '/lead/delete*' => 'lead.delete',

        '/author/view*' => 'author.view_list',
        '/author/tableHandler*' => 'author.view_list',
        '/author/create*' => 'author.create',
        '/author/update*' => 'author.edit',
        '/author/delete*' => 'author.delete',

        // Any other authenticated path — allow any logged-in user
        '/*' => null,
    ],

    'roles' => [
        User::ROLE_ADMIN => [User::ROLE_STAFF],  // Admin inherits staff permissions
        User::ROLE_STAFF => [],
    ],
];
