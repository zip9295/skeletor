<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Login\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Login\Service\Login;
use Skeletor\Tests\Support\InMemoryAccountRepository;
use Skeletor\Tests\Support\InMemoryForgotPasswordRepository;
use Skeletor\Tests\Support\RecordingMailer;
use Skeletor\Tests\Support\RecordingSessionManager;
use Skeletor\Tests\Support\TestAccount;

/**
 * What a session is, and what a password-reset link is worth.
 *
 * The session keys asserted here are the ones AuthMiddleware, SessionContext and every
 * template read; they are effectively the framework's login contract, and the only place
 * they are written is the method under test.
 */
#[CoversClass(Login::class)]
final class LoginTest extends TestCase
{
    private RecordingSessionManager $session;

    private InMemoryForgotPasswordRepository $tokens;

    private RecordingMailer $mailer;

    private EntityRegistry $registry;

    private TestAccount $account;

    protected function setUp(): void
    {
        $this->session = new RecordingSessionManager();
        $this->tokens = new InMemoryForgotPasswordRepository();
        $this->mailer = new RecordingMailer();
        $this->account = new TestAccount(id: 5, email: 'someone@example.com');
        $this->registry = new EntityRegistry();
        $this->registry->register('user', TestAccount::class, new InMemoryAccountRepository(true, $this->account));
    }

    private function service(): Login
    {
        return new Login(null, $this->session, $this->mailer, $this->tokens, null, $this->registry);
    }

    // ---- establishing a session ------------------------------------------------------

    public function testLoggingInWritesTheKeysEverythingElseReads(): void
    {
        $this->service()->login($this->account, 'user');

        $storage = $this->session->getStorage();
        self::assertSame(5, $storage->offsetGet('loggedIn'));
        self::assertSame('user', $storage->offsetGet('loggedInEntityType'));
        self::assertSame(1, $storage->offsetGet('loggedInRole'));
        self::assertSame('someone@example.com', $storage->offsetGet('loggedInEmail'));
        self::assertSame('/dashboard/', $storage->offsetGet('redirectPath'));
    }

    public function testTheSessionIdIsRegeneratedOnLogin(): void
    {
        // Without this, an id fixed by an attacker before the login still identifies the
        // session after it, and the login has handed them the account.
        $this->service()->login($this->account, 'user');

        self::assertTrue($this->session->idRegenerated);
    }

    public function testRememberMeIsOnlyHonouredWhenAskedFor(): void
    {
        $this->service()->login($this->account, 'user');
        self::assertFalse($this->session->remembered);

        $this->service()->login($this->account, 'user', true);
        self::assertTrue($this->session->remembered);
    }

    public function testTheEntityTypeIsRecordedSoTwoKindsOfAccountCannotBeConfused(): void
    {
        $this->service()->login(new TestAccount(id: 5), 'delegate');

        self::assertSame('delegate', $this->session->getStorage()->offsetGet('loggedInEntityType'));
    }

    public function testLoggingOutClearsEverySessionKeyAndTheSessionItself(): void
    {
        $service = $this->service();
        $service->login($this->account, 'user');

        $service->logout();

        $storage = $this->session->getStorage();
        foreach (['loggedIn', 'loggedInRole', 'loggedInEmail', 'loggedInEntityType', 'redirectPath'] as $key) {
            self::assertNull($storage->offsetGet($key), "{$key} should be gone");
        }
        self::assertTrue($this->session->forgotten, 'the remember-me cookie must go too');
        self::assertTrue($this->session->destroyed);
    }

    // ---- password reset links --------------------------------------------------------

    public function testAResetTokenIsMailedAndStoredAgainstTheRightEntityType(): void
    {
        $this->service()->sendForgotToken('someone@example.com', 'user');

        self::assertCount(1, $this->mailer->forgotPasswords);
        self::assertSame('user', $this->tokens->tokens[0]->entityType);
        self::assertSame('5', (string) $this->tokens->tokens[0]->entityId);
    }

    public function testTheStoredResetTokenIsHashed(): void
    {
        $this->service()->sendForgotToken('someone@example.com', 'user');

        $mailed = $this->mailer->forgotPasswords[0]['token'];
        self::assertNotSame($mailed, $this->tokens->tokens[0]->token);
        self::assertTrue(password_verify($mailed, $this->tokens->tokens[0]->token));
    }

    public function testAnUnknownAddressIsReportedRatherThanMailed(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service()->sendForgotToken('nobody@example.com', 'user');
    }

    public function testAValidResetLinkIdentifiesItsOwner(): void
    {
        $service = $this->service();
        $service->sendForgotToken('someone@example.com', 'user');
        $mailed = $this->mailer->forgotPasswords[0]['token'];

        $data = $service->verifyToken(sprintf('$%s$%s', 5, $mailed), 'user');

        self::assertSame('5', (string) $data['entityId']);
        self::assertSame('user', $data['entityType']);
        self::assertSame(1, $data['tokenId']);
    }

    public function testAResetLinkExpires(): void
    {
        // It never did. The cutoff was computed and then compared against nothing, so one old
        // message in a mailbox was a permanent way back into the account.
        $service = $this->service();
        $service->sendForgotToken('someone@example.com', 'user');
        $mailed = $this->mailer->forgotPasswords[0]['token'];
        $this->tokens->age('-4 hours');

        $this->expectException(\Exception::class);
        $service->verifyToken(sprintf('$%s$%s', 5, $mailed), 'user');
    }

    public function testASpentResetLinkIsRefused(): void
    {
        $service = $this->service();
        $service->sendForgotToken('someone@example.com', 'user');
        $mailed = $this->mailer->forgotPasswords[0]['token'];
        $this->tokens->resetToken(1);

        $this->expectException(\Exception::class);
        $service->verifyToken(sprintf('$%s$%s', 5, $mailed), 'user');
    }

    public function testAResetLinkIsNotValidForAnotherEntityTypeWithTheSameId(): void
    {
        // The reason entityType stopped being a hardcoded 1: a delegate and a user can both
        // be id 5, and the newest token wins a query that does not name the type.
        $service = $this->service();
        $service->sendForgotToken('someone@example.com', 'user');
        $mailed = $this->mailer->forgotPasswords[0]['token'];

        $this->expectException(\Exception::class);
        $service->verifyToken(sprintf('$%s$%s', 5, $mailed), 'delegate');
    }

    public function testAMalformedResetLinkIsRefusedRatherThanFatal(): void
    {
        // These arrive from mail clients that have mangled the URL, not only from attackers.
        $service = $this->service();

        foreach (['', 'nonsense', '$5', '$$'] as $hash) {
            try {
                $service->verifyToken($hash, 'user');
                self::fail(sprintf('"%s" should not verify', $hash));
            } catch (\Exception) {
                self::assertTrue(true);
            }
        }
    }

    public function testPasswordOperationsFailLoudlyWhenNoProviderIsWired(): void
    {
        // Solidarity has no password column anywhere and wires no provider; the failure
        // should name that rather than surface as a null dereference three frames down.
        $this->expectException(\LogicException::class);

        $this->service()->resetPassword(5, 'irrelevant');
    }
}
