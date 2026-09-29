<?php

declare(strict_types=1);

namespace Skeletor\Tests\Integration\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Page\Entity\Page as PageEntity;
use Skeletor\Page\Repository\PageRepository;
use Skeletor\Page\Validator\Page;
use Skeletor\Tests\Integration\IntegrationTestCase;
use Skeletor\Tests\Support\RecordingSessionManager;

/**
 * The page form's rules, against a real repository.
 *
 * The slug rule is the reason this needs a database: "is this slug taken, and if so is it
 * taken by the page I am editing" is a query, and a faked repository would only prove the
 * fake answers the way the test expects. The distinction matters -- get it wrong in the
 * lenient direction and two pages share a URL; get it wrong in the strict direction and
 * nobody can save an edit to an existing page without renaming it.
 */
#[CoversClass(Page::class)]
final class PageValidatorTest extends IntegrationTestCase
{
    private RecordingSessionManager $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new RecordingSessionManager();
    }

    private function validator(): Page
    {
        return new Page(new Csrf($this->session), new PageRepository($this->em()));
    }

    /**
     * A submission shaped the way Page\Filter hands it over: id is int|null, status is always
     * an int. Tests that pass strings here would be testing a form that cannot occur.
     */
    private function form(array $overrides = []): array
    {
        return $overrides + [
            'id' => null,
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => PageEntity::STATUS_PUBLISHED,
            'seoTitle' => 'About us',
            'seoDescription' => 'Who we are.',
            'seoImageId' => '3',
        ] + (new Csrf($this->session))->getTokenAsArray();
    }

    public function testACompleteNewPageIsAccepted(): void
    {
        $validator = $this->validator();

        self::assertTrue($validator->isValid($this->form()));
        self::assertSame([], $validator->getMessages());
    }

    // ---- the slug rule -----------------------------------------------------------------

    public function testANewPageCannotTakeASlugThatIsAlreadyUsed(): void
    {
        $this->createPage(slug: 'about-us');
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form()));
        self::assertArrayHasKey('slug', $validator->getMessages());
    }

    public function testEditingAPageMayKeepItsOwnSlug(): void
    {
        // The half that breaks in the strict direction: if this fails, saving any edit to an
        // existing page demands a new URL.
        $page = $this->createPage(slug: 'about-us');
        $validator = $this->validator();

        self::assertTrue($validator->isValid($this->form(['id' => $page->id])));
        self::assertSame([], $validator->getMessages());
    }

    public function testEditingAPageCannotStealAnotherPagesSlug(): void
    {
        $other = $this->createPage(slug: 'about-us');
        $mine = $this->createPage(slug: 'contact');
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form(['id' => $mine->id, 'slug' => 'about-us'])));
        self::assertArrayHasKey('slug', $validator->getMessages());
    }

    public function testAnUnusedSlugIsFineWhetherCreatingOrEditing(): void
    {
        $page = $this->createPage(slug: 'contact');

        self::assertTrue($this->validator()->isValid($this->form(['slug' => 'brand-new'])));
        self::assertTrue($this->validator()->isValid($this->form(['id' => $page->id, 'slug' => 'brand-new'])));
    }

    // ---- required fields ----------------------------------------------------------------

    /** @return array<string, array{string}> */
    public static function requiredFields(): array
    {
        return [
            'title' => ['title'],
            'seo title' => ['seoTitle'],
            'seo description' => ['seoDescription'],
            'seo image' => ['seoImageId'],
        ];
    }

    #[DataProvider('requiredFields')]
    public function testARequiredFieldLeftEmptyIsReported(string $field): void
    {
        $validator = $this->validator();

        self::assertFalse($validator->isValid($this->form([$field => ''])));
        self::assertArrayHasKey($field, $validator->getMessages());
    }

    public function testAMissingTitleIsRejectedRatherThanWarnedAbout(): void
    {
        $form = $this->form();
        unset($form['title']);

        self::assertFalse($this->validator()->isValid($form));
    }

    // ---- status -------------------------------------------------------------------------

    public function testEveryRealStatusIsAccepted(): void
    {
        // Including STATUS_NEW, which is 0. A validator that rejected falsy statuses -- or the
        // dead `=== '-1'` check that used to sit here, had it ever worked -- would make a new
        // page unsaveable.
        foreach (PageEntity::getHrStatuses() as $status => $label) {
            self::assertTrue(
                $this->validator()->isValid($this->form(['status' => $status, 'slug' => 'slug-' . $status])),
                sprintf('status %d (%s) should be valid', $status, $label)
            );
        }
    }

    public function testABadFormTokenThrowsBeforeAnythingIsQueried(): void
    {
        $form = $this->form();
        $form[Csrf::TOKEN_NAME] = 'not the token';

        $this->expectException(InvalidFormTokenException::class);
        $this->validator()->isValid($form);
    }
}
