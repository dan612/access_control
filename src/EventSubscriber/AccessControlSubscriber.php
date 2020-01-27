<?php

namespace Drupal\access_control\EventSubscriber;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * AccessControlSubscriber class
 */
class AccessControlSubscriber implements EventSubscriberInterface {
    
    protected $config;
    protected $response;
    protected $currentUser;
    protected $entityTypeManager;
    protected $cache;
    protected $nodeTypesToShowInLockdown = [
        'type' => 'page'
    ];

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
     * 
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
     * @param Symfony\Component\EventDispatcher\Event $event
     */
    public function checkAvailability(Event $event) {
        if ($this->currentUser->isAnonymous()) {
            if (self::shouldBeOffline()) {
                $output = '<h1>Website is Currently Offline</h1><h2>Look at all the fun content that awaits!</h2>';
                $lockdown_nodes = self::generateHtmlListOfLockdownNodes();
                $output .= $lockdown_nodes;
                $this->cache->set('access_control_page', $this->response, $this->cache::CACHE_PERMANENT, array('ac:response'));
                $this->response->setContent($output);
                $event->stopPropagation();
                $event->setResponse($this->response);
            }
        }
    }

    /**
     * Generates a list of titles of a given node type
     * as defined in $nodeTypesToShowInLockdown
     * 
     * Default is page
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