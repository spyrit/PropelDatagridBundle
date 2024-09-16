<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Propel\Bundle\PropelBundle\PropelBundle(),
            new Spyrit\PropelDatagridBundle\SpyritPropelDatagridBundle(),
            new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(),
            new Spyrit\TestBundle\SpyritTestBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/config_test.yml');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/SpyritPropelDatagridBundle/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/SpyritPropelDatagridBundle/logs';
    }
}
