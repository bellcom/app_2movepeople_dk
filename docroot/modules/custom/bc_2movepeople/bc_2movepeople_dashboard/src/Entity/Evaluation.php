<?php

namespace Drupal\bc_2movepeople_dashboard\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Evaluation entity.
 *
 * @ingroup bc_2movepeople_dashboard
 *
 * @ContentEntityType(
 *   id = "evaluation",
 *   label = @Translation("Evaluation"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bc_2movepeople_dashboard\EvaluationListBuilder",
 *     "views_data" = "Drupal\bc_2movepeople_dashboard\Entity\EvaluationViewsData",
 *
 *     "access" = "Drupal\bc_2movepeople_dashboard\EvaluationAccessControlHandler",
 *   },
 *   base_table = "evaluation",
 *   translatable = FALSE,
 *   admin_permission = "administer evaluation entities",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
class Evaluation extends ContentEntityBase implements EvaluationInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('Subject user for evaluation.'))
      ->setSetting('target_type', 'user');

    $fields['file'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('File'))
      ->setDescription(t('Reference to generated pdf file.'))
      ->setSetting('target_type', 'file');

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Evaluation data'))
      ->setDescription(t('Screening of evaluation data.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
