<?php

declare(strict_types=1);

namespace Skeletor\Tests\Reference\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Reference\Validator\Reference;
use Skeletor\Tests\Support\RecordingSessionManager;

/**
 * The smallest validator in the framework: a form token, a title and some content.
 *
 * Short, but it carries the convention the larger ones follow -- a bad form token throws
 * rather than returning false, so the controller answers "refresh and try again" instead of
 * listing field errors for a submission it never really read.
 */
#[CoversClass(Reference::class)]
final class ReferenceTest extends TestCase
{
    private RecordingSessionManager $session;

    protected function setUp(): void
    {
        $this->session = new RecordingSessionManager();
    }

    private function validator(): Reference
    {
        return new Reference(new Csrf($this->session));
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'A reference',
            'content' => 'Something worth quoting.',
        ] + (new Csrf($this->session))->getTokenAsArray();
    }

    public function testACompleteSubmissionIsAccepted(): void
    {
        $validator = $this->validator();

        self::assertTrue($validator->isValid($this->form()));
        self::assertSame([], $validator->getMessages());
    }

    public function testTitleIsRequired(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['title' => ''])));
        self::assertArrayHasKey('title', $validator->getMessages());
    }

    public function testContentIsRequired(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['content' => ''])));
        self::assertArrayHasKey('content', $validator->getMessages());
    }

    public function testWhitespaceAloneDoesNotCountAsContent(): void
    {
        // Both checks trim, so a field of spaces is empty. Worth pinning: a browser that
        // helpfully inserts a newline would otherwise satisfy a required field.
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['title' => "  \n ", 'content' => "\t"])));
        self::assertArrayHasKey('title', $validator->getMessages());
        self::assertArrayHasKey('content', $validator->getMessages());
    }

    public function testABadFormTokenThrowsBeforeAnyFieldIsLookedAt(): void
    {
        // Throws rather than returning false, and does it first -- so a stale form reports a
        // stale form, not a list of field errors the user cannot act on.
        $form = $this->form(['title' => '', 'content' => '']);
        $form[Csrf::TOKEN_NAME] = 'not the token';

        $this->expectException(InvalidFormTokenException::class);
        $this->validator()->isValid($form);
    }

    public function testUrlIsNotRequired(): void
    {
        // The check exists but is commented out in the validator. Pinned so that turning it
        // back on is a deliberate act with a failing test attached, rather than a surprise.
        self::assertTrue($this->validator()->isValid($this->form(['url' => ''])));
    }
}
