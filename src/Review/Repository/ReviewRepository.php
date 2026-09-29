<?php
namespace Skeletor\Review\Repository;

use Skeletor\Core\Config\Config;
use League\Event\EventDispatcher;
use Skeletor\Blog\Repository\PostReadRepository;
use Skeletor\Blog\Repository\VideoReadRepository;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Review\Event\Review;
use Skeletor\Review\Mapper\Review as Mapper;
use Skeletor\Review\Model\Review as Model;
use Skeletor\Visitor\Model\Visitor;
use Skeletor\Visitor\Service\VisitorRead;

class ReviewRepository extends TableViewRepository
{

    protected $event = Review::class;
    /**
     * @param Mapper $mapper
     * @param \DateTime $dt
     */
    public function __construct(
        Mapper $mapper, private \DateTime $dt, private VisitorRead $visitor, protected ?EventDispatcher $dispatcher,
        private PostReadRepository $post, private VideoReadRepository $video
    ) {
        parent::__construct($mapper, $dispatcher);
    }

    public function make($itemData): Model
    {
        $data = [];
        foreach ($itemData as $name => $value) {
            if (in_array($name, ['createdAt', 'updatedAt'])) {
                $data[$name] = null;
                if ($value) {
                    if (strtotime($value)) {
                        $dt = clone $this->dt;
                        $dt->setTimestamp(strtotime($value));
                        $data[$name] = $dt;
                    } else {
                        $data[$name] = null;
                    }
                }
            } else {
                $data[$name] = $value;
            }
        }

        if (!isset($data['createdAt'])) {
            $data['createdAt'] = null;
        }
        if (!isset($data['updatedAt'])) {
            $data['updatedAt'] = null;
        }
        try {
            $data['visitor'] = $this->visitor->getById($data['visitorId']);
        } catch(\Exception $e) {
            $data['visitor'] = $this->getAnonymousUser();
        }
        unset($data['visitorId']);
        switch ($data['entityType']) {
            case 1:
                $data['entity'] = $this->post->getById($data['entityId']);
                break;
            case 2:
                $data['entity'] = $this->video->getById($data['entityId']);
                break;
            default:
                throw new \Exception('Unknown entity type');
        }
        $data['replies'] = $this->fetchAll(['replyTo' => $data['id']]);

        return new Model(...$data);
    }

    public function getSearchableColumns(): array
    {
        return ['body', 'email'];
    }

    public function getAverageRating($entityId, $entityType)
    {
        return round($this->mapper->getAverageRating($entityId, $entityType), 1);
    }

    public function getRatingCount($entityId, $entityType)
    {
        return $this->mapper->getRatingCount($entityId, $entityType);
    }

    public function getUserCommentBasedOnTimeout($visitorId, $entityId, $entityType, $timeout)
    {
        return $this->mapper->getUserCommentBasedOnTimeout($visitorId, $entityId, $entityType, $timeout);
    }

    protected function beforeSave($data)
    {
        $this->mapper->beginTransaction();
        return $data;
    }

    protected function afterSave($data, $oldModel): void
    {
        parent::afterSave($data, $oldModel);
        $this->mapper->commitTransaction();
    }

    public function deleteByReplyTo($id)
    {
        $this->mapper->deleteByReplyTo($id);
    }

    public function getAnonymousUser()
    {
        $dt = date_create();
        return new Visitor(
            0,
            '',
            '',
            1,
            1,
            '',
            clone $dt,
            'Anonymous',
            '',
            clone $dt,
            clone $dt,
            [],
            null,
            'Anonymous'
        );
    }
}
