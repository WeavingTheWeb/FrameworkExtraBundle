<?php

namespace WeavingTheWeb\Bundle\FrameworkExtraBundle\Test;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface DatabaseAwareInterface
{
    public function loadFixtures();

    public function requireMySQLDatabase();

    public function requireSQLiteDatabase();
}
