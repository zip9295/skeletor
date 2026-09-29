<?php
namespace Skeletor\Core\Controller;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Skeletor\Core\Config\Config;
use League\Plates\Engine;

class FrontController
{
    /**
     * @var Engine
     */
    private $template;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Engine $template,
        Config $config
    ) {
        $this->template = $template;
        $this->config = $config;
        $this->response = new Response();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable|null $next
     * @return mixed
     * @throws \Exception
     */
    public function __invoke(Request $request, Response $response, ?callable $next = null)
    {
        if (!$request->getAttribute('action')) {
            throw new \Exception('Action attribute is not set.');
        }
        $this->request = $request;
        $this->response = $response;

        if (!method_exists($this, $request->getAttribute('action'))) {
            throw new \Exception(sprintf('Method %s does not exist', $request->getAttribute('action')));
        }
        $response = $this->{$request->getAttribute('action')}();

        return $response;
    }

    public function setGlobalVariable($name, $value)
    {
        $this->template->addGlobal($name, $value);
    }

    /**
     * Return response to client.
     *
     * @param $template
     * @param array $data
     *
     * @return Response
     */
    public function respond($template, $data = []) : Response
    {
        $className = explode('\\', get_class($this));
        $controller = strtolower(str_replace('Controller', '', $className[count($className) - 1]));
        $template = sprintf('%s/%s.twig', $controller, $template);
        try {
            $this->response->getBody()->write($this->template->render($template, ['data' => $data]));
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
        $this->response->getBody()->rewind();

        return $this->response;
    }

    public function respondPartial($template, $data = []) : Response
    {
        $template = sprintf('partials/%s.twig', $template);
        $this->response->getBody()->write($this->template->render($template, ['data' => $data]));
        $this->response->getBody()->rewind();

        return $this->response;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Redirect client to given uri.
     *
     * @param $url
     *
     * @return Response
     */
    public function redirect($url): Response
    {
        if (!strstr($url, 'http')) {
            $url = $this->config->offsetGet('baseUrl') . $url;
        }

        return $this->response->withStatus(302)->withHeader('Location', $url);
    }
}
