<?php

namespace WeavingTheWeb\Bundle\FrameworkExtraBundle\Test;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use WeavingTheWeb\Bundle\FrameworkExtraBundle\Test\CommandTestCase;

/**
 * @author Thierry Marianne <thierrym@theodo.fr>
 */
abstract class WebTestCase extends CommandTestCase
{
    protected static $kernel;

    protected static $container;

    /**
     * @var $client Client
     */
    protected $client;

    public static function setUpBeforeClass($environment = 'prod', $debug = true)
    {
        $options['environment'] = $environment;
        $options['debug'] = $debug;

        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }

        self::$kernel = self::createKernel($options);
        self::$kernel->boot();
        self::$container = self::$kernel->getContainer();
    }

    public static function tearDownAfterClass()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
    }

    public function detachSessionFromCookies()
    {
        if ('' !== session_id()) {
            session_destroy();
        }

        ini_set('session.use_only_cookies', false);
        ini_set('session.use_cookies', false);
        ini_set('session.use_trans_sid', false);
        ini_set('session.cache_limiter', null);
    }

    /**
     * @param $expectedCode
     * @return Response
     */
    public function assertResponseStatusCodeEquals($expectedCode)
    {
        /**
         * @var $response Response
         */
        $response   = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        if ($expectedCode !== $statusCode) {
            $outputMessage = $response->getContent();
        } else {
            $outputMessage = '';
        }

        $this->assertEquals($expectedCode, $statusCode, $outputMessage);

        return $response;
    }
}
