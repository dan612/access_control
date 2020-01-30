<?php

namespace Drupal\access_control\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block showing lockdown status.
 *
 * @Block(
 *   id = "lockdown_status",
 *   admin_label = @Translation("Lockdown Status"),
 *   category = @Translation("Lockdown Status"),
 * )
 */
class LockDownStatusBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Build and return an empty block.
    $build = [
      '#theme' => 'lockdown_status',
      '#body' => "",
    ];
    // $build['#cache']['#max-age'] = 0;.
    return $build;
  }

}
