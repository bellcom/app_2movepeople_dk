<?php

namespace Drupal\bc_webform;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformEntityListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of webform entities.
 *
 * @see \Drupal\webform\Entity\Webform
 */
class BcWebformList extends WebformEntityListBuilder implements ContainerInjectionInterface  {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type = $container->get('entity.manager')->getDefinition('webform');
    return new static(
      $container->get('entity.manager')->getDefinition('webform'),
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['add_webform'] = [
      '#title' => $this->t('Add webform'),
      '#type' => 'link',
      '#url' => Url::fromRoute('entity.webform.add_form'),
      '#attributes' => [
        'class' => ['btn', 'btn-default'],
      ],
      '#weight' => -10,
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    $bcWebformHandler = new BcWebformHandler($entity);
    if (!$bcWebformHandler->matchOrganization(\Drupal::currentUser()->id())) {
      return NULL;
    }
    $targetUsers = $bcWebformHandler->getTargetUsers();
    $results = &$row['results'];
    if (is_array($row['results'])) {
      $results = &$row['results']['data']['#title'];
    }
    $results .= '/' . count($targetUsers);
    return $row;
  }

}
