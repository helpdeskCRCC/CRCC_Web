<?php


namespace Drupal\crcc_migrate_tweak\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\menu_link_content\Plugin\migrate\source\MenuLink;

/**
 * Alters core 'menu_link' source plugin.
 *
 * @see \Drupal\menu_link_content\Plugin\migrate\source\MenuLink
 */
class CrccMenuLink extends MenuLink  {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->handleTranslation($query);

    // Migrate only content menus.
    $query->condition('ml.menu_name', $this->getMenuNamesToMigrate(), 'IN');

    return $query;
  }

  /**
   * Returns menu names to migrate.
   */
  protected function getMenuNamesToMigrate() {
    return ['menu-footer', 'main-menu', 'menu-newsroom', 'menu-secondary-menu'];
  }

  /**
   * Adapt our query for translations.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The generated query.
   */
  protected function handleTranslation(SelectInterface $query) {
    $default_lang = 'en';
    $lang_op = $this->configuration['translation'] ? '<>' : '=';
    $condition = $query->orConditionGroup()
      ->condition('ml.language', $default_lang, $lang_op)
      ->condition('ml.language', 'und');
    $query->condition($condition);
  }

}
