<?php
namespace Skeletor\Core\Controller;

use Psr\Log\LoggerInterface;
use Skeletor\Core\Security\Csrf;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Skeletor\Core\Config\Config;
use Laminas\Session\ManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Tamtamchik\SimpleFlash\Flash;
use League\Plates\Engine;

class Controller
{
    private $request;

    private ResponseInterface $response;

    private ?Csrf $csrf = null;

    public string $protectedPath = 'admin';

    protected array $refererQueryParams = [];

    public function __construct(
        protected Engine $template,
        protected Config $config,
        protected ManagerInterface $session,
        protected Flash $flash,
        protected LoggerInterface $logger
    ) {
        $this->response = new Response();
        // Cast: the property is typed now, and Config::offsetGet() returns null for a key
        // that is not set — so an app without adminPath in its config would TypeError on
        // every controller it constructs rather than simply having no admin prefix.
        $this->protectedPath = (string) $config->offsetGet('adminPath');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable|null $next
     * @return mixed
     * @throws \Exception
     */
    public function __invoke(Request $request, Response $response, ?callable $next = null): mixed
    {
        if (!$request->getAttribute('action')) {
            throw new \Exception('Action attribute is not set.');
        }
        $this->request = $request;
        $this->response = $response;
        if (!method_exists($this, $request->getAttribute('action'))) {
            throw new \Exception(sprintf('Method %s does not exist', $request->getAttribute('action')));
        }
        if(isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            $urlComponents = parse_url($referer);
            if (isset($urlComponents['query'])) {
                parse_str($urlComponents['query'], $queryParams);
                $this->refererQueryParams = $queryParams;
            }
        }

        return $this->{$request->getAttribute('action')}();
    }

    /**
     * @param $name
     * @param $value
     * @return void
     */
    public function setGlobalVariable($name, $value): void
    {
        $this->template->addData([$name => $value]);
    }

    /**
     * Set global twig variables.
     */
    protected function setGlobalVariables(): void
    {
        if ($this->getSession()->getStorage()->offsetGet('loggedIn')) {
            $this->setGlobalVariable('loggedInEmail', $this->getSession()->getStorage()->offsetGet('loggedInEmail'));
            $this->setGlobalVariable('loggedInFirstName', $this->getSession()->getStorage()->offsetGet('loggedInFirstName'));
            $this->setGlobalVariable('loggedInLastName', $this->getSession()->getStorage()->offsetGet('loggedInLastName'));
            $this->setGlobalVariable('loggedIn', $this->getSession()->getStorage()->offsetGet('loggedIn'));
            $this->setGlobalVariable('loggedInRole', $this->getSession()->getStorage()->offsetGet('loggedInRole'));
            $this->setGlobalVariable('loggedInEntityType', $this->getSession()->getStorage()->offsetGet('loggedInEntityType'));
            $this->setGlobalVariable('tenantId', $this->getSession()->getStorage()->offsetGet('tenantId'));
        }
        $this->setGlobalVariable('messages', $this->flash::display());
        $this->setGlobalVariable('route', $this->getRequest()->getUri()->getPath());
        $this->setGlobalVariable('protectedPath', $this->protectedPath);
        $this->setGlobalVariable('appName', $this->getConfig()->offsetGet('appName'));
        $this->setGlobalVariable('environment', getenv('APPLICATION_ENV'));
    }

    /**
     * Return response to client.
     *
     * @TODO solve theme resolving better
     *
     * @param $template
     * @param array $data
     *
     * @return Response
     */
    public function respond($template, $data = []) : Response
    {
        $this->setGlobalVariables();
        $className = explode('\\', get_class($this));
        $controller = strtolower(str_replace('Controller', '', $className[count($className) - 1]));
        $template = sprintf('%s/%s', $controller, $template);
        try {
            if ($this->template->exists($template)) {
                $html = $this->template->render($template, ['data' => $data]);
            } else {
                $tpl = 'defaultTheme::' . $template;
                if (strpos($template, 'view')) {
                    $tpl = 'partialsGlobalDefault::view';
                }
                $html = $this->template->render($tpl, ['data' => $data]);
            }
            $this->response->getBody()->write($html);
            $this->response->getBody()->rewind();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $output = "An error has occurred.";
            if(getenv('APPLICATION_ENV') !== 'production') {
                $output = $e->getMessage();
            }
            $this->response->getBody()->write($output);
            $this->response->getBody()->rewind();
        }

        return $this->response;
    }


    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * The session manager.
     *
     * Typed as the interface, not SessionManager. Everything the framework asks of it --
     * getStorage(), regenerateId(), rememberMe(), forgetMe(), destroy() -- is declared on
     * ManagerInterface, and requiring the concrete class made every controller that correctly
     * type-hinted the interface a static mismatch on the way to parent::__construct().
     */
    public function getSession() : ManagerInterface
    {
        return $this->session;
    }

    /**
     * CSRF service, built from the session this controller already holds.
     *
     * Exists so controllers can issue tokens without threading another constructor argument
     * through all 18 AjaxCrudController subclasses. Cached for the life of the controller.
     */
    protected function csrf() : Csrf
    {
        return $this->csrf ??= new Csrf($this->session);
    }

    public function getFlash() : Flash
    {
        return $this->flash;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function redirect($url): ResponseInterface
    {
        // Cast $url up front: callers routinely pass a config value or a session key that can
        // be null (a redirectPath that was never set), and str_replace()/strlen() on null is an
        // E_DEPRECATED in PHP 8.1+ — noise in the log for what is really "redirect home".
        // $protectedPath needs no guard: it is a typed string property assigned in the
        // constructor.
        $url = (string) $url;

        if (!strlen($this->protectedPath)) {
            $url = str_replace('admin/', '', $url);
        } else {
            $url = str_replace('admin/', sprintf('%s/', $this->protectedPath), $url);
        }
        if (!strstr($url, 'http')) {
            if(getenv('APPLICATION') === 'backend') {
                $url = $this->config->offsetGet('adminUrl') . $url;
            }
            if (getenv('APPLICATION') === 'frontend') {
                $url = $this->config->offsetGet('baseUrl') . $url;
            }
        }
        return $this->response->withStatus(302)->withHeader('Location', $url);
    }

    public function translate($string)
    {
        return $this->template->make('t')->t($string);
    }
}
