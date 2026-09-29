<?php

declare(strict_types=1);

namespace Skeletor\Tests\Integration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Skeletor\Author\Entity\Author;
use Skeletor\Blog\Entity\Category;
use Skeletor\Blog\Entity\Post;
use Skeletor\Blog\Entity\Tag;
use Skeletor\Lead\Entity\Lead;
use Skeletor\Core\Cache\Service\ObjectCache;
use Skeletor\Page\Entity\Page;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Base for tests that need a real database.
 *
 * Most of the framework's logic -- validators, the login stack -- is testable with in-memory
 * doubles, and those tests live outside this hierarchy. What needs a database is anything
 * whose correctness IS the query: a uniqueness check, a repository filter, a factory writing
 * an entity graph. Faking those proves the fake works.
 *
 * Connects with the credentials from config/config-local.php but against a dedicated
 * "<dbname>_test" schema, so development data is never touched. The schema is built once per
 * process by SchemaTool; every test runs inside a transaction that is rolled back afterwards,
 * so nothing is ever committed -- no fsync per flush, no truncation, and complete isolation
 * without the cost of rebuilding between tests.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static ?EntityManagerInterface $em = null;

    protected int $seq = 0;

    public static function setUpBeforeClass(): void
    {
        if (self::$em !== null) {
            return; // schema already built earlier in this process
        }

        $root = dirname(__DIR__, 2);
        $local = require $root . '/config/config-local.php';
        $db = $local['db']['write'] ?? $local['db'];
        $testDbName = $db['name'] . '_test';

        // The same entity paths config/bootstrap.php maps. Kept in step by hand, which is why
        // EntityShapeTest asserts the schema builds at all -- a path missing here shows up as
        // a table that silently does not exist.
        $ormConfig = ORMSetup::createAttributeMetadataConfiguration(
            paths: [
                $root . '/src/User',
                $root . '/src/Core/Activity/Entity',
                $root . '/src/Core/Entity',
                $root . '/src/Core/Login',
                $root . '/src/Image',
                $root . '/src/File',
                $root . '/src/Blog',
                $root . '/src/Page/Entity',
                $root . '/src/ThemeSettings',
                $root . '/src/Translator',
                $root . '/src/Reference/Entity',
                $root . '/src/Lead/Entity',
                $root . '/src/Author/Entity',
            ],
            isDevMode: true,
        );
        $ormConfig->addCustomStringFunction('DATE', fn () => new \DoctrineExtensions\Query\Mysql\Date('DATE'));
        $ormConfig->addCustomStringFunction('YEAR', fn () => new \DoctrineExtensions\Query\Mysql\Year('YEAR'));
        // Generate proxies in memory rather than into the shared temp directory: files there can
        // end up owned by the web-server user, and the atomic rename then fails mid-run.
        $ormConfig->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);
        // symfony/var-exporter 8 removed LazyGhostTrait, so Doctrine's proxy factory throws
        // unless native lazy objects are used. Mirrors config/bootstrap.php.
        $ormConfig->enableNativeLazyObjects(true);

        $serverParams = [
            'driver' => 'pdo_mysql',
            'host' => $db['host'],
            'user' => $db['user'],
            'password' => $db['pass'],
        ];

        $serverConn = DriverManager::getConnection($serverParams);
        $serverConn->executeStatement(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $testDbName,
        ));
        $serverConn->close();

        $connection = DriverManager::getConnection($serverParams + ['dbname' => $testDbName], $ormConfig);
        // Let Doctrine's per-flush transactions nest inside the per-test transaction as
        // savepoints instead of committing to disk.
        $connection->setNestTransactionsWithSavepoints(true);
        self::$em = new EntityManager($connection, $ormConfig);

        $schemaTool = new SchemaTool(self::$em);
        $metadata = self::$em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function setUp(): void
    {
        // Doctrine closes the EntityManager when a flush throws, and it is shared by every test
        // in the run -- so without this one genuine failure reports as a hundred "EntityManager
        // is closed" errors and buries its own cause.
        if (!self::$em->isOpen()) {
            self::$em = new EntityManager(self::$em->getConnection(), self::$em->getConfiguration());
        }

        self::$em->clear();
        self::$em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = self::$em->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }
        self::$em->clear();
    }

    protected function em(): EntityManagerInterface
    {
        return self::$em;
    }

    // ---- entity builders ---------------------------------------------------------------
    //
    // Every builder fills the NOT NULL columns and nothing else, so a test states only the
    // field it is about. Adding a required column makes these fail loudly rather than letting
    // a hundred tests fail on a constraint violation.

    protected function createAuthor(string $first = 'Ada', string $last = 'Lovelace'): Author
    {
        $author = new Author();
        $author->firstName = $first;
        $author->lastName = $last . (++$this->seq);
        $author->displayName = null;
        $author->isActive = true;
        $author->description = null;
        $author->shortDescription = null;
        $author->avatar = null;
        $author->seoTitle = 'seo title';
        $author->seoDescription = 'seo description';

        self::$em->persist($author);
        self::$em->flush();

        return $author;
    }

    protected function createPage(?string $slug = null, int $status = 1): Page
    {
        $page = new Page();
        $page->title = 'Page ' . (++$this->seq);
        $page->slug = $slug ?? ('page-' . $this->seq);
        $page->status = $status;
        $page->blockData = null;
        $page->featuredImage = null;
        $page->seoTitle = 'seo title';
        $page->seoDescription = 'seo description';

        self::$em->persist($page);
        self::$em->flush();

        return $page;
    }

    protected function createLead(?string $email = null, ?string $phone = null): Lead
    {
        $lead = new Lead();
        $lead->email = $email ?? ('lead' . (++$this->seq) . '@example.com');
        $lead->firstName = 'First';
        $lead->lastName = 'Last';
        $lead->phoneNumber = $phone;
        $lead->status = 1;
        $lead->source = null;

        self::$em->persist($lead);
        self::$em->flush();

        return $lead;
    }

    /**
     * The two collaborators PostRepository takes beyond the entity manager.
     *
     * Nothing under test reaches them -- the repository's reads go through the entity manager
     * -- but the constructor requires them, so they are built for real rather than mocked: an
     * in-memory cache adapter, and the same serializer config/bootstrap.php assembles. A test
     * double here would only assert that the double was never called.
     */
    protected function objectCache(): ObjectCache
    {
        return new ObjectCache(new TagAwareAdapter(new ArrayAdapter()));
    }

    protected function serializer(): SerializerInterface
    {
        return new Serializer([new DateTimeNormalizer(), new ObjectNormalizer()], [new JsonEncoder()]);
    }

    protected function createCategory(?string $slug = null): Category
    {
        $category = new Category();
        $category->title = 'Category ' . (++$this->seq);
        $category->slug = $slug ?? ('category-' . $this->seq);
        $category->parent = null;
        $category->level = 1;
        // Category uses the Seo trait too; both of its columns are NOT NULL.
        $category->seoTitle = 'seo title';
        $category->seoDescription = 'seo description';

        self::$em->persist($category);
        self::$em->flush();

        return $category;
    }

    protected function createTag(?string $slug = null): Tag
    {
        $tag = new Tag();
        $tag->title = 'Tag ' . (++$this->seq);
        $tag->slug = $slug ?? ('tag-' . $this->seq);
        $tag->seoTitle = 'seo title';
        $tag->seoDescription = 'seo description';

        self::$em->persist($tag);
        self::$em->flush();

        return $tag;
    }

    /**
     * A published post with its two mandatory relations already in place.
     *
     * author and mainCategory are both NOT NULL join columns, which is why they are built here
     * rather than left to the caller: a post without them is not a state the application can
     * reach, and a builder that allowed it would let tests assert against one.
     */
    protected function createPost(?string $slug = null, ?Author $author = null, ?Category $category = null): Post
    {
        $post = new Post();
        $post->title = 'Post ' . (++$this->seq);
        $post->slug = $slug ?? ('post-' . $this->seq);
        $post->shortDescription = null;
        $post->blockData = null;
        $post->status = Post::STATUS_PUBLISHED;
        $post->featuredImage = null;
        $post->mainCategory = $category ?? $this->createCategory();
        $post->author = $author ?? $this->createAuthor();
        $post->isLiveBlogPost = false;
        $post->publishAt = null;
        $post->tags = new ArrayCollection();
        $post->categories = new ArrayCollection();
        $post->seoTitle = 'seo title';
        $post->seoDescription = 'seo description';

        self::$em->persist($post);
        self::$em->flush();

        return $post;
    }
}
