<?php
namespace Skeletor\Category\Filter;

use Skeletor\Core\Filter\Str;
use Skeletor\Blog\Service\UrlHelper;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Service\BlocksParser;
use Skeletor\Core\Security\Csrf;
use Skeletor\Category\Validator\Category as CategoryValidator;
use Skeletor\Core\Validator\ValidatorException;

class Category implements FilterInterface
{
    /**
     * @var CategoryValidator
     */
    private $validator;

    /**
     * @param CategoryValidator $validator
     */
    public function __construct(CategoryValidator $validator, private BlocksParser $blocksParser)
    {
        $this->validator = $validator;
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $postData): array
    {
        $alnum = static fn ($v) => Str::alnum((string) $v, true);
        $slug = UrlHelper::slugify($postData['title']);
        if ($postData['slug']) {
            $slug = $postData['slug'];
        }
        $description = '';
        if (isset($postData['blocks'])) {
            $description = json_encode($this->blocksParser->parse((array) $postData['blocks']));
        }
        $data = [
            'id' => $postData['id'],
            'title' => $postData['title'],
            'description' => $description,
            'slug' => $slug,
            'status' => $postData['status'],
            'parent' => $postData['parent'],
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
        ];
        if(isset($postData['count'])) {
            $data['count'] = $postData['count'] ?? 0;
        }
        if(isset($postData['tenantId'])) {
            $data['tenantId'] = $postData['tenantId'];
        }
        if(isset($postData['imageId'])) {
            $data['imageId'] = $postData['imageId'] ?? '';
        }
        if(isset($postData['secondDescription'])) {
            $data['secondDescription'] = $postData['secondDescription'] ?? '';
        }
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }

        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }
}