<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Activity\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Core\Activity\Service\Activity;
use Skeletor\User\Service\Session;

/**
 * The audit log's diff.
 *
 * computeDiff() decides what an edit is recorded as having changed, and it is wrong in two
 * directions with different costs. Too eager and the history fills with entries claiming
 * fields changed when nothing did -- which makes the log useless precisely when someone is
 * trying to find the one edit that mattered. Too lax and a real change is never recorded, and
 * reconstructAt() rebuilds a state that never existed.
 *
 * The canonicalisation is the interesting part: array values are compared by content rather
 * than by key order, because Doctrine hands back JSON columns and relation collections in
 * whatever order the query produced.
 */
#[CoversClass(Activity::class)]
final class ActivityTest extends TestCase
{
    private function service(): Activity
    {
        // computeDiff touches none of these; the service is constructed only because the
        // method is not static.
        return new Activity(
            $this->createStub(ActivityRepository::class),
            $this->createStub(Session::class),
            new NullLogger(),
        );
    }

    public function testNothingChangedIsAnEmptyDiff(): void
    {
        $state = ['title' => 'About', 'status' => 1];

        self::assertSame([], $this->service()->computeDiff($state, $state));
    }

    public function testAChangedFieldIsRecordedWithBothSides(): void
    {
        $diff = $this->service()->computeDiff(
            ['title' => 'About', 'status' => 1],
            ['title' => 'About us', 'status' => 1],
        );

        self::assertSame(['title' => ['old' => 'About', 'new' => 'About us']], $diff);
    }

    public function testAFieldAddedByTheEditIsRecordedAsComingFromNothing(): void
    {
        $diff = $this->service()->computeDiff(['title' => 'About'], ['title' => 'About', 'slug' => 'about']);

        self::assertSame(['slug' => ['old' => null, 'new' => 'about']], $diff);
    }

    public function testAFieldRemovedByTheEditIsRecorded(): void
    {
        // Both sides are walked, not just the new one -- a field that disappears is a change.
        $diff = $this->service()->computeDiff(['title' => 'About', 'slug' => 'about'], ['title' => 'About']);

        self::assertSame(['slug' => ['old' => 'about', 'new' => null]], $diff);
    }

    public function testArrayValuesAreComparedByContentNotByKeyOrder(): void
    {
        // Doctrine returns JSON columns and collections in query order, which is not stable.
        // Comparing raw would log a change on every save that touched nothing.
        $diff = $this->service()->computeDiff(
            ['seo' => ['title' => 'A', 'description' => 'B']],
            ['seo' => ['description' => 'B', 'title' => 'A']],
        );

        self::assertSame([], $diff);
    }

    public function testNestedArraysAreComparedByContentToo(): void
    {
        $diff = $this->service()->computeDiff(
            ['blocks' => [['type' => 'text', 'value' => 'hi']]],
            ['blocks' => [['value' => 'hi', 'type' => 'text']]],
        );

        self::assertSame([], $diff);
    }

    public function testAGenuineChangeInsideAnArrayIsStillCaught(): void
    {
        // The other half of the reordering rule: ignoring order must not mean ignoring content.
        $diff = $this->service()->computeDiff(
            ['seo' => ['title' => 'A']],
            ['seo' => ['title' => 'B']],
        );

        self::assertArrayHasKey('seo', $diff);
        self::assertSame(['title' => 'B'], $diff['seo']['new']);
    }

    public function testTheRecordedValuesAreTheOriginalsNotTheCanonicalisedOnes(): void
    {
        // Canonicalisation is for comparing. What goes into the log has to be what was
        // actually stored, or the history shows a shape the application never had.
        $diff = $this->service()->computeDiff(
            ['seo' => ['title' => 'A']],
            ['seo' => ['z' => 1, 'a' => 2]],
        );

        self::assertSame(['z' => 1, 'a' => 2], $diff['seo']['new'], 'key order preserved as given');
    }

    public function testDifferentTypesWithTheSameLooseValueCountAsAChange(): void
    {
        // Comparison is strict, so 1 and "1" differ. That is the safer direction for an audit
        // log: a spurious entry is noise, a missed one is a hole in the record.
        $diff = $this->service()->computeDiff(['status' => 1], ['status' => '1']);

        self::assertArrayHasKey('status', $diff);
    }

    public function testAnEmptyOldStateMakesEveryFieldNew(): void
    {
        // What a create looks like.
        $diff = $this->service()->computeDiff([], ['title' => 'About', 'status' => 1]);

        self::assertSame(['title', 'status'], array_keys($diff));
        self::assertNull($diff['title']['old']);
    }

    public function testAFieldThatIsNullOnBothSidesIsNotAChange(): void
    {
        self::assertSame([], $this->service()->computeDiff(['image' => null], ['image' => null]));
    }
}
