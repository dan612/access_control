<?php

namespace Drupal\access_control\EventSubscriber;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * AccessControlSubscriber class.
 */
class AccessControlSubscriber implements EventSubscriberInterface {

  /**
   * Config Factory.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The current user for the request.
   *
   * @var Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Default Drupal cache object.
   *
   * @var Drupal\Core\Cache\DatabaseBackend
   */
  protected $cache;

  /**
   * Node types to show in lockdown mode.
   *
   * @var array
   */
  protected $nodeTypesToShowInLockdown = [
    'type' => 'page',
  ];

  /**
   * Constructor for AccessControlSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The current user service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Cache\DatabaseBackend $cache
   *   The default cache backend service.
   */
  public function __construct(ConfigFactory $config, AccountProxy $currentUser, EntityTypeManager $entityTypeManager, DatabaseBackend $cache) {
    $this->config = $config;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['checkAvailability', 9999];
    return $events;
  }

  /**
   * Check if site should be available - for anonymous only.
   *
   * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The current event.
   */
  public function checkAvailability(FilterResponseEvent $event) {
    if ($this->currentUser->isAnonymous() && self::shouldBeOffline()) {
      // @todo check for item in cache and return if true
      $output = '<h1>Website is Currently Offline</h1><h2>Look at all the fun content that awaits!</h2>';
      $output .= self::generateHtmlListOfLockdownNodes();
      $response = $event->getResponse();
      $response->setContent($output);
      $event->setResponse($response);
      $this->cache->set('access_control_page', $response, $this->cache::CACHE_PERMANENT, ['ac:response']);
      // Stop event.
      $event->stopPropagation();
    }
  }

  /**
   * Generates a list of titles of a given node type.
   *
   * @return string
   *   HTML string that is an unordered list.
   */
  public function generateHtmlListOfLockdownNodes() {
    $html = '<ul>';
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadByProperties($this->nodeTypesToShowInLockdown);
    foreach ($nodes as $node) {
      $title = $node->get('title')->value;
      $html .= '<li>' . $title . '</li>';
    }
    $html .= '</ul>';
    return $html;
  }

  /**
   * See if the site should be offline from settings.
   *
   * @return bool
   *   True/false should the site be offline.
   */
  public function shouldBeOffline() {
    $setting = $this->config->get('access_control.settings');
    $enabled_check = $setting->get('lockdown');
    if ($enabled_check === 1) {
      return TRUE;
    }
    return FALSE;
  }

}
