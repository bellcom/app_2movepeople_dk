<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Routing\RouteSubscriber.
 */

namespace Drupal\bc_2movepeople_dashboard\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_form', '\Drupal\bc_2movepeople_dashboard\Form\NewUserLoginForm');
    }
  }
}
