<?php

namespace Drupal\sbsys_integration\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for SBSYS XML Data Provider plugins.
 */
abstract class XmlDataProviderBase extends PluginBase implements XmlDataProviderInterface {
  
  /**
   * The context for the plugin.
   *
   * @var array
   */
  public $context = [];

  /**
   * Plugin data.
   *
   * @var array $data
   */
  protected $data;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getEditablePluginConfigName($plugin_id) {
    return 'sbsys_integration.' . $plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataKey($key) {
    return isset($this->data[$key]) ? $this->data[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setContextValue($name, $value) {
    $this->context[$name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setContextValues(array $values) {
    $this->context = array_merge_recursive($this->context, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getContextValues() {
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextValue($name) {
    return isset($this->context[$name]) ? $this->context[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

}
