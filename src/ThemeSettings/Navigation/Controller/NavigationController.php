<?php

namespace Skeletor\ThemeSettings\Navigation\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\ManagerInterface;
use Skeletor\Core\Controller\Controller;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\ThemeSettings\Navigation\Service\Navigation;
use Tamtamchik\SimpleFlash\Flash;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;

class NavigationController extends Controller
{
    public function __construct(
        // Not re-promoted. Controller already promotes all four as protected, and a
        // redeclaration has to match the parent's type exactly -- which is a fatal at
        // class-load time the moment one of those types changes upstream.
        Engine $template,
        Logger $logger,
        Config $config,
        ManagerInterface $session,
        Flash $flash,
        protected Navigation $navigationService
    ) {
        parent::__construct($template, $config, $session, $flash, $logger);
    }

    public function view(): \GuzzleHttp\Psr7\Response
    {
        $this->setGlobalVariable('pageTitle', 'Theme Settings');
        $navigations = $this->navigationService->getEntities();
        return $this->respond('view', [
            'navigations' => $navigations
        ]);
    }

    public function create(): \Psr\Http\Message\MessageInterface
    {
        $errors = [];
        $status = false;
        $message = '';
        $data = json_decode($this->getRequest()->getBody(), true);
        if(isset($data['name'])) {
            try {
                $nav = $this->navigationService->create([
                    'label' => $data['name'],
                ]);
                $status = true;
                $message = $this->translate('Navigation created successfully');
            } catch (InvalidFormTokenException $e) {
                $message = $this->translate('Access denied. Please refresh the page and try again.');
                $errors[] = $e->getMessage();
                $status = false;
            }
            catch (\Throwable $e) {
                $message = $this->translate('An error occurred while creating the navigation');
                $errors[] = $e->getMessage();
                $status = false;
            }
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => [],
            'id' => (isset($nav) && $nav) ? $nav->id : null,
            'status' => $status,
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function delete(): \Psr\Http\Message\MessageInterface
    {
        $errors = [];
        $status = false;
        $message = '';
        $data = json_decode($this->getRequest()->getBody(), true);
        if(isset($data['id'])) {
            try {
                $this->navigationService->delete($data['id']);
                $status = true;
                $message = $this->translate('Navigation deleted successfully');
            } catch (\Throwable $e) {
                $message = $this->translate('An error occurred while deleting the navigation');
                $errors[] = $e->getMessage();
                $status = false;
            }
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => [],
            'status' => $status,
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function get()
    {
        $errors = [];
        $items = [];
        $id = $this->getRequest()->getAttribute('id');
        try {
            $navigation = $this->navigationService->getById($id);
            if($navigation) {
                $items = $navigation->getItemsFormatted();
            }
            $status = true;
            $message = $this->translate('Navigation retrieved successfully');
        } catch (\Throwable $e) {
            $message = $this->translate('An error occurred while retrieving the navigation');
            $errors[] = $e->getMessage();
            $status = false;
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => [],
            'status' => $status,
            'items' => $items,
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }


    public function save()
    {
        $errors = [];
        $status = false;
        $message = '';
        $data = $this->getRequest()->getParsedBody();
        if(isset($data['navigationId'])) {
            try {
                $this->navigationService->save($data['navigationId'], $data['navigation'] ?? []);
                $status = true;
                $message = $this->translate('Navigation saved successfully');
            } catch (\Throwable $e) {
                $message = $this->translate('An error occurred while saving the navigation');
                $errors[] = $e->getMessage();
                $status = false;
            }
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => [],
            'status' => $status,
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }


}