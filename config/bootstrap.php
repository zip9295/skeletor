<?php

use Skeletor\Core\Security\Csrf;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Skeletor\Core\Config\Config;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\ManagerInterface;
use Laminas\Session\SessionManager;
use League\Event\EventDispatcher;
use League\Plates\Engine;
use Monolog\ErrorHandler;
use Skeletor\Core\Error\BootstrapErrorHandler;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Acl\Acl;
use Skeletor\Core\Action\Web\NotFound;
use Skeletor\Core\Action\Web\NotFoundInterface;
use Skeletor\Core\Middleware\AuthMiddleware;
use Skeletor\Translator\Service\Translator;
use Skeletor\User\Factory\UserFactory;
use Skeletor\User\Model\UserFactoryInterface;
use Skeletor\User\Repository\UserRepository;
use Skeletor\User\Repository\UserRepositoryInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Tamtamchik\SimpleFlash\Flash;
// Aliased rather than imported per-class: several block filters (Image, Table, Embed,
// Gallery) share names with classes already imported above.
use Skeletor\ContentEditor\BlockFilters;

$containerBuilder = new \DI\ContainerBuilder;
/* @var \DI\Container $container */
$container = $containerBuilder->build();

//@TODO setup caching
$container->set(Dispatcher::class, function() {
    $routeList = require __DIR__.'/../config/routes.php';

    /** @var Dispatcher $dispatcher */
    return FastRoute\simpleDispatcher(
        function (RouteCollector $r) use ($routeList) {
            foreach ($routeList as $routeDef) {
                $r->addRoute($routeDef[0], $routeDef[1], $routeDef[2]);
            }
        }
    );
});

$container->set(Acl::class, function() use ($container) {
    return new Acl(
        $container->get(ManagerInterface::class),
        $container->get(Config::class),
        require APP_PATH . '/config/acl.php',
        require APP_PATH . '/config/aclMessages.php',
    );
});
$container->set(Config::class, function() {
    $params = include(APP_PATH . "/config/config.php");
    $config = new \Skeletor\Core\Config\Config($params);
    $config = $config->merge(new \Skeletor\Core\Config\Config(include(APP_PATH . "/config/config-local.php")));

    return $config;
});
$container->set(Flash::class, function () use ($container) {
    //session needs to be started for flash
    $container->get(ManagerInterface::class);
    $flash = new Flash();
    $flash->setTemplate(new \Skeletor\Flash\Template\SkeletorTemplate());
    return $flash;
});
$container->set(ManagerInterface::class, function() {
    $sessionConfig = new SessionConfig();
    $sessionConfig->setOptions([
        'remember_me_seconds' => 2592000, //2592000, // 30 * 24 * 60 * 60 = 30 days
        'use_cookies'         => true,
        'cookie_httponly'     => true,
        'name'                => 'wellsite',
    ]);

    $session = new SessionManager($sessionConfig);
    $session->start();

    return $session;
});
$container->set(SessionManager::class, function() use ($container) {
    return $container->get(ManagerInterface::class);
});

$container->set(Logger::class, function() use ($container) {
    $logger = new \Monolog\Logger('its');
    $date = $container->get(\DateTime::class);
    $logDir = DATA_PATH . '/logs/';
    $logSubDir = $logDir . $date->format('Y') . '-' . $date->format('m');
    $logFile = $logSubDir . '/' . gethostname() . '-backend-' . $date->format('d') . '.log';
    $debugLog = DATA_PATH . '/logs/'. gethostname() . '-backend-debug.log';
    // create dir or file if needed
    if (!is_dir($logDir)) {
        mkdir($logDir);
    }
    if (!is_dir($logSubDir)) {
        mkdir($logSubDir);
    }
    if (!is_file($logFile)) {
        touch($logFile);
    }

    $logger->pushHandler(
        new StreamHandler($debugLog,\Monolog\Level::Info)
//        new Monolog\Handler\RotatingFileHandler($debugLog, 10,\Monolog\Level::Debug)
    );

    $logger->pushHandler(
        new StreamHandler($logFile, \Monolog\Level::Error, false)
    );
    $env = strtolower(getenv('APPLICATION_ENV'));
//    if ($env && strtolower($env) === 'production') {
//        $mailHandler = new GF_Mail_Log_Handler(\Monolog\Logger::ERROR, true);
//        $mailHandler->setMail($container->get(\WCP\Memo\Product\Mailer::class));
//        $logger->pushHandler($mailHandler);
//    }

    if ($env !== 'production') {
        $logger->pushHandler(new BrowserConsoleHandler());
    }
    ErrorHandler::register($logger);
    // Monolog now owns error handling; stand the boot-phase fallback down so
    // fatals are not logged twice.
    BootstrapErrorHandler::release();

    return $logger;
});
$container->set(Engine::class, function() use ($container) {
    $defaultTheme = APP_PATH . '/themes/admin';
    $theme = APP_PATH . '/themes/admin';
    $plates = new Engine($theme);
    $plates->addFolder('defaultTheme', $defaultTheme, true);
    $plates->addFolder('layout', APP_PATH . '/themes/admin/layout');
    $plates->addFolder('partialsGlobal', APP_PATH . '/themes/admin/partials/global');
    $plates->addFolder('partialsGlobalDefault', $defaultTheme . '/partials/global');
    $plates->registerFunction('printError', function($error, $label) use($plates) {
        return $plates->render('partialsGlobal::error', ['error' => $error, 'label' => $label]);
    });
    $plates->registerFunction('formToken', function () use ($container) { return $container->get(Csrf::class)->getHiddenInputString(); });
    $plates->registerFunction('formTokenArray', function () use ($container) { return $container->get(Csrf::class)->getTokenAsArray(); });
    $plates->registerFunction('getVersionPathPrefix', function() use($container) {
        /*
         * @ is used so that browser requests a new folder path for the assets when the version string bumps but the
         * web server returns the correct asset, so when the version string is bumped to 0.0.2, the url that the
         * browser fetches will be /@0.0.2/assets/some-asset.... while the webserver is configured to resolve the asset
         * without the version, in hand busting the cache as the browser thinks it's a new resource
         * */
        $versionString = $container->get(Config::class)->offsetGet('versionString');
        if($versionString) {
            return '/@' . $versionString;
        }
        return '';
    });
    $translator = $container->get(Translator::class);
    $session = $container->get(ManagerInterface::class);
    $selectedLanguage = $session->getStorage()->offsetGet('selectedLanguage') ?? 'en-us';
    $translator($selectedLanguage);
    $plates->loadExtension($translator);

    // Make language data available in all templates
    $plates->addData([
        'currentLanguage' => $translator->getLanguage(),
        'availableLanguages' => $translator->getAvailableLanguages(),
    ]);

    return $plates;
});
$container->set(DateTime::class, function() use ($container) {
    return new \DateTime('now', new DateTimeZone('Europe/Belgrade'));
});
$container->set(UserRepositoryInterface::class, function() use ($container) {
    return $container->get(UserRepository::class);
});

$container->set(UserFactoryInterface::class, function() use ($container) {
    return new UserFactory();
});

$container->set(EventDispatcher::class, function() use ($container) {
    $dispatcher = new EventDispatcher();
    // 'listeners' is optional: no app config defines it yet, and calling ->toArray() on the
    // resulting null is a fatal that only hides because this factory is lazy.
    $listeners = $container->get(Config::class)->offsetGet('listeners');
    foreach (($listeners ? $listeners->toArray() : []) as $event => $listener) {
        if(is_array($listener)) {
            foreach($listener as $listenerArrayEntry) {
                $dispatcher->subscribeTo($event, $container->get($listenerArrayEntry));
            }
            continue;
        }
        $dispatcher->subscribeTo($event, $container->get($listener));
    }

    return $dispatcher;
});
$container->set(NotFoundInterface::class, function() use ($container) {
    return $container->get(NotFound::class);
});

$container->set(\Skeletor\Core\Security\Authorization\PermissionRegistry::class, function() {
    $config = require APP_PATH . '/config/permissions.php';
    return new \Skeletor\Core\Security\Authorization\PermissionRegistry($config);
});

$container->set(\Skeletor\Core\Security\Authorization\AuthorizationService::class, function() use ($container) {
    return new \Skeletor\Core\Security\Authorization\AuthorizationService(
        $container->get(\Skeletor\Core\Security\Authorization\PermissionRegistry::class),
        $container->get(Logger::class),
    );
});

$container->set(\Skeletor\Core\Activity\Service\Activity::class, function() use ($container) {
    return new \Skeletor\Core\Activity\Service\Activity(
        $container->get(\Skeletor\Core\Activity\Repository\ActivityRepository::class),
        $container->get(\Skeletor\User\Service\Session::class),
        $container->get(Logger::class),
    );
});

$container->set(\Skeletor\User\Service\User::class, function() use ($container) {
    return new \Skeletor\User\Service\User(
        $container->get(\Skeletor\User\Repository\UserRepository::class),
        $container->get(\Skeletor\User\Service\Session::class),
        $container->get(Logger::class),
        $container->get(\Skeletor\User\Filter\User::class),
        $container->get(\Skeletor\Core\Activity\Service\Activity::class),
        tenant: null,
    );
});

//$container->set(\Skeletor\Core\Activity\Controller\ActivityController::class, function() use ($container) {
//    return new \Skeletor\Core\Activity\Controller\ActivityController(
//        $container->get(\Skeletor\Core\Activity\Service\Activity::class),
//        $container->get(ManagerInterface::class),
//        $container->get(Config::class),
//        $container->get(Flash::class),
//        $container->get(Engine::class),
//    );
//});

$container->set(\Skeletor\Translator\Service\TranslatorCrudService::class, function() use ($container) {
    return new \Skeletor\Translator\Service\TranslatorCrudService(
        $container->get(\Skeletor\Translator\Repository\TranslationRepository::class),
        $container->get(\Skeletor\User\Service\Session::class),
        $container->get(Logger::class),
        $container->get(Translator::class),
        $container->get(\Skeletor\Translator\Service\TranslationFileExporter::class),
        $container->get(\Skeletor\Core\Activity\Service\Activity::class),
    );
});

$container->set(\Skeletor\Translator\Controller\TranslatorController::class, function() use ($container) {
    return new \Skeletor\Translator\Controller\TranslatorController(
        $container->get(\Skeletor\Translator\Service\TranslatorCrudService::class),
        $container->get(ManagerInterface::class),
        $container->get(Config::class),
        $container->get(Flash::class),
        $container->get(Engine::class),
        $container->get(Logger::class),
        $container->get(Translator::class)
    );
});

$container->set(\Skeletor\Core\Mailer\Service\MailerInterface::class, function() use ($container) {
    return new \Skeletor\Core\Mailer\Service\Mailer(
        $container->get(\PHPMailer\PHPMailer\PHPMailer::class),
        $container->get(Config::class),
        $container->get(Engine::class),
    );
});

$container->set(\Redis::class, function() use ($container) {
    $redis = new \Redis();
    $config = $container->get(Config::class);
    $host = $config->redis->host ?? '127.0.0.1';
    $port = $config->redis->port ?? 6379;
    $redis->connect($host, $port);
    return $redis;
});

$container->set(\PHPMailer\PHPMailer\PHPMailer::class, function() use ($container) {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $config = $container->get(Config::class);
    if (!empty($config->mailer->server->toArray())) {
        $mail->isSMTP();
        $mail->Host = $config->mailer->server->host ?? 'localhost';
        $mail->Port = $config->mailer->server->port ?? 587;
    }
    return $mail;
});

$container->set(\Skeletor\Core\Login\Provider\ProviderInterface::class, function() use ($container) {
    return new \Skeletor\Core\Login\Provider\DbProvider(
        $container->get(\Skeletor\User\Repository\UserRepository::class)
    );
});

$container->set(\Skeletor\Core\Login\Validator\ResetPasswordInterface::class, function() use ($container) {
    $strategy = $container->get(Config::class)->passwordValidation ?? 'loose';
    return match ($strategy) {
        'strict' => $container->get(\Skeletor\Core\Login\Validator\ResetPasswordStrict::class),
        default => $container->get(\Skeletor\Core\Login\Validator\ResetPasswordLoose::class),
    };
});


// ---- login -----------------------------------------------------------------------------
//
// AuthPolicy autowires off Config and is the single source of truth for which login methods
// this app has; everything below asks it rather than deciding for itself.

$container->set(\Skeletor\Core\Security\EntityRegistry::class, function() use ($container) {
    $registry = new \Skeletor\Core\Security\EntityRegistry();
    $registry->register(
        'user',
        \Skeletor\User\Entity\User::class,
        $container->get(UserRepositoryInterface::class)
    );

    return $registry;
});

$container->set(\Skeletor\Core\Security\Authenticator\AuthenticatorRegistry::class, function() use ($container) {
    $policy = $container->get(\Skeletor\Core\Security\AuthPolicy::class);

    return new \Skeletor\Core\Security\Authenticator\AuthenticatorRegistry(
        $policy,
        $container->get(\Skeletor\Core\Security\Authenticator\PasswordAuthenticator::class),
        $container->get(\Skeletor\Core\Security\Authenticator\MagicLinkAuthenticator::class),
        // SSO needs an OAuth provider, which only an app that uses it can supply. Resolved
        // lazily so an app with sso switched off never has to wire one.
        $policy->isEnabled(\Skeletor\Core\Security\AuthPolicy::METHOD_SSO)
            ? $container->get(\Skeletor\Core\Security\Authenticator\SsoAuthenticator::class)
            : null,
    );
});

$container->set(\Skeletor\Core\Security\TwoFactor\TwoFactorService::class, function() use ($container) {
    $config = $container->get(Config::class);

    return new \Skeletor\Core\Security\TwoFactor\TwoFactorService(
        $container->get(\Skeletor\Core\Login\Repository\TwoFactorSecretRepository::class),
        new \Skeletor\Core\Security\TwoFactor\TotpGenerator(),
        new \Skeletor\Core\Security\TwoFactor\SecretCipher((string) ($config->twoFactor?->encryptionKey ?? '')),
        $container->get(DateTime::class),
        $container->get(\Skeletor\Core\Security\AuthPolicy::class),
        (string) ($config->twoFactor?->issuer ?? $config->appName ?? 'Skeletor'),
    );
});

$container->set(\Skeletor\Core\Login\Controller\LoginController::class, function() use ($container) {
    $policy = $container->get(\Skeletor\Core\Security\AuthPolicy::class);

    return new \Skeletor\Core\Login\Controller\LoginController(
        $container->get(\Skeletor\Core\Login\Service\Login::class),
        $container->get(ManagerInterface::class),
        $container->get(Config::class),
        $container->get(Flash::class),
        $container->get(Engine::class),
        $container->get(Logger::class),
        $container->get(\Skeletor\Core\Login\Filter\ForgotPassword::class),
        $container->get(\Skeletor\User\Filter\Login::class),
        $container->get(\Skeletor\Core\Login\Filter\ResetPassword::class),
        $container->get(\Skeletor\Core\Login\Repository\ForgotPasswordRepository::class),
        $container->get(\Skeletor\Core\Login\Service\MagicLinkService::class),
        $container->get(\Skeletor\Core\Security\Authenticator\AuthenticatorRegistry::class),
        $container->get(\Skeletor\Core\Security\EntityRegistry::class),
        $policy,
        $container->get(\Skeletor\Core\Security\Authentication\PendingAuthentication::class),
        // Built only when it is going to be used: TwoFactorService needs an encryption key,
        // and an app with two-factor off should not have to configure one.
        $policy->twoFactorRequired()
            ? $container->get(\Skeletor\Core\Security\TwoFactor\TwoFactorService::class)
            : null,
    );
});

$container->set(Skeletor\Core\Middleware\MiddlewareInterface::class, function() use ($container) {
    return new AuthMiddleware(
        $container->get(ManagerInterface::class),
        $container->get(Config::class),
        $container->get(Flash::class),
        $container->get(Acl::class),
        $container->get(\Skeletor\Core\Security\EntityRegistry::class),
        $container->get(\Skeletor\Core\Security\Authorization\AuthorizationService::class),
        $container->get(\Skeletor\Core\Security\AuthPolicy::class),
    );
});

$container->set(\Skeletor\Exporter\Contracts\ExporterFactoryInterface::class, function() use ($container) {
    return new \Skeletor\Exporter\ExporterFactory($container->get(\Skeletor\Translator\Service\Translator::class));
});

// Content editor. Core blocks are registered here; an app adds its own by calling
// registerBlockFilter() on the same factory.
$container->set(\Skeletor\ContentEditor\Contracts\BlockFilterFactoryInterface::class, function() use ($container) {
    $blockFilterFactory = new \Skeletor\ContentEditor\BlockFilterFactory(
        $container->get(\Skeletor\Image\Service\Image::class)
    );

    $blockFilterFactory->registerBlockFilter('core/paragraph', new BlockFilters\Paragraph());
    $blockFilterFactory->registerBlockFilter('core/heading', new BlockFilters\Heading());
    $blockFilterFactory->registerBlockFilter('core/headingtwo', new BlockFilters\HeadingTwo());
    $blockFilterFactory->registerBlockFilter('core/headingthree', new BlockFilters\HeadingThree());
    $blockFilterFactory->registerBlockFilter('core/headingfour', new BlockFilters\HeadingFour());
    $blockFilterFactory->registerBlockFilter('core/headingfive', new BlockFilters\HeadingFive());
    $blockFilterFactory->registerBlockFilter('core/headingsix', new BlockFilters\HeadingSix());
    $blockFilterFactory->registerBlockFilter('core/unorderedList', new BlockFilters\UnorderedList());
    $blockFilterFactory->registerBlockFilter('core/orderedList', new BlockFilters\OrderedList());
    $blockFilterFactory->registerBlockFilter('core/quote', new BlockFilters\Quote());
    $blockFilterFactory->registerBlockFilter('core/html', new BlockFilters\Html());
    $blockFilterFactory->registerBlockFilter('core/image', new BlockFilters\Image());
    $blockFilterFactory->registerBlockFilter('core/gallery', new BlockFilters\Gallery());
    $blockFilterFactory->registerBlockFilter('core/divider', new BlockFilters\Divider());
    $blockFilterFactory->registerBlockFilter('core/embed', new BlockFilters\Embed());
    $blockFilterFactory->registerBlockFilter('core/spacer', new BlockFilters\Spacer());
    $blockFilterFactory->registerBlockFilter('core/columns', new BlockFilters\Columns());
    $blockFilterFactory->registerBlockFilter('core/table', new BlockFilters\Table());
    $blockFilterFactory->registerBlockFilter('core/chart', new BlockFilters\Chart());
    $blockFilterFactory->registerBlockFilter('core/footnotes', new BlockFilters\Footnotes());
    $blockFilterFactory->registerBlockFilter('core/accordion', new BlockFilters\Accordion());
    $blockFilterFactory->registerBlockFilter('core/tabs', new BlockFilters\Tabs());
    $blockFilterFactory->registerBlockFilter('core/timeline', new BlockFilters\Timeline());

    return $blockFilterFactory;
});

$container->set(\Skeletor\ContentEditor\Contracts\ContentEditorFilterInterface::class, function() use ($container) {
    return $container->get(\Skeletor\ContentEditor\Filter::class);
});

// Renders core/* editor blocks from themes/frontend/contentEditor,
// e.g. the core/paragraph block from contentEditor/core/paragraph.php.
$container->set(\Skeletor\ContentEditor\Contracts\BlockViewInterface::class, function() use ($container) {
    $view = new \Skeletor\ContentEditor\View(
        $container->get(Engine::class),
        APP_PATH . '/themes/frontend/contentEditor'
    );

    $view->registerViewFilter('core/image', new \Skeletor\ContentEditor\BlockViewFilters\Image(
        $container->get(\Skeletor\Image\Service\Image::class)
    ));

    $view->registerViewFilter('core/gallery', new \Skeletor\ContentEditor\BlockViewFilters\Gallery(
        $container->get(\Skeletor\Image\Service\Image::class)
    ));

    $view->registerViewFilter('core/embed', new \Skeletor\ContentEditor\BlockViewFilters\Embed());

    return $view;
});

$container->set(EntityManagerInterface::class, function() use ($container) {
    //@TODO get from config
    $env = getenv('APPLICATION_ENV');
    $config = ORMSetup::createAttributeMetadataConfiguration(
        paths: [
            APP_PATH . "/src/User",
            APP_PATH . "/src/Core/Activity/Entity",
            APP_PATH . "/src/Image",
            APP_PATH . "/src/Core/Login",
            APP_PATH . '/src/File',
            APP_PATH . '/src/Blog',
            APP_PATH . '/src/Page/Entity',
            APP_PATH . '/src/ThemeSettings',
            APP_PATH . '/src/Translator',
            APP_PATH . '/src/Core/Entity',
            APP_PATH . '/src/Reference/Entity',
            APP_PATH . '/src/Lead/Entity',
            APP_PATH . '/src/Author/Entity',
        ],
//            APP_PATH . "/packages"],
        isDevMode: $env !== 'production',
    );
    $config->setAutoGenerateProxyClasses(true);
    // symfony/var-exporter 8 removed LazyGhostTrait, so Doctrine's proxy factory throws unless
    // native lazy objects are used. Requires PHP 8.4, and is mandatory in Doctrine ORM 4.
    $config->enableNativeLazyObjects(true);

//    $redisConnection = RedisAdapter::createConnection('redis://localhost');
//    $metadataCache = new RedisAdapter($redisConnection, 'doctrine_metadata');
//    $config->setMetadataCache($metadataCache);
//    $config->setResultCache($metadataCache);
//    $config->setHydrationCache($metadataCache);

//    $resultCache = new Symfony\Component\Cache\Adapter\RedisTagAwareAdapter($container->get(\Redis::class));
//    $config->setResultCache($resultCache);
//    $config->setMetadataCache($resultCache);
//    $config->setHydrationCache($resultCache);


    // todo add tracy panel
//    if($env !== 'production') {
//        \Tracy\Debugger::enable(\Tracy\Debugger::Development, DATA_PATH . '/logs');
//        $panel = new \A3S\Tracy\Service\DoctrinePanel();
//        \Tracy\Debugger::getBar()->addPanel($panel);
//        $logger = new \A3S\Tracy\Service\TracyDoctrineLogger($panel);
//        $middleware = new Middleware($logger);
//        $config->setMiddlewares([$middleware]);
//    }

    $dbConfig = $container->get(Config::class);
    $connection = \Doctrine\DBAL\DriverManager::getConnection([
        'dbname' => $dbConfig->db->write->name,
        'user' => $dbConfig->db->write->user,
        'password' => $dbConfig->db->write->pass,
        'host' => $dbConfig->db->write->host,
        'driver' => 'pdo_mysql',
    ], $config);

    $eventManager = new \Doctrine\Common\EventManager();
    if($env !== 'production') {
        // todo add tracy panel
//        $eventManager->addEventSubscriber(new TracyEventSubscriber());
    }

    $em = new EntityManager($connection, $config, $eventManager);

    return $em;
});

$container->set(SerializerInterface::class, function() {
    $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer()];
    $encoders = [new JsonEncoder(), new XmlEncoder()];

    return new Serializer($normalizers, $encoders);
});

$container->set(TagAwareAdapter::class, function() use ($container) {
    $config = $container->get(Config::class);

    //@TODO add failover
    $dsn = "redis://" . array_key_first($config->redis->hosts->toArray()) . $config->redis->hosts[0];
    $redisClient = RedisAdapter::createConnection($dsn);
    $redisAdapter = new RedisAdapter($redisClient);
    $cache = new TagAwareAdapter($redisAdapter);

    return $cache;
});

return $container;