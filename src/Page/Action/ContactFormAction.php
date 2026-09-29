<?php
namespace Skeletor\Page\Action;

use Skeletor\Core\Config\Config;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Twig\Environment;

class ContactFormAction extends Html
{
    /**
     * PageAction constructor.
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        Logger $logger, Config $config, Environment $template
    ) {
        parent::__construct($logger, $config, $template);
        $this->setGlobalVariable('pageTitle', 'Kontakt');
        if ($this->getConfig()->compileAssets) {
            $this->setGlobalVariable('jsPath', '/assets/front/cache/contact.js?v=1');
        }
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
        $data = ['captchaSiteKey' => $this->getConfig()->captcha];
        if (isset($request->getQueryParams()['sent']) && $request->getQueryParams()['sent']) {
            $data['success'] = 'Vaša poruka je uspešno poslata.';
        }
        return $this->respond('page/contact', $data);
    }


}