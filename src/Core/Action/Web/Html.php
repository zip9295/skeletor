<?php
namespace Skeletor\Core\Action\Web;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;

abstract class Html
{
    /**
     * @var Engine
     */
    protected $template;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Response
     */
    public $response;

    /**
     * @var Config
     */
    private $config;

    /**
     * ParseReportAction constructor.
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        Logger $logger, Config $config, Engine $template
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->template = $template;
        $this->response = new Response();
        $this->setGlobalVariable('cssPath', '');
        $this->setGlobalVariable('jsPath', '');
        if ($this->getConfig()->compileAssets) {
            $this->setGlobalVariable('cssPath', '/assets/front/cache/base.css?v=' . $this->getConfig()->gitLog);
            $this->setGlobalVariable('jsPath', '/assets/front/cache/base.js?v=' . $this->getConfig()->gitLog);
        }
    }

    public function respond($template, $data = []) : Response
    {
        $data = array_merge($data, [
            'webpSupport' => (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') >= 0),
        ]);
        if ($this->template->exists($template)) {
            $this->setGlobalVariable('template', $template);
            $html = $this->template->render($template, ['data' => $data]);
        } else {
            $tpl = 'defaultTheme::' . $template;
            if (strpos($template, 'view')) {
                $tpl = 'partialsGlobalDefault::view';
            }
            $this->setGlobalVariable('template', $tpl);
            $html = $this->template->render($tpl, ['data' => $data]);
        }
        $this->response->getBody()->write($html);
        $this->response->getBody()->rewind();

        return $this->response;
    }

    public function setGlobalVariable($name, $value)
    {
        $this->template->addData([$name => $value]);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function redirect($url): Response
    {
        if (!strstr($url, 'http')) {
            $url = $this->config->offsetGet('baseUrl') . $url;
        }

        return $this->response->withStatus(302)->withHeader('Location', $url);
    }
}