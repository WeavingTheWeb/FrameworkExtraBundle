<?php

namespace WeavingTheWeb\Bundle\FrameworkExtraBundle\Test;

use Doctrine\DBAL\Schema\SchemaException;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase,
    Symfony\Component\HttpKernel\HttpKernelInterface,
    Symfony\Bundle\FrameworkBundle\Client;

/**
 * @author Thierry Marianne <thierrym@theodo.fr>
 */
abstract class TestCase extends WebTestCase implements TestCaseInterface, DataFixturesAwareInterface
{
    /**
     * @var $client Client
     */
    protected $client;

    /**
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected static $kernel;

    /**
     * @param array $options
     * @param array $server
     * @return mixed|Client
     */
    public function getClient(array $options = array(), array $server = array())
    {
        $client = null;

        try {
            $client = self::createClient($options, $server);
        } catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }

        if ($this->requireFixtures()) {
            /** @var DatabaseManager $databaseManager */
            $databaseManager = $this->get('weaving_the_web_framework_extra.database_manager');

            $requiredFixturesParentDirectory = $this->requireFixturesParentDirectory();
            if (!is_null($requiredFixturesParentDirectory)) {
                $databaseManager->setFixturesParentDirectory($requiredFixturesParentDirectory);
            }

            if ($this->requireSQLiteFixtures()) {
                $databaseManager->requireSQLiteDatabase();
            } elseif ($this->requireMySQLFixtures()) {
                $databaseManager->requireMySQLDatabase();
            }

            $databaseManager->regenerateSchema();
            $databaseManager->loadFixtures();
        }

        return $client;
    }

    /**
     * Overrides this method to declare the fixtures parent directory
     *
     * @return bool
     */
    public function requireFixturesParentDirectory()
    {
        return null;
    }

    /**
     * @return bool
     */
    protected function requireFixtures()
    {
        return $this->requireSQLiteFixtures() || $this->requireMySQLFixtures();
    }

    public function requireMySQLFixtures()
    {
        return false;
    }

    public function requireSQLiteFixtures()
    {
        return false;
    }

    /**
     * @param $name
     * @param array $options
     * @return bool
     */
    protected function extractOption($name, array &$options)
    {
        if (array_key_exists($name, $options)) {
            $option = $options[$name];
            unset($options[$name]);
        } else {
            $option = false;
        }

        return $option;
    }

    public function getAuthenticatedClient(array $options = array(), $server = array())
    {
        if (null === $this->getContainer()) {
            $this->client = $this->getClient();
        }

        $followRedirects = $this->extractOption('follow_redirects', $options);

        $userName = $this->getParameter('weaving_the_web_framework_extra.authorization_user');
        $password = $this->getParameter('weaving_the_web_framework_extra.authorization_password');
        $server = array_merge($server,
            array(
                'PHP_AUTH_USER' => $userName,
                'PHP_AUTH_PW' => $password
            ));

        $this->client = $this->getClient($options, $server);
        $this->client->followRedirects($followRedirects);

        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getContainer()
    {
        return static::$kernel->getContainer();
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->getContainer()->getParameter($name);
    }

    /**
     * @param $serviceId
     * @return mixed
     */
    public function getService($serviceId)
    {
        return $this->get($serviceId);
    }

    public function get($serviceId)
    {
        return $this->getContainer()->get($serviceId);
    }
}
