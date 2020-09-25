<?php

namespace Drupal\bc_2movepeople_dashboard;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Class Bc2movepeopleDashboardServiceProvider.
 *
 * @package Drupal\bc_2movepeople_dashboard
 */
class Bc2movepeopleDashboardServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    try {
      $container->getDefinition('web_push_notification.sender');
    }
    catch (ServiceNotFoundException $exception) {
      $container->removeDefinition('2movepeople_dashboard.mailer');
    }
  }

}
