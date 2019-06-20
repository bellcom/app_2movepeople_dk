<?php

namespace Drupal\sbsys_integration\Plugin\XmlDataProvider;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sbsys_integration\Plugin\XmlDataProviderBase;
use Drupal\sbsys_integration\XmlHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @XmlDataProvider(
 *   id = "entity_values_data_provider",
 *   label = @Translation("Values from  entity"),
 * )
 */
class EntityValuesDataProvider extends XmlDataProviderBase {

  use DependencySerializationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $plugin_settings = [];
    $entity_type = $form_state->getValue([self::getPluginId(), 'entity_type']);
    $bundles = $this->entityBundleInfo->getAllBundleInfo();

    if (empty($form_state->getValue([self::getPluginId(), 'entity_type']))) {
      $entity_type = $this->configuration['entity_type'];;
    }
    if ($entity_type == 'user') {
      $entity_bundle = 'user';
    }
    else {
      $entity_bundle = $this->configuration['entity_bundle'];
    }
    $definitions = ['' => t('None')];
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition instanceof ContentEntityTypeInterface && !empty($bundles[$definition->id()])) {
        $definitions[$definition->id()] = $definition->getLabel();
      }
    }
    $plugin_settings['entity_type'] = array(
      '#type' => 'select',
      '#title' => t('Entity type'),
      '#options' => $definitions,
      '#default_value' => isset($entity_type) ? $entity_type : NULL,
      '#ajax' => [
        'callback' => [$this,'updateEntityType'],
        'wrapper' => ['entity-bundle-wrapper'],
      ],
    );

    $plugin_settings['entity_bundle'] = [
      '#markup' => '<div id="entity-bundle-wrapper"></div>',
    ];
    if (!empty($entity_type) && count($bundles[$entity_type]) > 1) {
      $options = ['none' => t('None')];
      foreach ($bundles[$entity_type] as $bundle_key => $bundle) {
        $options[$bundle_key] = $bundle['label'];
      }
      $plugin_settings['entity_bundle'] = [
        '#type' => 'select',
        '#title' => t('Entity bundle'),
        '#options' => $options,
        '#default_value' => isset($entity_bundle) ? $entity_bundle : NULL,
        '#prefix' => '<div id="entity-bundle-wrapper">',
        '#suffix' => '</div>',
      ];
    }
    else {
      $plugin_settings['entity_bundle'] = [
        '#type' => 'hidden',
        '#default_value' => empty($bundles[$entity_type]) ? NULL : reset(array_keys($bundles[$entity_type])),
      ];
    }

    $plugin_settings['divider'] = ['#markup' => '<hr/>'];

    if (!empty($entity_bundle)) {
      $options = ['' => t('None')];
      $allowedEntityProperties = $this->allowedEntityProperties();
      foreach ($this->entityFieldManager->getFieldDefinitions($entity_type, $entity_bundle) as $key => $field) {
        if (strpos($field->getName(), 'field_') !== FALSE
          || in_array($key, empty($allowedEntityProperties[$entity_type]) ? [] : $allowedEntityProperties[$entity_type])) {
          $options[$key] = $field->getLabel();
        }
      }

      $options += ['custom' => t('Custom')];
      foreach (XmlHandler::dataDefault() as $key => $value) {
        $plugin_settings[$key] = array(
          '#type' => 'select',
          '#title' => t('Get @key value from field or property.', array('@key' => $key)),
          '#options' => $options,
          '#default_value' => isset($this->configuration[$key]) ? $this->configuration[$key] : NULL,
          '#description' => t('Select a value from form submitted fields or provide a custom static value'),
        );

        $plugin_settings[$key . '_custom'] = [
          '#type' => 'textarea',
          '#rows' => 2,
          '#title' => $key,
          '#default_value' => isset($this->configuration[$key . '_custom']) ? $this->configuration[$key . '_custom'] : $value,
          '#states' =>  [
            'visible' => [
              ':input[name="' .  $this->getPluginId() . '[' . $key . ']"]' => array('value' => 'custom'),
            ],
          ]
        ];
      }
    }

    return $plugin_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataKey($key) {
    if ($this->configuration[$key] == 'custom') {
      return isset($this->configuration[$key . '_custom']) ? $this->configuration[$key . '_custom'] : NULL;
    }

    $entity_type = $this->configuration['entity_type'];
    $entity_bundle = $this->configuration['entity_bundle'];

    $entity = $this->getContextValue($entity_type);
    if (empty($entity)
      || !method_exists($entity, 'bundle')
      || $entity->bundle() != $entity_bundle
      || !$entity->hasField($this->configuration[$key])) {
      return NULL;
    }

    $field = $entity->get($this->configuration[$key]);
    if (empty($field) || empty($field->getValue())) {
      return NULL;
    }

    return is_array($field->value) ? $field->value[0] : $field->value;
  }

  /**
   * Ajax callback for the EntityType dropdown.
   */
  public static function updateEntityType(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    return $form['entity_values_data_provider']['entity_bundle'];
  }

  /**
   * List of entity properties allowed to use for plugin settings.
   *
   * Each entity should be keyed as sub array.
   */
  private function allowedEntityProperties() {
    return [
      'user' => ['name', 'mail'],
    ];
  }

}
