<?php

namespace Drupal\bc_2movepeople\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An example controller.
 */
class DashboardController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $build = array(
      '#type' => 'markup',
      '#markup' => t('Hello World!'),
    );
    return $build;
  }

}
