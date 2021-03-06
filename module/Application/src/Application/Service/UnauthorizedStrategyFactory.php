<?php

namespace Application\Service;

use Application\View\UnauthorizedStrategy;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UnauthorizedStrategyFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /* @var \ZfcRbac\Options\ModuleOptions $moduleOptions */
        $moduleOptions = $serviceLocator->get('ZfcRbac\Options\ModuleOptions');

        return new UnauthorizedStrategy($moduleOptions->getUnauthorizedStrategy());
    }
}
