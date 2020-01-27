<?php

namespace Drupal\access_control\EventSubscriber;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * AccessControlSubscriber class
 */
class AccessControlSubscriber implements EventSubscriberInterface {

    /**
     * Config Factory
     *
     * @var Drupal\Core\Config\ConfigFactory $config
     */
    protected $config;

    /**
     * Response object
     *
     * @var Symfony\Component\HttpFoundation\Response $response
     */
    protected $response;

    /**
     * The current user for the request
     *
     * @var Drupal\Core\Session\AccountProxy $currentUser
     */
    protected $currentUser;

    /**
     * Entity type manager
     *
     * @var Drupal\Core\Entity\EntityTypeManager $entityTypeManager
     */
    protected $entityTypeManager;

    /**
     * Default Drupal cache object
     *
     * @var Drupal\Core\Cache\DatabaseBackend $cache
     */
    protected $cache;

    /**
     * Node types to show in lockdown mode
     *
     * @var array
     */
    protected $nodeTypesToShowInLockdown = [
        'type' => 'page'
    ];

    /**
     * Constructor for AccessControlSubscriber
     *
     * @param ConfigFactory $config
     * @param AccountProxy $currentUser
     * @param EntityTypeManager $entityTypeManager
     * @param DatabaseBackend $cache
     */
    public function __construct(ConfigFactory $config, AccountProxy $currentUser, EntityTypeManager $entityTypeManager, DatabaseBackend $cache) {
        $this->config = $config;
        // @todo Find the right way to inject this
        $this->response = new Response();
        $this->currentUser = $currentUser;
        $this->entityTypeManager = $entityTypeManager;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     * The event names to listen for, and the methods that should be executed.
     *
     * @return array
     */
    public static function getSubscribedEvents() {
        $events = array();
        $events[KernelEvents::RESPONSE][] = ['checkAvailability', 9999];
        return $events;
    }

    /**
     * Check if site should be available - for anonymous only
     *
     * @param Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function checkAvailability(FilterResponseEvent $event) {
        if ($this->currentUser->isAnonymous()) {
            if (self::shouldBeOffline()) {
                $output = '<h1>Website is Currently Offline</h1><h2>Look at all the fun content that awaits!</h2>';
                $output .= self::generateHtmlListOfLockdownNodes();
                // @todo - do you have to check if the item exists before setting, or will render cache do that prior to this point?
                $this->cache->set('access_control_page', $this->response, $this->cache::CACHE_PERMANENT, array('ac:response'));
                // Set the content, stop all other events, respond
                $this->response->setContent($output);
                $event->stopPropagation();
                $event->setResponse($this->response);
            }
        }
    }

    /**
     * Generates a list of titles of a given node type
     * as defined in $nodeTypesToShowInLockdown
     * @return string
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
     * See if the site should be available from settings
     * path: /admin/access-control/killswitch
     * @return boolean
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