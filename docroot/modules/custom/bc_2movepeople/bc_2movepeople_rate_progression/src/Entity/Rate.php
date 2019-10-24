<?php

namespace Drupal\bc_2movepeople_rate_progression\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Rate entity.
 *
 * @ingroup rate
 *
 * @ContentEntityType(
 *   id = "rate",
 *   label = @Translation("Progression rate"),
 *   base_table = "bc_2movepeople_rate_progression",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "progression_target_id" = "progression_target_id",
 *     "goal_id" = "goal_id",
 *     "rate" = "rate",
 *     "uid" = "uid",
 *     "created" = "created",
 *     "rate_autor" = "rate_autor",
 *     "status" = "status",
 *     "meeting_id" = "meeting_id"
 *   },
 *   list_cache_tags = { "config:rate" }
 * )
 */
class Rate extends ContentEntityBase implements RateInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('id'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['progression_target_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Progression target id'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['goal_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Goal id'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['rate'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Goal rate'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Rated user id'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Created timestamp'))
      ->setDefaultValue(NULL);

    $fields['rate_autor'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Author user id'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Goal rate'))
      ->setDefaultValue(TRUE);

    $fields['meeting_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Meeting id'))
      ->setDefaultValue(NULL);

    return $fields;
  }

}
