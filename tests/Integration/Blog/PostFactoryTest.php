<?php

declare(strict_types=1);

namespace Skeletor\Tests\Integration\Blog;

use PHPUnit\Framework\Attributes\CoversClass;
use Skeletor\Blog\Entity\Post;
use Skeletor\Blog\Entity\Tag;
use Skeletor\Blog\Factory\PostFactory;
use Skeletor\Tests\Integration\IntegrationTestCase;

/**
 * Turning a filtered form into a post and its relations.
 *
 * This is where fields go missing quietly. The factory assigns each relation only if its id
 * resolves, so anything that does not resolve is simply never set -- and for the two NOT NULL
 * relations, author and mainCategory, "never set" means a typed property that was never
 * initialised. What that costs depends entirely on whether the validator caught it first.
 */
#[CoversClass(PostFactory::class)]
final class PostFactoryTest extends IntegrationTestCase
{
    /** Shaped the way Blog\Filter\Post hands it over. */
    private function data(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'A perfectly good title',
            'slug' => 'a-good-title-' . (++$this->seq),
            'shortDescription' => 'Short.',
            'blockData' => [],
            'status' => Post::STATUS_PUBLISHED,
            'isLiveBlogPost' => false,
            'publishAt' => null,
            'author' => $this->createAuthor()->id,
            'mainCategory' => $this->createCategory()->id,
            'categories' => [],
            'tags' => [],
            'seoTitle' => 'SEO title',
            'seoDescription' => 'SEO description',
        ];
    }

    private function tag(string $title): Tag
    {
        $tag = new Tag();
        $tag->title = $title;
        $tag->slug = strtolower($title) . '-' . (++$this->seq);
        // Tag uses the Seo trait, whose two columns are NOT NULL -- an unset typed property
        // reaches the insert as null, and the failure names the column rather than the tag.
        $tag->seoTitle = $title;
        $tag->seoDescription = $title;
        $tag->seoImage = null;
        $this->em()->persist($tag);
        $this->em()->flush();

        return $tag;
    }

    public function testACompleteSubmissionBecomesAPost(): void
    {
        $data = $this->data();

        $post = $this->em()->find(Post::class, PostFactory::compileEntityForCreate($data, $this->em()));

        self::assertNotNull($post);
        self::assertSame($data['title'], $post->title);
        self::assertSame($data['slug'], $post->slug);
        self::assertSame(Post::STATUS_PUBLISHED, $post->status);
    }

    public function testTheMandatoryRelationsAreWiredUp(): void
    {
        // author and mainCategory are both NOT NULL join columns, so if either were left
        // unset the flush inside the factory would fail. This is the test that says the
        // happy path actually connects them.
        $data = $this->data();

        $post = $this->em()->find(Post::class, PostFactory::compileEntityForCreate($data, $this->em()));

        self::assertSame($data['author'], $post->author->id);
        self::assertSame($data['mainCategory'], $post->mainCategory->id);
    }

    public function testTagsAndCategoriesAreAttached(): void
    {
        $first = $this->tag('Alpha');
        $second = $this->tag('Beta');
        $extra = $this->createCategory();

        $post = $this->em()->find(Post::class, PostFactory::compileEntityForCreate(
            $this->data(['tags' => [$first->id, $second->id], 'categories' => [$extra->id]]),
            $this->em()
        ));

        self::assertCount(2, $post->tags);
        self::assertCount(1, $post->categories);
    }

    public function testATagThatNoLongerExistsIsSkippedRatherThanAttachedAsNull(): void
    {
        // find() returns null for a deleted id -- a tag removed while the form was open, or a
        // stale id in a resubmitted request. That null used to go into the collection, and
        // Doctrine then failed on flush with a message naming neither the tag nor the post.
        $real = $this->tag('Alpha');

        $post = $this->em()->find(Post::class, PostFactory::compileEntityForCreate(
            $this->data(['tags' => [$real->id, 999999]]),
            $this->em()
        ));

        self::assertCount(1, $post->tags);
        self::assertSame('Alpha', $post->tags->first()->title);
    }

    public function testACategoryThatNoLongerExistsIsSkippedToo(): void
    {
        $real = $this->createCategory();

        $post = $this->em()->find(Post::class, PostFactory::compileEntityForCreate(
            $this->data(['categories' => [$real->id, 999999]]),
            $this->em()
        ));

        self::assertCount(1, $post->categories);
    }

    public function testSeoFieldsFallBackToTheirContentEquivalents(): void
    {
        // seoTitle and seoDescription are NOT NULL columns, so without this fallback a
        // submission that omitted them would fail at the database rather than inherit the
        // title. Worth pinning as intended behaviour rather than a happy accident.
        $data = $this->data();
        unset($data['seoTitle'], $data['seoDescription']);

        $post = $this->em()->find(Post::class, PostFactory::compileEntityForCreate($data, $this->em()));

        self::assertSame($data['title'], $post->seoTitle);
        self::assertSame($data['shortDescription'], $post->seoDescription);
    }

    // ---- updates ---------------------------------------------------------------------------

    /** The update path takes the same shape plus an id, and does not flush -- its caller does. */
    private function updateData(Post $post, array $overrides = []): array
    {
        return $overrides + [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'shortDescription' => null,
            'blockData' => [],
            'status' => $post->status,
            'isLiveBlogPost' => false,
            'publishAt' => null,
            'author' => $post->author->id,
            'mainCategory' => $post->mainCategory->id,
            'categories' => [],
            'tags' => [],
            'seoTitle' => 'SEO title',
            'seoDescription' => 'SEO description',
        ];
    }

    public function testAnUpdateChangesTheFieldsItWasGiven(): void
    {
        $existing = $this->createPost(slug: 'before');

        PostFactory::compileEntityForUpdate(
            $this->updateData($existing, [
                'title' => 'After the edit',
                'slug' => 'after',
                'status' => Post::STATUS_DRAFT,
            ]),
            $this->em()
        );
        // CrudRepository::update() flushes after the factory returns; the factory deliberately
        // does not, so the two writes commit together.
        $this->em()->flush();
        $this->em()->clear();

        $post = $this->em()->find(Post::class, $existing->id);
        self::assertSame('After the edit', $post->title);
        self::assertSame('after', $post->slug);
        self::assertSame(Post::STATUS_DRAFT, $post->status);
    }

    public function testAnUpdateClearsTheFeaturedImageWhenNoneIsSubmitted(): void
    {
        // Asymmetric with create on purpose: create leaves the field alone, update nulls it,
        // because on an edit "no image in the form" means the editor took it off.
        $existing = $this->createPost();

        PostFactory::compileEntityForUpdate($this->updateData($existing), $this->em());
        $this->em()->flush();

        self::assertNull($this->em()->find(Post::class, $existing->id)->featuredImage);
    }

    public function testAnUpdateReplacesTagsRatherThanAddingToThem(): void
    {
        // The collection is rebuilt from the submission each time, so removing a tag in the
        // form removes it from the post. A factory that merged instead would make tags
        // impossible to take off.
        $existing = $this->createPost();
        $keep = $this->tag('Keep');
        $drop = $this->tag('Drop');

        PostFactory::compileEntityForUpdate(
            $this->updateData($existing, ['tags' => [$keep->id, $drop->id]]),
            $this->em()
        );
        $this->em()->flush();

        PostFactory::compileEntityForUpdate(
            $this->updateData($existing, ['tags' => [$keep->id]]),
            $this->em()
        );
        $this->em()->flush();
        $this->em()->clear();

        $post = $this->em()->find(Post::class, $existing->id);
        self::assertCount(1, $post->tags);
        self::assertSame('Keep', $post->tags->first()->title);
    }

    public function testAnUpdateCanMoveAPostToAnotherAuthor(): void
    {
        $existing = $this->createPost();
        $newAuthor = $this->createAuthor(first: 'Grace', last: 'Hopper');

        PostFactory::compileEntityForUpdate(
            $this->updateData($existing, ['author' => $newAuthor->id]),
            $this->em()
        );
        $this->em()->flush();
        $this->em()->clear();

        self::assertSame($newAuthor->id, $this->em()->find(Post::class, $existing->id)->author->id);
    }
}
