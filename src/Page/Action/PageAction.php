<?php

namespace Skeletor\Page\Action;

use Skeletor\Core\Config\Config;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Skeletor\Page\Repository\PageRepository;
use Twig\Environment;

class PageAction extends Html
{
    private $repository;

    /**
     * PageAction constructor.
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        Logger $logger, Config $config, Environment $template, PageRepository $repository
    ) {
        parent::__construct($logger, $config, $template);
        $this->repository = $repository;
    }

    /**
     * Parses data for provided merchantId
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request request
     * @param \Psr\Http\Message\ResponseInterface $response response
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        try {
            $page = $this->repository->getBySlug($request->getAttribute('slug'));
        } catch (\Exception $e) {
            // @TODO render 404 template
            echo '404';
            $page = false;
        }


        return $this->respond('page', ['page' => $page]);
    }
}