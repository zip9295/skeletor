<?php
namespace Skeletor\Review\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Core\TableView\Service\Table as TableView;
use Skeletor\Review\Mapper\ReviewLike;
use Skeletor\Review\Repository\ReviewRepository;
use Skeletor\Review\Filter\Review as Filter;
use Skeletor\User\Service\Session;

class Review extends TableView
{
    /**
     * @param ReviewRepository $repo
     * @param Session $userSession
     * @param Logger $logger
     * @param ActivityRepository $activity
     * @param Filter $filter
     */
    public function __construct(
        ReviewRepository $repo, Session $userSession, Logger $logger, Filter $filter, private ReviewLike $reviewLike,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repo, $userSession, $logger, null, $filter, activity: $activity);
    }

    public function getEntityData(int $id)
    {
        $image = $this->repo->getById($id);

        return [
            'id' => $image->getId(),
            'createdAt' => $image->getUpdatedAt()->format('m.d.Y'),
            'updatedAt' => $image->getCreatedAt()->format('m.d.Y'),
        ];
    }

    public function fetchTableData($search, $filter, $offset, $limit, $order, $uncountableFilter = null)
    {
        $data = $this->repo->fetchTableData($search, $filter, $offset, $limit, $order, $uncountableFilter);
        if ($data['count'] === "0") {
            return [
                'count' => 0,
                'entities' => [],
            ];
        }
        $items = [];
        foreach ($data['entities'] as $review) {
            $itemData = [
                'id' => $review->getId(),
                'body' =>  [
                    'value' => $review->getBody(),
                    'editColumn' => true,
                ],
                'visitorId' => $review->getVisitor()->getFirstName() .' '. $review->getVisitor()->getLastName(),
                'email' => $review->getEmail(),
                'entity' => $review->getEntity()->getTitle(),
                'entityId' => $review->getEntityId(),
                'likeCount' => $review->getLikeCount(),
                'rating' => $review->getRating(),
                'status' => $review->getHrStatus($review->getStatus()),
                'createdAt' => $review->getCreatedAt()->format('d.m.Y'),
                'updatedAt' => $review->getCreatedAt()->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $review->getId(),
                'entityType' => $review->getEntity()->getType()
            ];
        }

        return [
            'count' => $data['count'],
            'entities' => $items,
        ];
    }

    public function compileTableColumns()
    {
        $columnDefinitions = [
            ['name' => 'id', 'label' => '#'],
            ['name' => 'body', 'label' => 'Content'],
            ['name' => 'entity', 'label' => 'Entity', 'sortable' => false],
            ['name' => 'visitorId', 'label' => 'Visitor'],
            ['name' => 'email', 'label' => 'Email'],
            ['name' => 'likeCount', 'label' => 'Like count'],
            ['name' => 'rating', 'label' => 'Rating'],
            ['name' => 'status', 'label' => 'Status', 'filterData' => \Skeletor\Review\Model\Review::getHrStatuses()],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at'],
        ];

        return $columnDefinitions;
    }

    public function getAverageRating($entityId, $entityType)
    {
        return $this->repo->getAverageRating($entityId, $entityType);
    }

    public function getRatingCount($entityId, $entityType)
    {
        return $this->repo->getRatingCount($entityId, $entityType);
    }

    public function unlikeReview($reviewId, $visitorId, $newCount)
    {
        $this->reviewLike->unlike($reviewId, $visitorId);
        $this->repo->updateField('likeCount', $newCount, $reviewId);
    }

    public function likeReview($reviewId, $visitorId, $newCount)
    {
        $this->reviewLike->insert([
            'reviewId' => $reviewId,
            'visitorId' => $visitorId,
        ]);
        $this->repo->updateField('likeCount', $newCount, $reviewId);
    }

    public function hasLiked($reviewId, $visitorId)
    {
        return (bool) count($this->reviewLike->fetchAll(['reviewId' => $reviewId, 'visitorId' => $visitorId]));
    }

    public function canUserCommentBasedOnTimeout($visitorId, $entityId, $entityType, $timeout)
    {
        if($timeout === 0) {
            return true;
        }
        return !(bool) count($this->repo->getUserCommentBasedOnTimeout($visitorId, $entityId, $entityType, $timeout));
    }

    public function deleteByReplyTo($id)
    {
        $this->repo->deleteByReplyTo($id);
    }

    public function update(array $data, bool $useCSRF = true)
    {
        if ($this->filter) {
            $data = $this->filter->filter($data, $useCSRF);
        }
        $oldModel = $this->repo->getById($data['id']);
        $model = $this->repo->update($data);

        return $model;
    }

    public function create(array $data, bool $useCSRF = true)
    {
        if ($this->filter) {
            $data = $this->filter->filter($data, $useCSRF);
        }
        $model = $this->repo->create($data);

        return $model;
    }


}