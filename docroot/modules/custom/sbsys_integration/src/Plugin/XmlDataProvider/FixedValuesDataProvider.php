<?php

namespace Drupal\sbsys_integration\Plugin\XmlDataProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sbsys_integration\Plugin\XmlDataProviderBase;
use Drupal\sbsys_integration\XmlHandler;

/**
 * @XmlDataProvider(
 *   id = "fixed_values_data_provider",
 *   label = @Translation("Fixed values for XML."),
 * )
 */
class FixedValuesDataProvider extends XmlDataProviderBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $plugin_settings = [];
    foreach (XmlHandler::dataDefault() as $key => $value) {
      $plugin_settings[$key] = [
        '#type' => 'textfield',
        '#title' => $key,
        '#default_value' => isset($this->configuration[$key]) ? $this->configuration[$key] : $value,
      ];
    }
    return $plugin_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataKey($key) {
    return isset($this->configuration[$key]) ? $this->configuration[$key] : NULL;
  }

}
