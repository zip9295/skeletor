<?php
namespace Skeletor\Visitor\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\Tenant\Model\Tenant;
use Skeletor\Visitor\Repository\VisitorRepositoryInterface as VisitorRepo;
use Skeletor\User\Service\Session;
use Skeletor\Visitor\Filter\Visitor as Filter;

class Visitor extends TableView
{

    protected $session;

    public function __construct(
        VisitorRepo $repo, Session $session, Logger $logger,
        \Skeletor\Core\Activity\Service\Activity $activity, ?Filter $filter = null) {
        parent::__construct($repo, $session, $logger, $filter, activity: $activity);
    }

    /**
     * @param $email
     * @return \Skeletor\Visitor\Model\Visitor
     * @throws \Exception
     */
    public function getByEmail($email)
    {
        return $this->repo->getByEmail($email);
    }

    public function prepareEntities($entities)
    {
        $items = [];
        /* @var \Skeletor\Visitor\Model\Visitor $visitor */
        foreach ($entities as $visitor) {
            $imgHtml = '';
//            $image = $visitor->getAvatar();
//            if (is_object($image)) {
//                $image = $image->getFilename();
//                $imageUrl = "/images" . $image;
//                $imgHtml = '<img width="90px" src="'.$imageUrl.'" alt="category image">';
//            }

            $editRoute = sprintf("<a href='/visitor/form/%s/' title='Edit visitor'>%s</a>", $visitor->getId(), $visitor->getEmail());
            $itemData = [
                'id' => $visitor->getId(),
                'email' => [
                    'value' => $editRoute,
                    'editColumn' => true,
                ],
                'firstName' => $visitor->getFirstName(),
                'lastName' => $visitor->getLastName(),
                'isActive' => ($visitor->getIsActive()) ? 'Yes':'No',
//                'avatar' => $imgHtml,
                'createdAt' => $visitor->getCreatedAt()->format('d.m.Y'),
                'updatedAt' => $visitor->getUpdatedAt()->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $visitor->getId(),
            ];
        }
        return $items;
    }

    public function compileTableColumns()
    {
        $columnDefinitions = [
//            ['name' => 'id', 'label' => '#'],
            ['name' => 'email', 'label' => 'Email'],
            ['name' => 'firstName', 'label' => 'First name'],
            ['name' => 'lastName', 'label' => 'Last name'],
            ['name' => 'isActive', 'label' => 'Is active', 'filterData' => [1 => 'Yes', 0 => 'No']],
//            ['name' => 'avatar', 'label' => 'Avatar'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at'],
        ];

        return $columnDefinitions;
    }
}
