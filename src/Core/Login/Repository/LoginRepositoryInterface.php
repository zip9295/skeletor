<?php
namespace Skeletor\Core\Login\Repository;

use Skeletor\Core\Model\Model;

interface LoginRepositoryInterface
{
    /**
     * The account for this address.
     *
     * Implementations differ on the miss: some throw NotFoundException, some return null.
     * Both are tolerated — go through EntityRegistry::findByEmail() rather than calling this
     * directly from framework code, and the difference stops mattering.
     */
    public function findByEmail(string $email);

    public function updatePassword($userId, $password);

    public function updateLoginInfo($model);

    /**
     * Declared because AuthMiddleware and MagicLinkAuthenticator both rely on it to rehydrate
     * an entity from a session or a token. Every implementation inherits it from
     * CrudRepository; naming it here stops that from being an accident.
     */
    public function getById($id);
}