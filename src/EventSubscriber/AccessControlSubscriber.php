<?php

namespace Drupal\access_control\EventSubscriber;

use Drupal\access_control\AccessControlLockdown;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Render\Renderer;

/**
 * AccessControlSubscriber class.
 */
class AccessControlSubscriber implements EventSubscriberInterface {

  /**
   * Config Factory.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The current user for the request.
   *
   * @var Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Default Drupal cache object.
   *
   * @var Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Access control service.
   *
   * @var Drupal\access_control\AccessControlLockdown
   */
  protected $accessControl;

  /**
   * Renderer service.
   *
   * @var Drupal\Core\Render\Renderer
   */
  protected $renderer;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $default_cache
   *   The default cache backend service.
   * @param \Drupal\access_control\AccessControlLockdown $access_control
   *   The access control lockdown service.
   * @param Drupal\Core\Render\Renderer $renderer_service
   *   The renderer service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $default_cache, AccessControlLockdown $access_control, Renderer $renderer_service) {
    $this->config = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $default_cache;
    $this->accessControl = $access_control;
    $this->renderer = $renderer_service;
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
    // Gateways.
    $anon_check = $this->currentUser->isAnonymous();
    if (!$anon_check) {
      // Not anonymous - exit.
      return;
    }
    $lockdown_check = $this->accessControl->shouldLockdown();
    if (!$lockdown_check) {
      // Killswitch is off - exit.
      return;
    }

    // Check for the item in cache.
    $cacheItem = $this->cache->get('access_control_page)');
    // If no data retrieved from cache, output & set in cache.
    if (empty($cacheItem->data)) {
      $output = [
        '#theme' => 'lockdown_response',
        '#body' => $this->accessControl->generateListOfLockdownNodes(),
        '#custom_message' => $this->accessControl->customMessage(),
      ];
      // @todo -- this shouldnt use the straight renderer class.
      $rendered = $this->renderer->renderPlain($output);
      $response = $event->getResponse();
      $response->setContent($rendered);
      $event->setResponse($response);
      $this->cache->set('access_control_page', $response, $this->cache::CACHE_PERMANENT, ['ac:response']);
      $event->stopPropagation();
      return;
    }
    else {
      $response = $cacheItem->data;
    }
    $event->setResponse($cacheItem->data);
    $event->stopPropagation();
  }

}
