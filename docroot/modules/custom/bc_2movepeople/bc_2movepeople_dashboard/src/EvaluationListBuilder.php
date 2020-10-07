<?php

namespace Drupal\bc_2movepeople_dashboard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Evaluation entities.
 *
 * @ingroup bc_2movepeople_dashboard
 */
class EvaluationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Evaluation ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\bc_2movepeople_dashboard\Entity\Evaluation $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.evaluation.edit_form',
      ['evaluation' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
