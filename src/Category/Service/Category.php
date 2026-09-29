<?php
namespace Skeletor\Category\Service;

use Skeletor\Attribute\Service\AttributeGroup;
use Skeletor\Category\Repository\CategoryMapperRepository;
use Skeletor\Category\Repository\CategoryRepository;
use Skeletor\Category\Repository\CategoryRepositoryInterface;
use Skeletor\Category\Repository\TenantCategoryMapperRepository;
use Skeletor\Feeder\Repository\CategoryParsingRule;
use Skeletor\Product\Service\Product;
use Skeletor\Product\Service\ProductSync;
use Skeletor\Tenant\Repository\TenantRepository;
use Skeletor\Tenant\Service\Tenant;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Skeletor\Image\Service\Image;
use Skeletor\Core\Config\Config;
use Skeletor\Core\TableView\Service\TableView;
use Psr\Log\LoggerInterface as Logger;

use Skeletor\User\Service\Session;
use Skeletor\Category\Filter\Category as CategoryFilter;

class Category extends TableView
{
    /**
     * @param CategoryRepositoryInterface $repo
     * @param Session $user
     * @param Logger $logger
     * @param CategoryFilter $filter
     * @param Config $config
     * @param Tenant|null $tenantService
     */
    public function __construct(
        CategoryRepositoryInterface $repo, Session $user, Logger $logger, CategoryFilter $filter, private Config $config,
        \Skeletor\Core\Activity\Service\Activity $activity,
        private ?Tenant $tenantService = null
//        private CategoryMapperRepository $categoryMapperRepo,
//        private TenantCategoryMapperRepository $tenantCategoryMapperRepo,
//        private AttributeGroup $attributeGroupService, private ProductSync $productSyncService, private CategoryParsingRule $categoryParsingRuleRepo
    ) {
        parent::__construct($repo, $user, $logger, $filter, activity: $activity);
    }

    public function getEntityData($id)
    {
        $category = $this->repo->getById($id);

        return [
            'id' => $category->getId(),
            'title' => $category->getName(),
            'parent' => $category->getParent()->getName(),
            'level' => $category->getLevel(),
            'image' => $category->getImage(),
            'status' => $category->getStatus(),
            'description' => $category->getDescription(),
            'count' => $category->getCount(),
            'createdAt' => $category->getUpdatedAt()->format('m.d.Y'),
            'updatedAt' => $category->getCreatedAt()->format('m.d.Y'),
        ];
    }

    public function getMasterCategories()
    {
        $items = [];
        $cats = $this->repo->fetchAll(['tenant' => null]);
        foreach($cats as $cat) {
            $items[] = ['value' => $cat->getTitle(), 'id' => $cat->getId()];
        }
        return $items;
    }

    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $category) {
//            $createActivity = $this->activity->fetchByEntityIdAndAction($merchant->getId(), $merchant::class);
//            $createdBy = 'N/A';
//            if (!empty($createActivity)) {
//                $createdBy = $createActivity[0]->getUser()->getFirstName() .' '. $createActivity[0]->getUser()->getLastName();
//            }
            $imgHtml = '';
            if ($category->getImage()) {
                $image = $category->getImage()->getFilename();
                $imageUrl = "/images" . $image;
                $imgHtml = '<img width="90px" src="'.$imageUrl.'" alt="category image">';
            }
            $itemData = [
                'id' => $category->getId(),
                'title' =>  [
                    'value' => $category->getName(),
                    'editColumn' => true,
                ],
                'slug' => $category->getSlug(),
                'tenant' => $category->getTenant()?->getName() ?? 'No tenant',
                'image' => $imgHtml,
                'status' => \Skeletor\Category\Model\Category::getHrStatus($category->getStatus()),
                'level' => $category->getLevel(),
                'parent' => $category->getParent()?->getTitle() ?? '/',
                'createdAt' => $category->getCreatedAt()->format('d.m.Y'),
                'updatedAt' => $category->getCreatedAt()->format('d.m.Y'),
                'count' => sprintf('<a href="/product/view/?category=%s">%s</a>', $category->getId(),$category->getCount()),
//                'sync' => '<div class="syncButtonContainer">
//                    <button data-action="/category/syncProducts/'.$category->getId(). '/" class="categorySyncButton">Sync</button>
//                </div>',
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $category->getId(),
            ];
        }
        return $items;
    }

    /**
     * @throws GuzzleException
     */
    private function syncCategories($category)
    {
        if($category->getTenant() !== 0) {
            $environment = $this->config->get('environment') ?? null;
            $tenant = $this->tenantService->getEntities(['tenantId' => $category->getTenant()])[0];
            $url = $tenant->getProductionUrl();
            if($url) {
                $url .= $this->config->get('categorySyncUrl');
            }
            if ($environment === 'development' || $environment === 'local') {
                return;
                $url = $tenant['developmentUrl'];
                if($url) {
                    $url .= $this->config->get('categorySyncUrl');
                }
            }
            if ($url) {
                $client = new Client();
                $client->request('get', (string)$url);
            }
        }
    }

    public function compileTableColumns()
    {
        $tenants = $this->tenantService->getEntities();
        $tenantFilterData = ['null' => 'global'];
        foreach ($tenants as $tenant) {
            $tenantFilterData[$tenant->getId()] = $tenant->getName();
        }
        $columnDefinitions = [
            ['name' => 'title', 'label' => 'Name'],
            ['name' => 'slug', 'label' => 'Slug'],
            ['name' => 'tenant', 'label' => 'Tenant', 'filterData' => $tenantFilterData],
            ['name' => 'image', 'label' => 'Image'],
            ['name' => 'parent', 'label' => 'Parent'],
            ['name' => 'title', 'label' => 'Name'],
            ['name' => 'level', 'label' => 'level', 'filterData' => [1 => 1, 2 => 2, 3 => 3]],
            ['name' => 'status', 'label' => 'Status'],
            ['name' => 'count', 'label' => 'Count'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at'],
        ];

        return $columnDefinitions;
    }
    
    public function fetchAllArray($filter = [])
    {
        return $this->repo->fetchAllArray($filter);
    }

    /**
     * @return array
     */
    public function getCategoryHierarchy(): array
    {
        $array = [];
        $topLevel = $this->getEntities(['level' => 1, 'tenant' => null]);
        foreach ($topLevel as $key => $cat) {
            $array[$key]['topLevelCategory'] = $cat;
            $secondLevel = $this->getEntities(['parent' => $cat->getId()]);
            if ($secondLevel !== []) {
                foreach ($secondLevel as $secLevelKey => $secondCat) {
                    $array[$key]['secondLevelCategories'][$secLevelKey][] = $secondCat;
                    $thirdLevel = $this->getEntities(['parent' => $secondCat->getId()]);
                    if ($thirdLevel !== []) {
                        foreach ($thirdLevel as $thirdCat) {
                            $array[$key]['secondLevelCategories'][$secLevelKey]['thirdLevelCategories'][] = $thirdCat;
                        }
                    }
                }
            }
        }
        return $array;
    }

    public function fetchForApi($tenantId)
    {
        return $this->repo->fetchForApi($tenantId);
    }

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function update(array $data)
    {
        if ($this->filter) {
            $data = $this->filter->filter($data);
        }
        $message = null;
        $oldModel = $this->repo->getById($data['id']);
        $data['level'] =  1;
        //Determine level of category
        if(isset($data['parent']) && (int)$data['parent'] !== 0) {
            $parent = $this->repo->getById($data['parent']);
            $parentLevel = (int)$parent->getLevel();
            if($parentLevel < 3) {
                $data['level'] = $parentLevel + 1;
            }
        }
        //WHen level is going down
        if($oldModel->getLevel() < $data['level']) {
            /**
             * when level 1 category is changed to level 2 check if there is any level 3 category in its tree and if
             * there is one prevent the change if not change the level of the category and its children
             */
            if ($data['level'] === 2 && $oldModel->getLevel() === 1) {
                $catChildren = $this->repo->fetchAll(['parent' => $oldModel->getId()]);
                foreach ($catChildren as $child) {
                  $grandChildren = $this->repo->fetchAll(['parent' => $child->getId()]);
                  if($grandChildren !== []) {
                      $data['level'] = $oldModel->getLevel();
                      $data['parent'] = $oldModel->getParent()->getId();
                      $message = 'You can not change the level of this category because it has level 3 categories in its tree';
                  }
                }
                if (!$message) {
                    foreach ($catChildren as $child) {
                        $this->updateField('level', 3, $child->getId());
                    }
                }
            }
            /**
             * when level 2 category is changed to level 3 check if there is any level 3 category in its tree and if
             * there is one prevent the change if not change the level of the category and its children
             */
            if ($data['level'] === 3 && $oldModel->getLevel() === 2) {
                $catChildren = $this->repo->fetchAll(['parent' => $oldModel->getId()]);
                if($catChildren !== []) {
                    $data['level'] = $oldModel->getLevel();
                    $data['parent'] = $oldModel->getParent()->getId();
                    $message = 'You can not change the level of this category because it has level 3 categories in its tree';
                }
            }
        }

        if($data['level'] === 1 && $oldModel->getLevel() === 2) { // level 2 to level 1
            $children = $this->repo->fetchAll(['parent' => $oldModel->getId()]);
            foreach($children as $child) {
                $this->updateField('level',2, $child->getId());
            }
        }
        $data['image'] = '';
        $model = $this->repo->update($data);
//        if ($model->getParent()?->getId() !== $oldModel->getParent()?->getId()) {
//            $this->productSyncService->syncAllProductsInCatTree($model->getId());
//        }

        if($message) {
            throw new \Exception($message);
        }

//        $this->syncCategories($model);
        return $model;
    }

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function create(array $data)
    {
        if ($this->filter) {
            $data = $this->filter->filter($data);
        }

        //disable this for migration
//        $data['level'] =  1;
//        if(isset($data['parent']) && (int)$data['parent'] !== 0) {
//            $parent = $this->repo->getById($data['parent']);
//            $parentLevel = $parent->getLevel();
//            if($parentLevel < 3) {
//                $data['level'] = $parentLevel + 1;
//            }
//        }

        $data['image'] = '';
        $model = $this->repo->create($data);
//        if($model->getTenant() === 0) {
//            $tenants = $this->tenantService->getEntities();
//            foreach ($tenants as $tenant) {
//                $this->tenantCategoryMapperRepo->create([
//                    'categoryId' => $model->getId(),
//                    'tenantId' => $tenant->getId(),
//                    'mappedToId' => 0
//                ]);
//            }
//        }
//        $this->syncCategories($model);
        return $model;
    }

    /**
     * @throws \Exception
     */
    public function delete($id)
    {
        $model = $this->repo->getById($id);
        if ((int)$model->getCount() !== 0) {
            throw new \Exception(sprintf('Category %s cannot be deleted product count is not 0', $model->getTitle()));
        }

//        $tenantMapping = $this->tenantCategoryMapperRepo->fetchAll(['categoryId' => $model->getId()]);
//        if(count($this->tenantCategoryMapperRepo->fetchAll(['mappedToId' => $model->getId()])) > 0) {
//            throw new \Exception('Category cannot be deleted because the category is mapped');
//        }

//        $mappedCats = $this->tenantCategoryMapperRepo->fetchAll(['categoryId' => $model->getId()]);
//        foreach ($mappedCats as $mappedCat){
//            if ($mappedCat->getRemoteCategory() !== 0 && $mappedCat->getRemoteCategory() !== null) {
//                throw new \Exception('Category cannot be deleted because there are tenant categories mapped to this category');
//            }
//            $this->tenantCategoryMapperRepo->delete($mappedCat->getId());
//        }
//        if(count($this->categoryMapperRepo->fetchAll(['categoryId' => $model->getId()])) > 0) {
//            throw new \Exception('Category  cannot be deleted because the category is mapped');
//        }
//        $value = serialize(['category' => "{$model->getId()}"]);
//        $value = str_replace('a:1:{', '', $value);
//        $value = str_replace('}', '', $value);
//        $rules = $this->categoryParsingRuleRepo->searchInData($value);
//        if (count($rules) > 0) {
//            throw new \Exception('Category cannot be deleted because there are parsing rule action/s that use this category');
//        }
//        $rules = $this->categoryParsingRuleRepo->getRulesByCatId($model->getId());
//        if (count($rules) > 0) {
//            throw new \Exception('Category cannot be deleted because there are parsing rule/s that use this category');
//        }

        $catsToDelete = [$model];
        if ($model->getLevel() !== 3) {
            $children = $this->getEntities(['parent' => $model->getId()]);
            foreach($children as $child) {
                if ((int)$child->getCount() !== 0) {
                    throw new \Exception(sprintf('Child category: %s has %d products, category cannot be deleted.', $child->getTitle(), $child->getCount()));
                }
//                if(count($this->tenantCategoryMapperRepo->fetchAll(['mappedToId' => $child->getId()])) > 0) {
//                    throw new \Exception('Category  cannot be deleted because the category is mapped');
//                }
//                if(count($this->categoryMapperRepo->fetchAll(['categoryId' => $child->getId()])) > 0) {
//                    throw new \Exception('Category  cannot be deleted because the category is mapped');
//                }
                $catsToDelete[] = $child;
                if ($child->getLevel() === 2) {
                    $children = $this->getEntities(['parent' => $child->getId()]);
                    foreach($children as $subChild) {
                        if ((int)$subChild->getCount() !== 0) {
                            throw new \Exception(sprintf('Child category: %s has %d products, category cannot be deleted.', $subChild->getTitle(), $subChild->getCount()));
                        }
//                        if(count($this->tenantCategoryMapperRepo->fetchAll(['mappedToId' => $subChild->getId()])) > 0) {
//                            throw new \Exception('Category  cannot be deleted because the category is mapped');
//                        }
//                        if(count($this->categoryMapperRepo->fetchAll(['categoryId' => $subChild->getId()])) > 0) {
//                            throw new \Exception('Category cannot be deleted because the category is mapped');
//                        }
                        $catsToDelete[] = $subChild;
                    }
                }
            }
        }
        foreach ($catsToDelete as $cat) {
            $this->repo->delete($cat->getId());
//            if($cat->getTenant() === 0) {
//                $entities = $this->attributeGroupService->getEntities(['categoryId' => $cat->getId()]);
//                foreach($entities as $entity) {
//                    $this->attributeGroupService->updateField('categoryId', null, $entity->getId());
//                }
//            }
        }

//        if(count($tenantMapping) > 0) {
//            $this->tenantCategoryMapperRepo->delete($tenantMapping[0]->getId());
//        }

//        $this->syncCategories($model);
    }

    public function getChildCategories($catId)
    {
        $cats = [];
        $targetCat = $this->getById($catId);
        $children = $this->repo->fetchAll(['parent' => $targetCat->getId()]);
        foreach($children as $child) {
            $cats[] = $child;
            $grandChildren = $this->repo->fetchAll(['parent' => $child->getId()]);
            foreach($grandChildren as $grandChild) {
                $cats[] = $grandChild;
            }
        }
        return $cats;
    }
}