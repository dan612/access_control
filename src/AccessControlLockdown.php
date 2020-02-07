<?php

namespace Drupal\access_control;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use GuzzleHttp\Client;

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
   * The HTTP Client Service.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructor for AccessControlLockdown.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param GuzzleHttp\Client $http_client
   *   The HTTP Client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, Client $http_client) {
    $this->config = $config_factory->get('access_control.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->httpClient = $http_client;
  }

  /**
   * Generates a list of node types for the settings form.
   *
   * @return array
   *   Array of node types available.
   */
  public function generateNodeTypeList() {
    $type_labels = [];
    $node_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($node_types as $type) {
      $type_labels[$type->id()] = $this->t('@label', ['@label' => $type->id()]);
    }
    return $type_labels;
  }

  /**
   * Generates a list of taxonomy terms for the settings form.
   *
   * @return array
   *   Array of taxonomy terms in sports taxonomy
   */
  public function generateTaxonomyTermList() {
    $vocab = "sports";
    $term_list = [];
    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree($vocab);
    foreach ($terms as $term) {
      $term_name = $this->t('@name', ['@name' => $term->name]);
      $term_list["$term_name"] = $term_name;
    }
    return $term_list;
  }

  /**
   * Generate node titles for preview in lockdown mode.
   */
  public function generateNodeTitlesForPreview() {
    $content_type = $this->config->get('lockdown_preview_type');
    if (!$content_type) {
      // Content type is not set - exit.
      return;
    }
    $values = [
      'type' => $content_type,
    ];
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties($values);
    $titles = [];
    foreach ($nodes as $node) {
      $title = $this->t("@title", ['@title' => $node->get('title')->value]);
      $titles[] = $title;
    }
    return $titles;
  }

  /**
   * Generate headlines from ESPN for display during lockdown.
   */
  public function generateEspnHeadlinesForPreview() {
    $selected_sport = $this->config->get('lockdown_preview_sports_headlines');
    if (!$selected_sport) {
      // No sport selected - exit.
      return;
    };
    $base_url = "https://www.espn.com/espn/rss/";
    $news_feed = $base_url . "$selected_sport/news";
    try {
      $request = $this->httpClient->get($news_feed);
    }
    catch (Exception $e) {
      echo "Unable to fetch URL:" . $news_feed;
    }
    $xml = $request->getBody()->getContents();
    $xml_string = simplexml_load_string($xml);
    $items = $xml_string->channel->item;
    $headlines = [];

    foreach ($items as $item) {
      $headlines[] = [
        $item->image,
        $item->title,
        $item->link,
      ];
    };
    return $headlines;
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
