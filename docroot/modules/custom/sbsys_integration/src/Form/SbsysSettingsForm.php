<?php

namespace Drupal\sbsys_integration\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sbsys_integration\Plugin\XmlDataProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SbsysSettingsForm.
 */
class SbsysSettingsForm extends ConfigFormBase {

  use DependencySerializationTrait;

  /** @var PluginManagerInterface $dataProvider */
  private $pluginManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param PluginManagerInterface $plugin_manager
   *   Plugin manager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $plugin_manager) {
    parent::__construct($config_factory);
    $this->pluginManager = $plugin_manager;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.xml_data_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $config_names_array = [
      'sbsys_integration.settings',
    ];
    foreach ($this->pluginManager->getDefinitions() as $plugin_definition) {
      $config_names_array[] = XmlDataProviderBase::getEditablePluginConfigName($plugin_definition['id']);
    }
    return $config_names_array;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sbsys_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sbsys_integration.settings');

    $plugins = [
      '' => $this->t('None'),
    ];
    foreach ($this->pluginManager->getDefinitions() as $plugin_definition) {
      $plugins[$plugin_definition['id']] = $plugin_definition['label'];
      $plugin_config_name = XmlDataProviderBase::getEditablePluginConfigName($plugin_definition['id']);
      $plugin = $this->pluginManager->createInstance($plugin_definition['id'], $this->config($plugin_config_name)->get());
      $settings = $plugin->settingsForm($form, $form_state);
      if (!empty($settings)) {
        $form[$plugin_definition['id']] = array_merge_recursive([
          '#type' => 'details',
          '#title' => $plugin_definition['label'],
          '#open' => $config->get('xml_data_provider_plugin_id') == $plugin_definition['id'],
          '#tree' => TRUE,
        ], $settings);
      }
    }
    $form['xml_data_provider_plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('SBSYS XML Data Provider plugin'),
      '#description' => $this->t('Specify xml data provider plugin.'),
      '#options' => $plugins,
      '#default_value' => $config->get('xml_data_provider_plugin_id'),
      '#weight' => -1,
    ];
    
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('sbsys_integration.settings');
    $config->set('xml_data_provider_plugin_id', $form_state->getValue('xml_data_provider_plugin_id'))
      ->save();

    foreach ($this->pluginManager->getDefinitions() as $plugin_definition) {
      $settings = $form_state->getValue($plugin_definition['id']);
      if (!empty($settings)) {
        $plugin_config = $this->config(XmlDataProviderBase::getEditablePluginConfigName($plugin_definition['id']));
        foreach ($settings as $key => $value) {
          $plugin_config->set($key, $value)->save();
        }
      }
    }
  }

}
