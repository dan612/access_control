<?php

namespace Drupal\access_control\EventSubscriber;

use Drupal\access_control\AccessControlLockdown;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * AccessControlSubscriber class.
 */
class AccessControlSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
   * Access control lockdown service.
   *
   * @var Drupal\access_control\AccessControlLockdown
   */
  protected $accessControl;

  /**
   * Bare HTML Renderer service.
   *
   * @var Drupal\Core\Render\BareHtmlPageRendererInterface
   */
  protected $renderer;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   * @param Drupal\Core\Render\BareHtmlPageRendererInterface $html_renderer
   *   The bare html page renderer service.
   * @param Drupal\Core\Messenger\MessengerInterface $msg_service
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $default_cache, AccessControlLockdown $access_control, BareHtmlPageRendererInterface $html_renderer, MessengerInterface $msg_service, TranslationInterface $string_translation) {
    $this->config = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $default_cache;
    $this->accessControl = $access_control;
    $this->renderer = $html_renderer;
    $this->messenger = $msg_service;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['checkForLockdownMode', 9999];
    $events[KernelEvents::FINISH_REQUEST][] = ['displayAdminWarning', 9999];
    return $events;
  }

  /**
   * Check if site should be in lockdown mode - for anonymous only.
   *
   * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The current event.
   */
  public function checkForLockdownMode(FilterResponseEvent $event) {
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
        '#custom_message' => $this->accessControl->customLockdownMessage(),
      ];
      $rendered = $this->renderer->renderBarePage($output, $this->t("Website Offline"), 'page');
      $response = $event->getResponse();
      $response->setContent($rendered->getContent());
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

  /**
   * Displays a warning message to admins if in lockdown.
   *
   * @param \Symfony\Component\HttpKernel\Event\FinishRequestEvent $event
   *   Event when response is generated.
   */
  public function displayAdminWarning(FinishRequestEvent $event) {
    $admin_user = $this->currentUser->hasPermission('administer site configuration');
    if (!$admin_user) {
      // Not an admin user - exit.
      return;
    }
    if ($this->accessControl->shouldLockdown()) {
      $message = $this->t('Site is currently in lockdown mode. Visit the <a href="@killswitch">killswitch</a> to disable.', ['@killswitch' => Url::fromRoute('access_control.settings')->toString()]);
      $this->messenger->addWarning($message);
      return;
    };
  }

}
