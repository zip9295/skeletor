<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Skeletor\Core\Login\Controller\LoginController;

/**
 * What an application's login controller actually looks like: the framework's, with the
 * messages redeclared in the language its users speak.
 *
 * This exists because nothing in the framework's own tests subclassed LoginController, and
 * two bugs lived in that gap. static::FAILURES could not resolve a private constant under a
 * subclass's name, so every failure path was a fatal; and once that was fixed, the table
 * itself still held the framework's English, because a constant array can only reach the
 * message constants through self::. Both are invisible from a test that instantiates the
 * framework class directly -- there, static:: and self:: are the same class.
 *
 * Redeclaring exactly two constants is the point: the rest must still come through in
 * English, or a subclass would be all-or-nothing.
 */
final class TranslatedLoginController extends LoginController
{
    public const TRANSLATED_TOKEN = 'Forma je istekla.';
    public const TRANSLATED_GENERIC = 'Došlo je do greške.';

    const LOGIN_ERROR_TOKEN = self::TRANSLATED_TOKEN;
    const GENERIC_ERROR = self::TRANSLATED_GENERIC;
}
