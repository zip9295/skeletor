<?php

declare(strict_types=1);

namespace Skeletor\Tests\Integration\Blog;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Skeletor\Blog\Entity\Post as PostEntity;
use Skeletor\Blog\Repository\PostRepository;
use Skeletor\Blog\Validator\Post;
use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Tests\Integration\IntegrationTestCase;
use Skeletor\Tests\Support\RecordingSessionManager;

/**
 * The post form: more required fields than anything else in the framework, plus a unique slug.
 *
 * Four rules here were repaired while this was written, so these tests double as the record of
 * what they now mean -- the title length was measured before trimming, the status was compared
 * against a sentinel that does not exist, a missing featured image returned early and hid every
 * later error, and the edit guard trimmed a boolean.
 */
#[CoversClass(Post::class)]
final class PostValidatorTest extends IntegrationTestCase
{
    private RecordingSessionManager $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new RecordingSessionManager();
    }

    private function validator(): Post
    {
        return new Post(
            new Csrf($this->session),
            new PostRepository($this->em(), $this->objectCache(), $this->serializer()),
        );
    }

    /** Shaped the way Blog\Filter\Post hands it over: id and status are already ints. */
    private function form(array $overrides = []): array
    {
        return $overrides + [
            'id' => null,
            'title' => 'A perfectly good title',
            'slug' => 'a-perfectly-good-title',
            'status' => PostEntity::STATUS_PUBLISHED,
            'mainCategory' => 1,
            'seoTitle' => 'A title',
            'seoDescription' => 'A description',
            'featuredImageId' => 7,
            'seoImageId' => 8,
            'author' => 3,
        ] + (new Csrf($this->session))->getTokenAsArray();
    }

    public function testACompletePostIsAccepted(): void
    {
        $validator = $this->validator();

        self::assertTrue($validator->isValid($this->form()));
        self::assertSame([], $validator->getMessages());
    }

    public function testATitleIsRequired(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['title' => ''])));
        self::assertArrayHasKey('title', $validator->getMessages());
    }

    public function testATitleShorterThanFiveCharactersIsRejected(): void
    {
        self::assertFalse($this->validator()->isValid($this->form(['title' => 'Four'])));
    }

    public function testExactlyFiveCharactersIsAccepted(): void
    {
        self::assertTrue($this->validator()->isValid($this->form(['title' => 'Fives'])));
    }

    public function testATitleOfSpacesIsRequiredRatherThanLongEnough(): void
    {
        // The repaired rule. Length used to be measured on the untrimmed value, so eight
        // spaces satisfied "at least five characters" while also failing "required".
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['title' => '        '])));
        self::assertSame(['Title is required.'], $validator->getMessages()['title']);
    }

    public function testABadFormTokenThrowsBeforeAnythingIsQueried(): void
    {
        $form = $this->form();
        $form[Csrf::TOKEN_NAME] = 'not the token';

        $this->expectException(InvalidFormTokenException::class);
        $this->validator()->isValid($form);
    }

    // ---- status ----------------------------------------------------------------------------

    /** @return array<string, array{int}> */
    public static function realStatuses(): array
    {
        return [
            'published' => [PostEntity::STATUS_PUBLISHED],
            'draft' => [PostEntity::STATUS_DRAFT],
            'pending' => [PostEntity::STATUS_PENDING],
            'scheduled' => [PostEntity::STATUS_SCHEDULED],
        ];
    }

    #[DataProvider('realStatuses')]
    public function testEveryRealStatusIsAccepted(int $status): void
    {
        self::assertTrue($this->validator()->isValid($this->form(['status' => $status])));
    }

    public function testAnUnselectedStatusIsRejected(): void
    {
        // The filter casts with (int), so "nothing chosen" arrives as 0 -- not a Post status,
        // and something getHrStatus() would later fail on. The old check compared against the
        // string '-1' and could never fire, so 0 sailed through.
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['status' => 0])));
        self::assertArrayHasKey('status', $validator->getMessages());
    }

    // ---- the slug rule -----------------------------------------------------------------------

    public function testANewPostCannotTakeASlugThatIsAlreadyUsed(): void
    {
        $this->createPost(slug: 'taken-slug');
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['slug' => 'taken-slug'])));
        self::assertArrayHasKey('slug', $validator->getMessages());
    }

    public function testEditingAPostMayKeepItsOwnSlug(): void
    {
        $post = $this->createPost(slug: 'taken-slug');

        self::assertTrue($this->validator()->isValid($this->form([
            'id' => $post->id,
            'slug' => 'taken-slug',
        ])));
    }

    public function testEditingAPostCannotStealAnotherPostsSlug(): void
    {
        $this->createPost(slug: 'theirs');
        $mine = $this->createPost(slug: 'mine');
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['id' => $mine->id, 'slug' => 'theirs'])));
        self::assertArrayHasKey('slug', $validator->getMessages());
    }

    public function testASlugIsRequired(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['slug' => ''])));
        self::assertArrayHasKey('slug', $validator->getMessages());
    }

    // ---- everything else that is mandatory ----------------------------------------------------

    /** @return array<string, array{string}> */
    public static function requiredFields(): array
    {
        return [
            'main category' => ['mainCategory'],
            'seo title' => ['seoTitle'],
            'seo description' => ['seoDescription'],
            'featured image' => ['featuredImageId'],
            'seo image' => ['seoImageId'],
            'author' => ['author'],
        ];
    }

    #[DataProvider('requiredFields')]
    public function testAMandatoryFieldMissingIsReported(string $field): void
    {
        $form = $this->form();
        unset($form[$field]);
        $validator = $this->validator();

        self::assertFalse($validator->isValid($form));
        self::assertArrayHasKey($field, $validator->getMessages());
    }

    public function testAnAuthorIsRequiredEvenAsAnEmptyList(): void
    {
        // The filter passes `$authors[0] ?? null`, so an empty picker arrives as null -- but an
        // array is possible too. Both mean nobody was chosen.
        self::assertFalse($this->validator()->isValid($this->form(['author' => null])));
        self::assertFalse($this->validator()->isValid($this->form(['author' => []])));
    }

    public function testEveryProblemIsReportedInOnePass(): void
    {
        // The repaired short-circuit. A missing featured image used to `return false` on the
        // spot, so the SEO image and author errors below it were never collected -- fix one,
        // resubmit, discover the next.
        $form = $this->form();
        unset($form['featuredImageId'], $form['seoImageId'], $form['author']);
        $validator = $this->validator();

        $validator->isValid($form);

        self::assertArrayHasKey('featuredImageId', $validator->getMessages());
        self::assertArrayHasKey('seoImageId', $validator->getMessages());
        self::assertArrayHasKey('author', $validator->getMessages());
    }
}
