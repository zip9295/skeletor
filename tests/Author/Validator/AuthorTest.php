<?php

declare(strict_types=1);

namespace Skeletor\Tests\Author\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Skeletor\Author\Validator\Author;
use Skeletor\Core\Security\Csrf;
use Skeletor\Tests\Support\RecordingSessionManager;

/**
 * The rules behind the author form.
 *
 * Worth pinning because half of them exist to protect NOT NULL columns rather than to express
 * a business rule: seoTitle and seoDescription come from the Seo trait, and a form that omits
 * them reaches the database and fails there instead of on the form. A length check that
 * silently stops matching is the same class of bug -- the row is rejected by MySQL, and
 * AjaxCrudController turns that into a spinner that never resolves.
 */
#[CoversClass(Author::class)]
final class AuthorTest extends TestCase
{
    private RecordingSessionManager $session;

    protected function setUp(): void
    {
        $this->session = new RecordingSessionManager();
    }

    private function validator(): Author
    {
        return new Author(new Csrf($this->session));
    }

    /** A complete, valid submission; individual tests break one field at a time. */
    private function form(array $overrides = []): array
    {
        return $overrides + [
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'displayName' => 'Ada L.',
            'seoTitle' => 'Ada Lovelace',
            'seoDescription' => 'The first programmer.',
        ] + (new Csrf($this->session))->getTokenAsArray();
    }

    public function testACompleteSubmissionIsAccepted(): void
    {
        $validator = $this->validator();

        self::assertTrue($validator->isValid($this->form()));
        self::assertSame([], $validator->getMessages());
    }

    /** @return array<string, array{string}> */
    public static function requiredFields(): array
    {
        return [
            'first name' => ['firstName'],
            'last name' => ['lastName'],
            'seo title' => ['seoTitle'],
            'seo description' => ['seoDescription'],
        ];
    }

    #[DataProvider('requiredFields')]
    public function testARequiredFieldLeftEmptyIsReported(string $field): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form([$field => ''])));
        self::assertArrayHasKey($field, $validator->getMessages());
    }

    #[DataProvider('requiredFields')]
    public function testARequiredFieldMissingEntirelyIsReportedRatherThanWarned(string $field): void
    {
        // A form that omits the key altogether -- an older template, a curl -- must fail
        // validation rather than raise an undefined-index warning on the way to failing.
        $form = $this->form();
        unset($form[$field]);
        $validator = $this->validator();

        self::assertFalse($validator->isValid($form));
        self::assertArrayHasKey($field, $validator->getMessages());
    }

    /** @return array<string, array{string, int}> */
    public static function lengthLimits(): array
    {
        return [
            'firstName' => ['firstName', 128],
            'lastName' => ['lastName', 128],
            'displayName' => ['displayName', 128],
            'seoTitle' => ['seoTitle', 128],
            'seoDescription' => ['seoDescription', 255],
        ];
    }

    #[DataProvider('lengthLimits')]
    public function testAFieldIsAcceptedExactlyAtItsColumnLength(string $field, int $limit): void
    {
        self::assertTrue($this->validator()->isValid($this->form([$field => str_repeat('a', $limit)])));
    }

    #[DataProvider('lengthLimits')]
    public function testAFieldOneCharacterOverItsColumnLengthIsRejected(string $field, int $limit): void
    {
        // The boundary is the point: these mirror VARCHAR widths, and one character over is
        // what MySQL rejects. Off by one here means the form accepts a row the database will not.
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form([$field => str_repeat('a', $limit + 1)])));
        self::assertArrayHasKey($field, $validator->getMessages());
    }

    public function testDisplayNameIsOptional(): void
    {
        // Nullable column, unlike the other two names.
        self::assertTrue($this->validator()->isValid($this->form(['displayName' => ''])));
    }

    public function testAnInvalidFormTokenIsReportedRatherThanThrown(): void
    {
        // Deliberately different from Page, Lead and Reference, which throw
        // InvalidFormTokenException for the same condition. Callers of this one get a false
        // and a message under 'general'; anyone reading the two side by side should know.
        $form = $this->form();
        $form[Csrf::TOKEN_NAME] = 'not the token';
        $validator = $this->validator();

        self::assertFalse($validator->isValid($form));
        self::assertArrayHasKey('general', $validator->getMessages());
    }

    public function testEveryProblemIsReportedAtOnce(): void
    {
        // The form shows all of them in one pass; reporting only the first would make fixing a
        // bad submission a game of whack-a-mole.
        $validator = $this->validator();

        $validator->isValid($this->form(['firstName' => '', 'lastName' => '', 'seoTitle' => '']));

        self::assertSame(['firstName', 'lastName', 'seoTitle'], array_keys($validator->getMessages()));
    }
}
