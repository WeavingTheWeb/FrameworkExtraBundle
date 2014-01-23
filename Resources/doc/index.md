Getting started
==================================

Installation
--------------------------------

Add the Weaving The Web Framework Extra bundle as a requirement in your composer.json:

    {
        "require": {
            "weaving-the-web/framework-extra-bundle": "dev-master"
        }
    }

Register the bundle in your Kernel for the test environment:

    # in app/AppKernel.php
    $bundles[] = new WeavingTheWeb\Bundle\FrameworkExtraBundle\WeavingTheWebFrameworkExtraBundle();

Download Composer

    curl -sS https://getcomposer.org/installer | php

Install the bundle dependencies in your project's `vendor` directory

    php composer.phar install --prefer-dist
