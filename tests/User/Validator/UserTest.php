<?php

declare(strict_types=1);

namespace Skeletor\Tests\User\Validator;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Tests\Support\RecordingSessionManager;
use Skeletor\User\Entity\User as UserEntity;
use Skeletor\User\Validator\User;

/**
 * The rules that still run when an administrator creates or edits a user.
 *
 * Fewer than it looks: the email format and uniqueness checks are commented out, and the
 * commented uniqueness check calls $this->userRepo, which this class does not have -- so it
 * could not be uncommented as it stands. What remains is the password pair, a password length
 * that only applies on create, a display-name minimum, and a role that must not be guest.
 *
 * The disabled uniqueness check is worth knowing about rather than assuming away: User::$email
 * is a UNIQUE column, so a duplicate address is not rejected by the form -- it reaches MySQL,
 * and AjaxCrudController turns that into a save that spins forever.
 */
#[CoversClass(User::class)]
final class UserTest extends TestCase
{
    private RecordingSessionManager $session;

    protected function setUp(): void
    {
        $this->session = new RecordingSessionManager();
    }

    private function validator(): User
    {
        // The EntityManager is a constructor dependency the live rules never touch.
        return new User($this->createStub(EntityManagerInterface::class), new Csrf($this->session));
    }

    /** Shaped the way User\Filter hands it over: id is null on create, password may be null. */
    private function form(array $overrides = []): array
    {
        return $overrides + [
            'id' => null,
            'email' => 'someone@example.com',
            'password' => 'longenoughpassword',
            'password2' => 'longenoughpassword',
            'role' => UserEntity::ROLE_ADMIN,
            'isActive' => 1,
            'displayName' => 'Someone',
            'firstName' => 'Some',
            'lastName' => 'One',
        ] + (new Csrf($this->session))->getTokenAsArray();
    }

    public function testACompleteNewUserIsAccepted(): void
    {
        $validator = $this->validator();

        self::assertTrue($validator->isValid($this->form()));
        self::assertSame([], $validator->getMessages());
    }

    // ---- passwords ------------------------------------------------------------------------

    public function testTheTwoPasswordFieldsMustMatch(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['password2' => 'somethingelse'])));
        self::assertArrayHasKey('password', $validator->getMessages());
    }

    public function testANewUserNeedsAPasswordOfAtLeastEightCharacters(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['password' => 'short12', 'password2' => 'short12'])));
        self::assertArrayHasKey('password', $validator->getMessages());
    }

    public function testExactlyEightCharactersIsAccepted(): void
    {
        self::assertTrue($this->validator()->isValid($this->form([
            'password' => 'eightchr',
            'password2' => 'eightchr',
        ])));
    }

    public function testAnExistingUserMayBeSavedWithoutTouchingThePassword(): void
    {
        // The length rule is skipped once there is an id, which is what lets an administrator
        // edit somebody's name without knowing or resetting their password. The filter only
        // hashes when both fields are non-empty, so a blank pair leaves the stored hash alone.
        self::assertTrue($this->validator()->isValid($this->form([
            'id' => '7',
            'password' => '',
            'password2' => '',
        ])));
    }

    public function testAnExistingUserStillCannotMistypeAPasswordChange(): void
    {
        // The match rule has no such exemption -- it applies whether creating or editing.
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form([
            'id' => '7',
            'password' => 'newpassword',
            'password2' => 'newpassxxxx',
        ])));
        self::assertArrayHasKey('password', $validator->getMessages());
    }

    // ---- display name and role ---------------------------------------------------------------

    public function testADisplayNameOfOneCharacterIsRejected(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['displayName' => 'A'])));
        self::assertArrayHasKey('displayName', $validator->getMessages());
    }

    public function testTwoCharactersIsEnoughForADisplayName(): void
    {
        self::assertTrue($this->validator()->isValid($this->form(['displayName' => 'Jo'])));
    }

    public function testTheGuestRoleCannotBeAssigned(): void
    {
        // ROLE_GUEST is 0, and the check is `(int) $role === 0` -- so it doubles as a guard
        // against a role field that was never filled in, which also arrives as 0.
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['role' => UserEntity::ROLE_GUEST])));
        self::assertArrayHasKey('role', $validator->getMessages());
    }

    public function testTheRealRolesAreAccepted(): void
    {
        foreach ([UserEntity::ROLE_ADMIN, UserEntity::ROLE_STAFF] as $role) {
            self::assertTrue($this->validator()->isValid($this->form(['role' => $role])), 'role ' . $role);
        }
    }

    // ---- what this validator does NOT do -------------------------------------------------------

    public function testEmailFormatIsNotCheckedHere(): void
    {
        // Deliberately disabled in the source, with a note about wanting per-app rules. Pinned
        // so that turning it back on is a decision with a failing test attached, rather than a
        // surprise for whichever app relied on it being off.
        self::assertTrue($this->validator()->isValid($this->form(['email' => 'not-an-address'])));
    }

    public function testABadFormTokenThrowsBeforeAnyRuleRuns(): void
    {
        $form = $this->form(['displayName' => 'A', 'role' => UserEntity::ROLE_GUEST]);
        $form[Csrf::TOKEN_NAME] = 'not the token';

        $this->expectException(InvalidFormTokenException::class);
        $this->validator()->isValid($form);
    }
}
