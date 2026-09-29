<?php

namespace Skeletor\Visitor\Controller;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\Controller;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Visitor\Filter\Login as Filter;
use Skeletor\Visitor\Service\Login;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class LoginController extends Controller
{
    const LOGGED_OUT = 'You have successfully signed out.';
    const LOGIN_FORM_PATH = '/login/loginForm/';

    /**
     * LoginController constructor.
     * @param Login $loginService
     * @param Session $session
     * @param Config $config
     * @param Flash $flash
     * @param Engine $template
     * @param Logger $logger
     * @param Filter $loginFilter
     */
    public function __construct(
        private Login $loginService, private Filter $loginFilter, Session $session, Config $config, Flash $flash, Engine $template,
        Logger $logger,
    ) {
        parent::__construct($template, $config, $session, $flash, $logger);
    }

    /**
     * @return Response
     */
    public function loginForm(): Response
    {
        if ($this->getSession()->getStorage()->offsetGet('loggedIn')) {
            return $this->redirect($this->getSession()->getStorage()->offsetGet('redirectPath'));
        }
        $this->setGlobalVariable('pageTitle', 'Login');

        return $this->respond('loginForm');
    }

    /**
     * Login action.
     *
     * @return Response
     */
    public function login(): Response
    {
        try {
            $data = $this->loginFilter->filter($this->getRequest());
        } catch (ValidatorException $e) {
            foreach ($this->loginFilter->getErrors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->getFlash()->error($message);
                }
            }
            return $this->redirect(static::LOGIN_FORM_PATH);
        } catch (\Exception $e) {
            $this->getFlash()->error($e->getMessage());
            return $this->redirect(static::LOGIN_FORM_PATH);
        }
        if (!$this->loginService->login($data)) {
            $this->getFlash()->error($this->loginService->getMessages()[0]);
            return $this->redirect(static::LOGIN_FORM_PATH);
        }
        $this->getFlash()->success($this->loginService->getMessages()[0]);

        return $this->redirect($this->getSession()->getStorage()->offsetGet('redirectPath'));
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function logOut(): Response
    {
        $this->getSession()->getStorage()->offsetUnset('loggedIn');
        $this->getSession()->getStorage()->offsetUnset('loggedInRole');
        $this->getSession()->getStorage()->offsetUnset('redirectPath');
        $this->getSession()->getStorage()->offsetUnset('loggedInEmail');
        $this->getSession()->forgetMe();
        $this->getFlash()->success(self::LOGGED_OUT);

        return $this->redirect(static::LOGIN_FORM_PATH);
    }

}