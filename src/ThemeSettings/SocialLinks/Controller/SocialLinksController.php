<?php

namespace Skeletor\ThemeSettings\SocialLinks\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\ManagerInterface;
use League\Plates\Engine;
use Skeletor\Core\Controller\Controller;
use Skeletor\ThemeSettings\SocialLinks\Service\SocialLinks;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class SocialLinksController extends Controller
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
        protected SocialLinks $socialLinksService,
    ) {
        parent::__construct($template, $config, $session, $flash, $logger);
    }

    public function view(): \GuzzleHttp\Psr7\Response
    {
        $this->setGlobalVariable('pageTitle', 'Theme Settings');

        return $this->respond('view', [
            'socialLinks' => $this->getSocialLinksWithPlatformAsKey($this->socialLinksService->getEntities())
        ]);
    }

    public function getSocialLinksWithPlatformAsKey(array $socialLinks): array
    {
        $formattedSocialLinks = [];
        foreach ($socialLinks as $socialLink) {
            if($socialLink instanceof \Skeletor\ThemeSettings\SocialLinks\Entity\SocialLinks) {
                $formattedSocialLinks[$socialLink->platform] = $socialLink;
            }
        }
        return $formattedSocialLinks;
    }

    public function save(): \Psr\Http\Message\MessageInterface
    {
        $errors = [];
        $data = $this->getRequest()->getParsedBody();
        try {
            foreach ($data as $platform) {
                $socialLink = $this->socialLinksService->getEntityByPlatform($platform['platform']);
                if($socialLink) {
                    if(trim($platform['url']) === '') {
                        $this->socialLinksService->delete($socialLink->id);
                        continue;
                    }
                    $this->socialLinksService->updateField('url', $platform['url'], $socialLink->id);
                    $this->socialLinksService->updateField('position', (int)$platform['position'], $socialLink->id);
                } else {
                    if(trim($platform['url']) === '') {
                        continue;
                    }
                    $this->socialLinksService->create([
                        'platform' => $platform['platform'],
                        'url' => $platform['url'],
                        'position' => $platform['position']
                    ]);
                }
            }
            $status = true;
            $message = $this->translate('Social links saved successfully');
        } catch (\Throwable $e) {
            $message = $this->translate('An error occurred while saving the social links');
            $errors[] = $e->getMessage();
            $status = false;
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