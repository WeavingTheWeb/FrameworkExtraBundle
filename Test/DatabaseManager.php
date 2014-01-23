<?php

namespace WeavingTheWeb\Bundle\FrameworkExtraBundle\Test;

use Doctrine\DBAL\DriverManager,
    Doctrine\DBAL\Schema\SchemaException,
    Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Finder\Finder;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class DatabaseManager implements DatabaseAwareInterface
{
    const DRIVER_MYSQL = 'mysql';

    const DRIVER_SQLITE = 'sqlite';

    /**
     * @var bool
     */
    protected $isMySQLDatabaseRequired;

    /**
     * @var bool
     */
    protected $isSQLiteDatabaseRequired;

    /**
     * @var string
     */
    protected $requiredDriver = self::DRIVER_SQLITE;

    /**
     * @var
     */
    protected $fixturesParentDirectory;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $entityManagers;

    /**
     * @var \Doctrine\Common\DataFixtures\Executor\AbstractExecutor
     */
    protected $executors;

    /**
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    protected $schemaManipulators;

    /**
     * @var \Symfony\Component\Finder\Finder $finder
     */
    public $finder;

    /**
     * @var \Doctrine\Common\DataFixtures\Loader
     */
    public $loader;

    /**
     * @var \Psr\Log\loggerInterface
     */
    public $logger;

    public function requireSQLiteDatabase()
    {
        $this->requiredDriver = self::DRIVER_SQLITE;
        $this->isSQLiteDatabaseRequired = true;
    }

    public function requireMySQLDatabase()
    {
        $this->requiredDriver = self::DRIVER_MYSQL;
        $this->isMySQLDatabaseRequired = true;
    }

    /**
     * DatabaseManager constructor.
     *
     * @param array $entityManagers
     * @param array $executors
     * @param array $schemaManipulators
     */
    public function __construct(array $entityManagers, array $executors, array $schemaManipulators)
    {
        $this->entityManagers = $entityManagers;
        $this->executors = $executors;
        $this->schemaManipulators = $schemaManipulators;
        
        $this->fixturesParentDirectory = __DIR__ . '/../../../../';
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     *
     * @return void
     */
    public function regenerateSchema()
    {
        $this->dropDatabase();
        $this->createSchema();
    }

    public function dropDatabase()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->entityManagers[$this->requiredDriver];

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $entityManager->getConnection();
        $name = $connection->getDatabase();

        if ($this->isMySQLDatabaseRequired) {
            $metadata = $this->getSchemaMetadata();
            try {
                $connection->getSchemaManager()->listDatabases();

                /** @var \Doctrine\ORM\Tools\SchemaTool $schemaTool */
                $schemaTool = $this->schemaManipulators[$this->requiredDriver];
                $schemaTool->dropSchema($metadata);
            } catch (\PDOException $exception) {
                /**
                 * http://dev.mysql.com/doc/refman/5.0/fr/error-handling.html#error_er_bad_db_error
                 * Unknown database error code
                 */
                if ($exception->getCode() === 1049) {
                    $this->createDatabase($connection);
                } else {
                    $this->logger->error($exception->getMessage());
                    throw new $exception;
                }
            }
        } elseif ($this->isSQLiteDatabaseRequired) {
            $connection->getSchemaManager()->dropDatabase($name);
        }
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function createSchema()
    {
        $metadata = $this->getSchemaMetadata();

        if (!empty($metadata)) {
            /** @var \Doctrine\ORM\Tools\SchemaTool $schemaTool */
            $schemaTool = $this->schemaManipulators[$this->requiredDriver];
            $schemaTool->createSchema($metadata);
        } else {
            throw new SchemaException('No Metadata Classes to process.');
        }
    }

    public function loadFixtures()
    {
        $directories = $this->getFixturesDirectories();
        $fixtures = $this->getFixtures($directories);

        /** @var \Doctrine\Common\DataFixtures\Executor\AbstractExecutor $executor */
        $executor = $this->executors[$this->requiredDriver];
        $executor->execute($fixtures);
    }

    /**
     * @return Finder
     */
    public function getFixturesDirectories()
    {
        return $this->finder->ignoreVCS(true)
            ->ignoreDotFiles(true)
            ->depth('<= 5')
            ->directories()
            ->in($this->fixturesParentDirectory)
            ->name('DataFixtures');
    }

    /**
     * @param $directory
     * @return $this
     */
    public function setFixturesParentDirectory($directory)
    {
        $this->fixturesParentDirectory = $directory;
        
        return $this;
    }

    /**
     * @param $directories
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getFixtures($directories)
    {
        /** @var \SplFileInfo $directory */
        foreach ($directories as $directory) {
            $this->loader->loadFromDirectory($directory->getRealPath());
        }

        return $this->loader->getFixtures();
    }

    /**
     * @return array
     */
    public function getSchemaMetadata()
    {
        return $this->entityManagers[$this->requiredDriver]->getMetadataFactory()->getAllMetadata();
    }

    /**
     * @param $connection
     */
    protected function createDatabase($connection)
    {
        $params = $connection->getParams();
        $name = isset($params['path']) ? $params['path'] : $params['dbname'];
        unset($params['dbname']);
        $tmpConnection = DriverManager::getConnection($params);

        // Only quote if we don't have a path
        if (!isset($params['path'])) {
            $name = $tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($name);
        }

        $tmpConnection->getSchemaManager()->createDatabase($name);
    }
}
