<?php

namespace Drupal\access_control\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class for Lockdown Status Check.
 */
class LockDownStatus extends ControllerBase {

  /**
   * Check the status of the killswitch.
   */
  public function checkLockDownStatus() {
    $setting = $this->config('access_control.settings');
    if ($setting->get('lockdown') === 1) {
      return new Response(1);
    }
    return new Response(0);
  }

}
