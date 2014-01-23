<?php

namespace WeavingTheWeb\Bundle\FrameworkExtraBundle\Test;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface DataFixturesAwareInterface
{
    public function requireMySQLFixtures();

    public function requireSQLiteFixtures();
}
