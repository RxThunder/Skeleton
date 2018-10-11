<?php

use Symfony\Component\DependencyInjection\Definition;

$definition = new Definition();
$definition->setPublic(false);
$definition->setAutowired(true);
$definition->setAutoconfigured(true);

$this->registerClasses($definition, 'App\\', '../src/*');
