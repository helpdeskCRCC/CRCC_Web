<?php

namespace Drupal\crcc_migrate_tweak;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use \Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Class CrccMigrateTweakServiceProvider.
 *
 * crcc_migrate_tweak custom service provider.
 * Replaces core FieldDiscovery service with a custom one.
 */
class CrccMigrateTweakServiceProvider extends ServiceProviderBase implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('migrate_drupal.field_discovery');
    $definition->setClass('Drupal\crcc_migrate_tweak\migrate_drupal\CrccFieldDiscovery');
  }

}
