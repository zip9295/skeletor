<?php

namespace Skeletor\Core\Middleware;

use Skeletor\Core\Config\Config;
use Laminas\Session\ManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Skeletor\Core\Acl\Acl;
use Skeletor\Core\Acl\AclInterface;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Security\Authorization\AuthorizationService;
use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Login\Repository\LoginRepositoryInterface;
use Tamtamchik\SimpleFlash\Flash;

class AuthMiddleware implements \Skeletor\Core\Middleware\MiddlewareInterface
{
    private string $baseUrl;
    
    public function __construct(
        // The interface, not SessionManager: this only ever reaches for getStorage(),
        // and the container binds the manager against ManagerInterface anyway.
        private ManagerInterface $sessionManager,
        private Config $config,
        private Flash $flash,
        private AclInterface $acl,
        private LoginRepositoryInterface|EntityRegistry $repository,
        private AuthorizationService $authorizationService,
        private AuthPolicy $policy,
        private bool $useVoters = true
    ) {
        if (getenv('APPLICATION') === 'frontend') {
            $this->baseUrl = $this->config->baseUrl;
        } else {
            $this->baseUrl = $this->config->adminUrl ?? $this->config->baseUrl ?? '';
        }
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?callable $next = null
    )
    {
        $path = $request->getUri()->getPath();
        //skip pages that do not require auth
        if ($this->acl->isGuestPath($path)) {
            return $next($request, $response);
        }

        $loggedInEntityId = $this->sessionManager->getStorage()->offsetGet('loggedIn');

        if (!$loggedInEntityId) {
            $this->flash::error($this->acl->getMessage(Acl::MSG_NOT_LOGGED_IN));

            return $response->withStatus(302)->withHeader('Location', $this->loginUrl());
        }

        // Support for EntityRegistry (multi-entity) or fallback to single repository
        if ($this->repository instanceof EntityRegistry) {
            // Get entity type from session (defaults to 'user' for backward compatibility)
            $entityType = $this->sessionManager->getStorage()->offsetGet('loggedInEntityType') ?? 'user';

            try {
                $entity = $this->repository->getRepository($entityType)->getById($loggedInEntityId);
            } catch (\Exception) {
                $entity = null;
            }

            // An unknown entity type throws; a known one whose row has been deleted just
            // returns null. Both mean the same thing -- the session names an account that
            // cannot be loaded -- but only the throwing half used to be handled, so a deleted
            // account fell through to canAccessPath(null) and TypeErrored on every request.
            if (!$entity instanceof AuthenticatableInterface) {
                // Keep the entity type only while it is still registered: a session naming a
                // type the app no longer has would otherwise build /login/educator/..., which
                // the login controller answers with a 404, and the visitor could never return.
                $url = $this->loginUrl($this->repository->has($entityType) ? $entityType : null);
                $this->sessionManager->getStorage()->clear();
                $this->flash::error('Invalid session. Please log in again.');

                return $response->withStatus(302)->withHeader('Location', $url);
            }
        } else {
            // Legacy: single repository mode
            $entity = $this->repository->getById($loggedInEntityId);
        }

        // Use voter-based authorization if enabled, otherwise fall back to path-based ACL
        $hasAccess = $this->useVoters && $this->authorizationService
            ? $this->authorizationService->canAccessPath($path, $entity)
            : $this->acl->canAccess($entity, $path);

        if (!$hasAccess) {
            $url = sprintf('%s/%s', $this->baseUrl, ltrim($entity->getRedirectPath(), '/'));
            $this->flash::error(sprintf($this->acl->getMessage(Acl::MSG_NO_PERMISSIONS), $path));

            return $response->withStatus(302)->withHeader('Location', $url);
        }

        return $next($request, $response);
    }

    /**
     * Where to send someone who has to log in.
     *
     * The path comes from AuthPolicy, which derives it from the enabled login methods, so
     * there is no second list of URLs to keep in step with them. The old loginUrl/loginUrls
     * config was exactly that second list: it had to be corrected by hand whenever a method
     * was switched on or off, and could quietly point at a form the app no longer serves.
     *
     * Still per entity type, because a delegate bounced to the staff login form gets a page
     * they can never get through - but the entity type now only picks the route segment,
     * not a separately configured destination.
     */
    private function loginUrl(?string $entityType = null): string
    {
        $path = $this->policy->loginPath($entityType ?? 'user');

        $adminPath = trim((string) ($this->config->adminPath ?? ''), '/');
        if ($adminPath === '') {
            return sprintf('%s%s', $this->baseUrl, $path);
        }

        return sprintf('%s/%s%s', $this->baseUrl, $adminPath, $path);
    }
}
