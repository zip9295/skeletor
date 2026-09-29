<?php

namespace Skeletor\Blog\Controller;

use Skeletor\Blog\Service\Category;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class CategoryController extends AjaxCrudController
{
    const TITLE_VIEW = "View categories";
    const TITLE_CREATE = "Create category";
    const TITLE_UPDATE = "Edit category: ";
    const TITLE_UPDATE_SUCCESS = "Category updated successfully.";
    const TITLE_CREATE_SUCCESS = "Category created successfully.";
    const TITLE_DELETE_SUCCESS = "Category deleted successfully.";
    const FORM_TITLE_ENTITY_IDENTIFIER = 'title';
    const PATH = 'category';

    public function __construct(
        Category $categoryService,
        Session $session,
        Config $config,
        Flash $flash,
        Engine $template, Logger $logger
    ) {
        parent::__construct($categoryService, $session, $config, $flash, $template, $logger);
    }
}
