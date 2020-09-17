<?php

namespace Drupal\crcc_migrate_tweak\migrate_drupal;

use Drupal\migrate_drupal\FieldDiscovery;
use Drupal\migrate_drupal\FieldDiscoveryInterface;

/**
 * Class CrccFieldDiscovery.
 *
 * Overrides FieldDiscovery::getFieldInstanceStubMigrationDefinition().
 * Prevents the error: Field discovery failed for Drupal core version 7. Did this site have the CCK or Field module installed? Error: No database connection configured for source plugin d7_field_instance
 *
 * @see FieldDiscovery::getFieldInstanceStubMigrationDefinition()
 */
class CrccFieldDiscovery extends FieldDiscovery implements FieldDiscoveryInterface {

  /**
   * {@inheritdoc}
   */
  protected function getFieldInstanceStubMigrationDefinition($core) {
    $definition = parent::getFieldInstanceStubMigrationDefinition($core);

    // Specify the db key.
    if (!empty($definition['source']) && !isset($definition['source']['key'])) {
      $definition['source']['key'] = 'drupal7';
    }
    return $definition;
  }

}
