<?php

namespace Drupal\sbsys_integration\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for SBSYS XML Data Provider plugins.
 */
interface XmlDataProviderInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Returns a form to configure settings for the plugin.
   *
   * Invoked from \Drupal\sbsys_integration\Form\SbsysSettingsForm to allow
   * administrators to configure the plugin.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form elements for the plugin settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Declares plugin config name.
   */
  public static function getEditablePluginConfigName($plugin_id);

  /**
   * Returns a values from data array by key.
   *
   * @param string $key
   *   The form where the settings form is being included in.
   *
   * @return mixed
   *   Value for requested key.
   */
  public function getDataKey($key);

  /**
   * Sets the values for context.
   *
   * @param array $values
   *   An array of new context values.
   */
  public function setContextValues(array $values);

  /**
   * Sets the value for a defined context.
   *
   * @param string $name
   *   The name of the context in the plugin definition.
   * @param mixed $value
   *   The value to set the context to. The value has to validate against the
   *   provided context definition.
   */
  public function setContextValue($name, $value);

  /**
   * Gets the values for all defined contexts.
   *
   * @return array
   *   An array of set context values, keyed by context name.
   */
  public function getContextValues();

  /**
   * Gets the value for a defined context.
   *
   * @param string $name
   *   The name of the context in the plugin configuration.
   *
   * @return mixed
   *   The currently set context value.
   */
  public function getContextValue($name);

}
