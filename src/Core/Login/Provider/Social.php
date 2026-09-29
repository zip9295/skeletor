<?php
namespace Skeletor\Core\Login\Provider;

use Laminas\Session\ManagerInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use Monolog\Logger;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Login\Repository\LoginRepositoryInterface;
use Skeletor\Core\Security\Csrf;

class Social implements ProviderInterface
{
    const TYPE_LINKEDIN = 1;
    const TYPE_FACEBOOK = 2;
    const TYPE_TWITTER = 3;
    const TYPE_GOOGLE = 4;
    const TYPE_INSTAGRAM = 5;

    public function __construct(
        private AbstractProvider $provider, private ManagerInterface $session, private LoginRepositoryInterface $loginRepo,
        private Logger $logger
    ) {}
    public function login($requestData)
    {
//        if (empty($requestData['state']) || ($requestData['state'] !== $this->session->getStorage()->offsetGet('SSO_STATE'))) {
//            $this->session->getStorage()->offsetUnset('SSO_STATE');
//            exit('Invalid state');
//        }
        $remoteType = match ($_GET['type']) {
            'LinkedIn' => self::TYPE_LINKEDIN,
            'Facebook' => self::TYPE_FACEBOOK,
            'Twitter' => self::TYPE_TWITTER,
            'Google' => self::TYPE_GOOGLE,
        };
        try {
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $requestData['code']
            ]);
            $this->session->getStorage()->offsetSet('SSO_STATE', $requestData['state']);
            $userData = $this->provider->getResourceOwner($token);
            $model = $this->loginRepo->getByEmail($userData->getEmail());
        } catch (NotFoundException $e) {
            $model = $this->loginRepo->create([
                'firstName' => $userData->getFirstName(),
                'lastName' => $userData->getLastName(),
                'email' => $userData->getEmail(),
                'isActive' => 1,
                'remoteType' => $remoteType,
                'id' => 0,
                'password' => ''
            ]);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            throw $e;
        }

        return $model;
    }
    public function getSsoState()
    {
        return $this->provider->getState();
    }
    public function getAuthorizationUrl()
    {
        return $this->provider->getAuthorizationUrl();
    }
}