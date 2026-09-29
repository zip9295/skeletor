<?php

namespace Skeletor\Blog\Repository;

use Skeletor\Blog\Entity\Post;
use Skeletor\Blog\Factory\PostFactory;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\Cache\Service\ObjectCache;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PostRepository extends TableViewRepository
{
    const ENTITY = Post::class;
    const FACTORY = PostFactory::class;

    const CACHE_KEY_POST = 'blogPost_';
    const CACHE_KEY_LIST = 'blogPostList_';

    public function __construct(
        protected EntityManagerInterface $entityManager, private ObjectCache $cache, private SerializerInterface $serializer
    ) {
        parent::__construct($this->entityManager);
    }

    /**
     * @TODO add caching
     *
     * @param $categoryId
     * @param $limit
     * @param $offset
     * @return mixed
     * @throws \Doctrine\DBAL\Exception
     */
    public function getPosts($categoryId, $limit = 15, $offset = 0)
    {
        $sql = "SELECT id FROM post p 
          LEFT JOIN post_category pc ON (p.id = pc.post_id)
          WHERE p.status = 1 
            AND (p.publishAt IS NULL OR p.publishAt <= CURRENT_DATE())
            AND 
            (p.categoryId = :categoryId
            OR pc.category_id = :categoryId)
            ORDER BY p.createdAt, p.publishAt DESC
            LIMIT :limit OFFSET :offset";
        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('categoryId', $categoryId);
        $stmt->bindValue('limit', $limit, ParameterType::INTEGER);
        $stmt->bindValue('offset', $offset, ParameterType::INTEGER);
        $ids = [];
        foreach ($stmt->executeQuery()->fetchAllAssociative() as $item) {
            $ids[] = $item['id'];
        }
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p')
            ->from(Post::class, 'p')
            ->where($qb->expr()->in('p.id', ':ids'))
            ->setParameter('ids', $ids)
            ->orderBy('p.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function search($term)
    {
//        $sql = "SELECT * FROM post WHERE MATCH(title, shortDescription, blockData) AGAINST(:searchTerm IN BOOLEAN MODE)
//        AND status = 1
//        ORDER BY createdAt DESC";
        $sql = "SELECT id FROM post WHERE status = 1 
            AND 
            (title LIKE :searchTerm
            OR shortDescription LIKE :searchTerm
             OR JSON_UNQUOTE(JSON_EXTRACT(blockData, '$[*].text.text')) LIKE :searchTerm)
            ORDER BY createdAt DESC";
        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('searchTerm', "%".$term."%");
        $ids = [];
        foreach ($stmt->executeQuery()->fetchAllAssociative() as $item) {
            $ids[] = $item['id'];
        }
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p')
            ->from(Post::class, 'p')
            ->where('p.status = :status')
            ->andWhere($qb->expr()->in('p.id', ':ids'))
            ->setParameter('status', Post::STATUS_PUBLISHED)
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * Force doctrine to eager load all relations
     *
     * @param $postId
     * @return mixed
     */
    public function getByIdEager($postId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p', 'featuredImage', 'tags', 'mainCategory', 'mainSeoImage', 'categories', 'catSeoImage',
            'postSeoImage', 'tagsSeoImage', 'categoryParent')
            ->from(Post::class, 'p')
            ->leftJoin('p.featuredImage', 'featuredImage')
            ->leftJoin('p.tags', 'tags')
            ->leftJoin('p.mainCategory', 'mainCategory')
            ->leftJoin('mainCategory.seoImage', 'mainSeoImage')
            ->leftJoin('tags.seoImage', 'tagsSeoImage')
            ->leftJoin('p.categories', 'categories')
            ->leftJoin('categories.parent', 'categoryParent')
            ->leftJoin('categories.seoImage', 'catSeoImage')
            ->leftJoin('p.seoImage', 'postSeoImage')
            ->where('p.id = :id')
            ->setParameter('id', $postId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getByIdCached($postId)
    {
        // bypassing for now
//        return $this->getById($postId);

        $cacheKey = static::CACHE_KEY_POST . $postId;

        $cachedData = $this->cache->adapter->get($cacheKey, function (ItemInterface $item) use ($postId, $cacheKey) {
            $item->tag([$cacheKey]);
            $item->expiresAfter(null);
//            $post = $this->getById($postId);
            $post = $this->getByIdEager($postId);

//            return $this->serializer->serialize($post, 'json');
            return igbinary_serialize($post);
        });
        return igbinary_unserialize($cachedData);
//        return $this->serializer->deserialize($cachedData, Post::class, 'json');
    }

    public function invalidatePost($postId)
    {
        $this->cache->adapter->invalidateTags([static::CACHE_KEY_POST . $postId]);
    }

    public function update($data)
    {
        $id = static::FACTORY::compileEntityForUpdate($data, $this->entityManager);
        $this->entityManager->flush();
        $this->invalidatePost($id);

        return $this->getById($id);
    }

    public function delete($id): bool
    {
        $entity = $this->entityManager->getRepository(static::ENTITY)->find($id);
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
        $this->invalidatePost($id);

        return true;
    }

    public function updateField($field, $value, $entityId)
    {
        parent::updateField($field, $value, $entityId);
        $this->invalidatePost($entityId);
    }

    public function getSearchableColumns(): array
    {
        return ['a.title', 'a.blockData', 'a.shortDescription'];
    }

    // @TODO add pagination
    public function getPostsForTag(int $tagId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p')
            ->from(Post::class, 'p')
            ->join('p.tags', 't')
            ->where('t.id = :tagId')
            ->setParameter('tagId', $tagId)
            ->orderBy('p.createdAt', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function getCountPostsForTag(int $tagId): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(p.id)')
            ->from(Post::class, 'p')
            ->join('p.tags', 't')
            ->where('t.id = :tagId')
            ->setParameter('tagId', $tagId);
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getCountPostsForCategory(int $categoryId): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(p.id)')
            ->from(Post::class, 'p')
            ->where('p.mainCategory = :categoryId')
            // @todo add secondary categories to count
//            ->orWhere('p.categories = :categoryId')
            ->andWhere('p.status = :status')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('status', Post::STATUS_PUBLISHED);
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getNewestPublishedPostsWithExcludedIds(int $limit, ?int $categoryId = null, array $excludedIds = []): array
    {
        if(empty($excludedIds)) {
            $excludedIds = [];
        }
        $cacheKey = static::CACHE_KEY_LIST .'_NEWEST_EXCLUDE_IDS_'. implode(',', $excludedIds) .'_catID_'. $categoryId . '_number_' . $limit;
        $collection = $this->cache->adapter->get($cacheKey, function (ItemInterface $item) use ($limit, $cacheKey, $excludedIds, $categoryId) {
            $excludedIds[] = $cacheKey;
            $item->expiresAfter(60);
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('p')
                ->from(Post::class, 'p')
                ->where('p.status = :status');
            if(!empty($excludedIds)) {
                $qb->andWhere($qb->expr()->notIn('p.id', ':excludedIds'));
                $qb->setParameter('excludedIds', $excludedIds);
            }
            if ($categoryId) {
                $qb->andWhere('p.mainCategory = :categoryId')
                    ->setParameter('categoryId', $categoryId);
            }
            $qb->setParameter('status', Post::STATUS_PUBLISHED)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

            return $qb->getQuery()->getResult();
        });

        return $collection;
    }

    /**
     * For usage by blocks
     *
     * @param array $ids
     * @return array
     */
    public function getPublishedPostsByIds(array $ids): array
    {
        if(empty($ids)) {
            return [];
        }
        $cacheKey = static::CACHE_KEY_LIST .'_FIXED_IDS_'. implode(',', $ids);

        $collection = $this->cache->adapter->get($cacheKey, function (ItemInterface $item) use ($ids, $cacheKey) {
//            $ids[] = $cacheKey;
            foreach ($ids as $id) {
                $item->tag(static::CACHE_KEY_LIST .'_FIXED_IDS_ID_' . $id);
            }
            $item->expiresAfter(60);
            $qb = $this->entityManager->createQueryBuilder();
            $qb->select('p')
                ->from(Post::class, 'p')
                ->where('p.status = :status')
                ->andWhere($qb->expr()->in('p.id', ':ids'))
                ->setParameter('status', Post::STATUS_PUBLISHED)
                ->setParameter('ids', $ids);

            $results = $qb->getQuery()->getResult();

            $indexed = [];
            foreach ($results as $post) {
                $indexed[$post->getId()] = $post;
            }

            $ordered = [];
            foreach ($ids as $id) {
                if (isset($indexed[$id])) {
                    $ordered[] = $indexed[$id];
                }
            }

            return $ordered;
        });

        return $collection;
    }

    public function getPublishedOrdered(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p')
            ->from(Post::class, 'p')
            ->where('p.status = :status')
            ->setParameter('status', Post::STATUS_PUBLISHED)
            ->orderBy('COALESCE(p.publishAt, p.createdAt)', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getPostContent(int $postId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p.blockData')
            ->from(Post::class, 'p')
            ->where('p.id = :id')
            ->setParameter('id', $postId);

        return $qb->getQuery()->getOneOrNullResult()['blockData'] ?? null;
    }

}