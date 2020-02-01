<?php

namespace Drupal\access_control;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Access Control Lockdown Service.
 */
class AccessControlLockdown {

  use StringTranslationTrait;
  /**
   * The config factory service.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Entity type manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;


  /**
   * Node types to show in lockdown mode.
   *
   * @var array
   */
  protected $nodeTypesToShowInLockdown = [
    'type' => 'page',
  ];

  /**
   * Constructor for AccessControlLockdown.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->config = $config_factory->get('access_control.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Returns a list of node titles.
   *
   * @return array
   *   Array of titles.
   */
  public function generateListOfLockdownNodes() {
    // @todo - this needs a limit.
    $titles = [];
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadByProperties($this->nodeTypesToShowInLockdown);
    foreach ($nodes as $node) {
      $title = $this->t("@title", ['@title' => $node->get('title')->value]);
      $titles[] = $title;
    }
    return $titles;
  }

  /**
   * Checks if the site is in lockdown mode.
   *
   * @return bool
   *   Should the site be in lockdown?
   */
  public function shouldLockdown() {
    if ($this->config->get('lockdown') === 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Retrieves the custom message to display in lockdown mode.
   *
   * @return string
   *   Custom message from settings page.
   */
  public function customLockdownMessage() {
    $message = $this->t("@message", ['@message' => $this->config->get('lockdown_message')]);
    return $message;
  }

}
