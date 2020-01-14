<?php

namespace Drupal\bc_webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Contains MovepeopleDashboardController.
 */
class BcWebformController extends ControllerBase {

  /**
   * Entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Redirect to analysis page.
   */
  public function analysisRedirect() {
    if (\Drupal::currentUser()->id() == 1) {
      return $this->entityTypeManager->getListBuilder('webform')->render();
    }
    return new RedirectResponse(Url::fromRoute('bc_webform.collection')->toString());
  }
}
