<?php
namespace Skeletor\Visitor\Action;

use Skeletor\Core\Config\Config;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Visitor\Filter\Login as Filter;
use Skeletor\Visitor\Service\Login as LoginService;
use Tamtamchik\SimpleFlash\Flash;

class Login extends Html
{
    const LOGIN_FORM_PATH = '/login/loginForm';
    const REDIRECT_PATH = '/my-profile';

    public function __construct(
        private Filter $filter, private LoginService $login, private Flash $flash, Logger $logger, Config $config,
        Engine $template
    ) {
        parent::__construct($logger, $config, $template);
    }

    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        try {
            $data = $this->filter->filter($request);
        } catch (ValidatorException $e) {
            foreach ($this->filter->getErrors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->flash->error($message);
                }
            }
            return $this->redirect(static::LOGIN_FORM_PATH);
        } catch (\Exception $e) {
            $this->flash->error($e->getMessage());
            return $this->redirect(static::LOGIN_FORM_PATH);
        }
        if (!$this->login->login($data)) {
            $this->flash->error($this->login->getMessages()[0]);
            return $this->redirect(static::LOGIN_FORM_PATH);
        }
        $this->flash->success($this->login->getMessages()[0]);

//        return $this->redirect($this->getSession()->getStorage()->offsetGet('redirectPath'));
        return $this->redirect(static::REDIRECT_PATH);
    }
}