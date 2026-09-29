<?php

namespace Skeletor\ThemeSettings\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\ManagerInterface;
use Tamtamchik\SimpleFlash\Flash;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;

class ThemeSettingsController extends \Skeletor\Core\Controller\Controller
{
    public function __construct(
        // Not re-promoted. Controller already promotes all four as protected, and a
        // redeclaration has to match the parent's type exactly -- which is a fatal at
        // class-load time the moment one of those types changes upstream.
        Engine $template,
        Logger $logger,
        Config $config,
        ManagerInterface $session,
        Flash $flash
    ) {
        parent::__construct($template, $config, $session, $flash, $logger);
    }

    public function view(): \GuzzleHttp\Psr7\Response
    {
        $this->setGlobalVariable('pageTitle', 'Theme Settings');
        return $this->respond('view', [
            'pageTitle' => 'Theme Settings'
        ]);
    }
}