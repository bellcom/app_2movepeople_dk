<?php

namespace Drupal\krisecenter\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Kvinde info entity.
 *
 * @ingroup krisecenter
 *
 * @ContentEntityType(
 *   id = "krisecenter_kvinde_info",
 *   label = @Translation("Kvinde info"),
 *   base_table = "kvinde_info",
 *   translatable = FALSE,
 *   admin_permission = "administer kvinde info entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uid" = "uid",
 *     "basic_info" = "basic_info",
 *   },
 * )
 */
class KvindeInfo extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('integer')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['basic_info'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Kvinde info')
      ->setDefaultValue(NULL);

    return $fields;
  }

  /**
   * Gets entity by user.
   *
   * @param UserInterface $user
   *
   * @return \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|null
   */
  public static function loadByUser(UserInterface $user) {
    $query = \Drupal::entityQuery('krisecenter_kvinde_info');
    $query->condition('uid', $user->id());
    $res = $query->execute();
    if (empty($res)) {
      return $kvindeInfo = KvindeInfo::create(['uid' => $user->id(), 'basic_info' => serialize(KvindeInfo::getBasicInfoDefaultValue())]);
    }

    return KvindeInfo::load(reset($res));
  }

  public function getBasicInfo() {
    return empty($this->basic_info->value) ? $this->getBasicInfoDefaultValue() : unserialize($this->basic_info->value) + $this->getBasicInfoDefaultValue();
  }

  public static function getBasicInfoDefaultValue() {
    return [
      'general' => [
        'name' => '',
        'phone_number' => '',
        'municipality' => '',
        'marital_status' => '',
        'country_of_origin' => '',
        'need_assistance' => '',
        'support_basis' => '',
      ],
      'general_children' => [],
    ];
  }

  public function setBasicInfo($value) {
    $this->basic_info->setValue(serialize($value));
  }
}
