<?php
/**
 * @file
 * Contains sik_webshop RouteSubscriber definition.
 */

namespace Drupal\bc_webform\Routing;

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
    if (!empty($route = $collection->get('entity.webform.collection'))) {
      $route->setDefaults([
        '_controller' => '\Drupal\bc_webform\Controller\BcWebformController::analysisRedirect',
        '_title' => 'Webforms',
      ]);
      return;
    }
  }

}
