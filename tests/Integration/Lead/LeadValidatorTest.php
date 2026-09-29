<?php

declare(strict_types=1);

namespace Skeletor\Tests\Integration\Lead;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Lead\Repository\LeadRepository;
use Skeletor\Lead\Validator\Lead;
use Skeletor\Tests\Integration\IntegrationTestCase;
use Skeletor\Tests\Support\RecordingSessionManager;

/**
 * Lead uniqueness, which is the most tangled rule in the framework.
 *
 * Email and phone are both unique, and both behave differently depending on whether this is a
 * create or an edit. The validator expresses that by looking up who currently owns the
 * submitted value and comparing it with the record being edited -- four combinations per
 * field, and the two failure directions cost very different things. Too strict and nobody can
 * save an edit without also changing their email; too lax and two leads collide on a UNIQUE
 * column, which surfaces as an AjaxCrudController spinner that never resolves rather than as
 * a form error.
 *
 * All four combinations are pinned below, for both fields.
 */
#[CoversClass(Lead::class)]
final class LeadValidatorTest extends IntegrationTestCase
{
    private RecordingSessionManager $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new RecordingSessionManager();
    }

    private function validator(): Lead
    {
        return new Lead(new LeadRepository($this->em()), new Csrf($this->session));
    }

    /** Shaped the way Lead\Filter hands it over: absent optional fields arrive as null. */
    private function form(array $overrides = []): array
    {
        return $overrides + [
            'id' => null,
            'email' => 'new@example.com',
            'firstName' => 'First',
            'lastName' => 'Last',
            'phoneNumber' => '0601234567',
            'status' => null,
        ] + (new Csrf($this->session))->getTokenAsArray();
    }

    public function testANewLeadWithUnusedDetailsIsAccepted(): void
    {
        $validator = $this->validator();

        self::assertTrue($validator->isValid($this->form()));
        self::assertSame([], $validator->getMessages());
    }

    /** @return array<string, array{string}> */
    public static function malformedAddresses(): array
    {
        return [
            'no at sign' => ['not-an-address'],
            'no domain' => ['someone@'],
            'empty' => [''],
            'spaces' => ['a b@example.com'],
        ];
    }

    #[DataProvider('malformedAddresses')]
    public function testAMalformedAddressIsRejected(string $email): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['email' => $email])));
        self::assertArrayHasKey('email', $validator->getMessages());
    }

    public function testABadFormTokenThrowsBeforeAnythingIsQueried(): void
    {
        $form = $this->form();
        $form[Csrf::TOKEN_NAME] = 'not the token';

        $this->expectException(InvalidFormTokenException::class);
        $this->validator()->isValid($form);
    }

    // ---- email, four combinations --------------------------------------------------------

    public function testANewLeadCannotTakeAnAddressAlreadyInTheSystem(): void
    {
        $this->createLead(email: 'taken@example.com');
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['email' => 'taken@example.com'])));
        self::assertArrayHasKey('email', $validator->getMessages());
    }

    public function testEditingALeadMayKeepItsOwnAddress(): void
    {
        // The strict-direction failure: if this breaks, no lead can ever be saved again
        // without also being given a new email.
        $lead = $this->createLead(email: 'mine@example.com', phone: '060111');

        $valid = $this->validator()->isValid($this->form([
            'id' => (string) $lead->id,
            'email' => 'mine@example.com',
            'phoneNumber' => '060111',
        ]));

        self::assertTrue($valid, 'editing a lead must not collide with itself');
    }

    public function testEditingALeadCannotTakeSomebodyElsesAddress(): void
    {
        $this->createLead(email: 'theirs@example.com', phone: '060222');
        $mine = $this->createLead(email: 'mine@example.com', phone: '060111');
        $validator = $this->validator();

        $valid = $validator->isValid($this->form([
            'id' => (string) $mine->id,
            'email' => 'theirs@example.com',
            'phoneNumber' => '060111',
        ]));

        self::assertFalse($valid);
        self::assertArrayHasKey('email', $validator->getMessages());
    }

    public function testEditingALeadOntoAnUnusedAddressIsAccepted(): void
    {
        $lead = $this->createLead(email: 'mine@example.com', phone: '060111');

        self::assertTrue($this->validator()->isValid($this->form([
            'id' => (string) $lead->id,
            'email' => 'brand-new@example.com',
            'phoneNumber' => '060111',
        ])));
    }

    // ---- phone, the same four ------------------------------------------------------------

    public function testANewLeadCannotTakeAPhoneNumberAlreadyInTheSystem(): void
    {
        $this->createLead(email: 'other@example.com', phone: '060999');
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['phoneNumber' => '060999'])));
        self::assertArrayHasKey('phoneNumber', $validator->getMessages());
    }

    public function testEditingALeadMayKeepItsOwnPhoneNumber(): void
    {
        $lead = $this->createLead(email: 'mine@example.com', phone: '060111');

        self::assertTrue($this->validator()->isValid($this->form([
            'id' => (string) $lead->id,
            'email' => 'mine@example.com',
            'phoneNumber' => '060111',
        ])));
    }

    public function testEditingALeadCannotTakeSomebodyElsesPhoneNumber(): void
    {
        $this->createLead(email: 'theirs@example.com', phone: '060222');
        $mine = $this->createLead(email: 'mine@example.com', phone: '060111');
        $validator = $this->validator();

        $valid = $validator->isValid($this->form([
            'id' => (string) $mine->id,
            'email' => 'mine@example.com',
            'phoneNumber' => '060222',
        ]));

        self::assertFalse($valid);
        self::assertArrayHasKey('phoneNumber', $validator->getMessages());
    }

    public function testAPhoneNumberIsOptional(): void
    {
        // Nullable column, and the filter turns a blank field into null. A uniqueness check
        // that treated null as a value would make the second lead without a phone collide
        // with the first.
        $this->createLead(email: 'other@example.com', phone: null);

        self::assertTrue($this->validator()->isValid($this->form(['phoneNumber' => null])));
    }

    // ---- names ---------------------------------------------------------------------------

    /** @return array<string, array{string}> */
    public static function nameFields(): array
    {
        return ['first name' => ['firstName'], 'last name' => ['lastName']];
    }

    #[DataProvider('nameFields')]
    public function testANameShorterThanThreeCharactersIsRejected(string $field): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form([$field => 'Jo'])));
        self::assertArrayHasKey($field, $validator->getMessages());
    }

    #[DataProvider('nameFields')]
    public function testExactlyThreeCharactersIsAccepted(string $field): void
    {
        self::assertTrue($this->validator()->isValid($this->form([$field => 'Ann'])));
    }

    #[DataProvider('nameFields')]
    public function testANameIsOptionalAltogether(string $field): void
    {
        // The filter nulls a blank field, and the length rule only applies once something is
        // there -- otherwise "optional" and "at least three characters" would contradict.
        self::assertTrue($this->validator()->isValid($this->form([$field => null])));
    }
}
