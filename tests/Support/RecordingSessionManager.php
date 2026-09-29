<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Laminas\Session\SessionManager;
use Laminas\Session\Storage\ArrayStorage;

/**
 * A session manager backed by array storage that remembers what was asked of it.
 *
 * The login flow does three things to the session that have to be asserted rather than
 * assumed: it regenerates the id (without which a fixated id survives the login and the
 * session is stealable), it may set a remember-me cookie, and logout must destroy it. A
 * plain stub returns null for all three and every one of those tests passes vacuously.
 *
 * Extends the real class rather than implementing ManagerInterface so the signatures cannot
 * drift, and deliberately does not call the parent constructor: that would build a real
 * session config and try to touch PHP's session machinery.
 */
class RecordingSessionManager extends SessionManager
{
    public bool $idRegenerated = false;

    public bool $remembered = false;

    public bool $forgotten = false;

    public bool $destroyed = false;

    public bool $started = false;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(private ArrayStorage $arrayStorage = new ArrayStorage())
    {
    }

    public function getStorage()
    {
        return $this->arrayStorage;
    }

    public function start($preserveStorage = false)
    {
        $this->started = true;
    }

    public function regenerateId($deleteOldSession = true)
    {
        $this->idRegenerated = true;

        return $this;
    }

    public function rememberMe($ttl = null)
    {
        $this->remembered = true;
    }

    public function forgetMe()
    {
        $this->forgotten = true;
    }

    public function destroy(?array $options = null)
    {
        $this->destroyed = true;

        return $this;
    }

    public function getId()
    {
        return 'test-session-id';
    }

    /** Everything the session holds, for assertions that want the whole picture. */
    public function toArray(): array
    {
        return $this->arrayStorage->toArray();
    }
}
