<?php
namespace Skeletor\Visitor\Action;

use Skeletor\Core\Config\Config;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Visitor\Filter\Visitor as Filter;
use Skeletor\Visitor\Repository\VisitorRepositoryInterface;
use Tamtamchik\SimpleFlash\Flash;

class Register extends Html
{
    const FORM_PATH = '/login/registerForm';

    public function __construct(
        private Filter $filter, private VisitorRepositoryInterface $visitor, private Flash $flash, Logger $logger, Config $config,
        Engine $template
    ) {
        parent::__construct($logger, $config, $template);
    }

    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        try {
            $data = $this->filter->filter($request->getParsedBody());
            $this->visitor->create($data);
        } catch (ValidatorException $e) {
            foreach ($this->filter->getErrors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->flash->error($message);
                }
            }
            return $this->redirect(static::FORM_PATH);
        } catch (\Exception $e) {
            $this->flash->error($e->getMessage());
            return $this->redirect(static::FORM_PATH);
        }
        $this->flash->success('You have registered successfully, you may now login.');

//        return $this->redirect($this->getSession()->getStorage()->offsetGet('redirectPath'));
        return $this->redirect('/login/loginForm');
    }
}