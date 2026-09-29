<?php

declare(strict_types=1);

namespace Skeletor\Tests\Integration\Blog;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Skeletor\Blog\Repository\CategoryRepository;
use Skeletor\Blog\Validator\Category;
use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Tests\Integration\IntegrationTestCase;
use Skeletor\Tests\Support\RecordingSessionManager;

/**
 * The blog category form: a title with a minimum length, a unique slug, and three SEO fields.
 *
 * Two rules were repaired while this was written, both the same defect the post and page forms
 * carried -- the title length was measured before trimming, and the edit guard trimmed a
 * boolean rather than the id. Neither showed as a visible failure, which is exactly why they
 * are pinned here.
 *
 * One rule below cannot fire in production and is asserted anyway: "SEO Image is required."
 * tests isset($data['seoImageId']), and Blog\Filter\Category substitutes '' rather than null
 * for a missing one, so the key always exists by the time the validator sees it. The check is
 * live in the validator's own terms, dead through the form. Blog\Filter\Post is the one filter
 * that passes null here, which is why the same rule does bite on posts.
 */
#[CoversClass(Category::class)]
final class CategoryValidatorTest extends IntegrationTestCase
{
    private RecordingSessionManager $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new RecordingSessionManager();
    }

    private function validator(): Category
    {
        return new Category(new Csrf($this->session), new CategoryRepository($this->em()));
    }

    /** Shaped the way Blog\Filter\Category hands it over: id is int|null, level an int. */
    private function form(array $overrides = []): array
    {
        return $overrides + [
            'id' => null,
            'title' => 'A perfectly good title',
            'slug' => 'a-perfectly-good-title',
            'parent' => null,
            'level' => 1,
            'seoTitle' => 'A title',
            'seoDescription' => 'A description',
            'seoImageId' => 8,
        ] + (new Csrf($this->session))->getTokenAsArray();
    }

    public function testACompleteCategoryIsAccepted(): void
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

    // ---- the title rule ----------------------------------------------------------------------

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

    public function testATitlePaddedOutToFiveCharactersIsStillTooShort(): void
    {
        // The repaired rule. Length was measured on the untrimmed value, so '  ab  ' cleared a
        // five character minimum while the stored title was two characters long.
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['title' => '  ab  '])));
        self::assertSame(['Title must be at least 5 characters.'], $validator->getMessages()['title']);
    }

    public function testATitleOfSpacesIsRequiredRatherThanLongEnough(): void
    {
        // Eight spaces used to satisfy "at least five characters" while also failing
        // "required", because the length was measured before the trim.
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['title' => '        '])));
        self::assertSame(['Title is required.'], $validator->getMessages()['title']);
    }

    // ---- the slug rule -----------------------------------------------------------------------

    public function testASlugIsRequired(): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['slug' => ''])));
        self::assertArrayHasKey('slug', $validator->getMessages());
    }

    public function testANewCategoryCannotTakeASlugThatIsAlreadyUsed(): void
    {
        $this->createCategory(slug: 'taken-slug');
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['slug' => 'taken-slug'])));
        self::assertSame(['Slug already exists.'], $validator->getMessages()['slug']);
    }

    public function testEditingACategoryMayKeepItsOwnSlug(): void
    {
        $category = $this->createCategory(slug: 'its-own-slug');

        self::assertTrue($this->validator()->isValid(
            $this->form(['id' => $category->id, 'slug' => 'its-own-slug']),
        ));
    }

    public function testEditingACategoryCannotStealAnotherCategorysSlug(): void
    {
        $mine = $this->createCategory(slug: 'mine');
        $this->createCategory(slug: 'yours');
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

    public function testAnEmptySeoFieldPassesBecauseTheRuleTestsPresenceOnly(): void
    {
        // Documented rather than repaired: the rules are isset() checks, and the filter fills
        // every one of them in, so an empty SEO title reaches a NOT NULL column as ''. Making
        // them non-empty checks would be a product decision, not a defect fix.
        self::assertTrue($this->validator()->isValid($this->form(['seoTitle' => '', 'seoImageId' => ''])));
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
