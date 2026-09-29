<?php

declare(strict_types=1);

namespace Skeletor\Tests\Integration\Blog;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Skeletor\Blog\Repository\TagRepository;
use Skeletor\Blog\Validator\Tag;
use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Tests\Integration\IntegrationTestCase;
use Skeletor\Tests\Support\RecordingSessionManager;

/**
 * The tag form: the category form minus the parent, the level, and the title length rule.
 *
 * The missing length rule is deliberate on the framework's side as far as anything says, so it
 * is pinned rather than added -- a one character tag is accepted here and rejected as a
 * category, and a test is the only place that difference is written down.
 *
 * The edit guard was repaired while this was written: it read trim($data['id'] !== ''), which
 * trims a boolean. See Blog\Validator\Category for the same defect and the same repair.
 */
#[CoversClass(Tag::class)]
final class TagValidatorTest extends IntegrationTestCase
{
    private RecordingSessionManager $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new RecordingSessionManager();
    }

    private function validator(): Tag
    {
        return new Tag(new Csrf($this->session), new TagRepository($this->em()));
    }

    /** Shaped the way Blog\Filter\Tag hands it over: id is int|null. */
    private function form(array $overrides = []): array
    {
        return $overrides + [
            'id' => null,
            'title' => 'A perfectly good tag',
            'slug' => 'a-perfectly-good-tag',
            'seoTitle' => 'A title',
            'seoDescription' => 'A description',
            'seoImageId' => 8,
        ] + (new Csrf($this->session))->getTokenAsArray();
    }

    public function testACompleteTagIsAccepted(): void
    {
        $validator = $this->validator();

        self::assertTrue($validator->isValid($this->form()));
        self::assertSame([], $validator->getMessages());
    }

    public function testABadFormTokenThrowsBeforeAnythingIsQueried(): void
    {
        $form = $this->form();
        $form[Csrf::TOKEN_NAME] = 'not the token';

        $this->expectException(InvalidFormTokenException::class);
        $this->validator()->isValid($form);
    }

    public function testATitleIsRequired(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['title' => '   '])));
        self::assertSame(['Title is required.'], $validator->getMessages()['title']);
    }

    public function testAOneCharacterTitleIsAcceptedUnlikeACategory(): void
    {
        self::assertTrue($this->validator()->isValid($this->form(['title' => 'x'])));
    }
    // ---- the slug rule -----------------------------------------------------------------------

    public function testASlugIsRequired(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['slug' => ''])));
        self::assertArrayHasKey('slug', $validator->getMessages());
    }

    public function testANewTagCannotTakeASlugThatIsAlreadyUsed(): void
    {
        $this->createTag(slug: 'taken-slug');
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['slug' => 'taken-slug'])));
        self::assertSame(['Slug already exists.'], $validator->getMessages()['slug']);
    }

    public function testEditingATagMayKeepItsOwnSlug(): void
    {
        $tag = $this->createTag(slug: 'its-own-slug');

        self::assertTrue($this->validator()->isValid(
            $this->form(['id' => $tag->id, 'slug' => 'its-own-slug']),
        ));
    }

    public function testEditingATagCannotStealAnotherTagsSlug(): void
    {
        $mine = $this->createTag(slug: 'mine');
        $this->createTag(slug: 'yours');
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['id' => $mine->id, 'slug' => 'yours'])));
        self::assertSame(['Slug already exists.'], $validator->getMessages()['slug']);
    }

    // ---- the SEO fields ----------------------------------------------------------------------

    /** @return array<string, array{string}> */
    public static function mandatoryFields(): array
    {
        return [
            'seo title' => ['seoTitle'],
            'seo description' => ['seoDescription'],
            'seo image' => ['seoImageId'],
        ];
    }

    #[DataProvider('mandatoryFields')]
    public function testAMandatoryFieldMissingIsReported(string $field): void
    {
        $form = $this->form();
        unset($form[$field]);
        $validator = $this->validator();

        self::assertFalse($validator->isValid($form));
        self::assertArrayHasKey($field, $validator->getMessages());
    }

    public function testEveryProblemIsReportedInOnePass(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form([
            'title' => '',
            'slug' => '',
            'seoTitle' => null,
            'seoDescription' => null,
        ])));
        self::assertSame(
            ['title', 'slug', 'seoTitle', 'seoDescription'],
            array_keys($validator->getMessages()),
        );
    }
}
