<?php

namespace WeavingTheWeb\Bundle\FrameworkExtraBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\HttpKernel\DependencyInjection\Extension,
    Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class WeavingTheWebFrameworkExtraExtension extends Extension implements PrependExtensionInterface
{

    public function prepend(ContainerBuilder $container)
    {
        $this->loadConfiguration($container);

        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);

        $configs = $container->getExtensionConfig('security');

        unset($configs[0]['access_control']);
        $configs[0]['providers']['in_memory']['memory']['users'][$config['test_user']['user']] = [
            'password' => $config['test_user']['password'],
            'roles' => ['ROLE_USER']
        ];

        $container->prependExtensionConfig('security', $configs[0]);
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration([], $container);
        $this->processConfiguration($configuration, $configs);

        $this->loadConfiguration($container);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration(
            $container->getParameter('weaving_the_web_framework_extra.authorization_user'),
            $container->getParameter('weaving_the_web_framework_extra.authorization_password')
        );
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadConfiguration(ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
