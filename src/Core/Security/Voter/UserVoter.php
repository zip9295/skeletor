<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Voter;

use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\User\Entity\User;

/**
 * Voter for User permissions
 */
class UserVoter extends AbstractResourceVoter
{
    const VIEW = 'user.view';
    const VIEW_LIST = 'user.view_list';
    const CREATE = 'user.create';
    const EDIT = 'user.edit';
    const EDIT_SELF = 'user.edit_self';
    const DELETE = 'user.delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'user.');
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        AuthenticatableInterface $user
    ): bool {
        return match($attribute) {
            self::VIEW_LIST => $this->canViewList($user),
            self::VIEW => $this->canView($user, $subject),
            self::CREATE => $this->canCreate($user),
            self::EDIT => $this->canEdit($user, $subject),
            self::EDIT_SELF => $this->canEditSelf($user, $subject),
            self::DELETE => $this->canDelete($user, $subject),
            default => false,
        };
    }

    private function canViewList(AuthenticatableInterface $user): bool
    {
        // Admin and staff can view user list
        return $this->hasAnyRole($user, [User::ROLE_ADMIN, User::ROLE_DELEGATE]);
    }

    private function canView(AuthenticatableInterface $user, mixed $subject): bool
    {
        // Admin can view all users
        if ($this->hasRole($user, User::ROLE_ADMIN)) {
            return true;
        }

        // Staff can view users
        if ($this->hasRole($user, User::ROLE_DELEGATE)) {
            return true;
        }

        // Users can view themselves
        return $this->isOwner($user, $subject);
    }

    private function canCreate(AuthenticatableInterface $user): bool
    {
        // Only admin can create users
        return $this->hasRole($user, User::ROLE_ADMIN);
    }

    private function canEdit(AuthenticatableInterface $user, mixed $subject): bool
    {
        // Only admin can edit any user
        return $this->hasRole($user, User::ROLE_ADMIN);
    }

    private function canEditSelf(AuthenticatableInterface $user, mixed $subject): bool
    {
        // Users can edit themselves
        return $this->isOwner($user, $subject);
    }

    private function canDelete(AuthenticatableInterface $user, mixed $subject): bool
    {
        // Only admin can delete users
        // Cannot delete yourself
        return $this->hasRole($user, User::ROLE_ADMIN) && !$this->isOwner($user, $subject);
    }
}
