<?php

namespace Drupal\bc_2movepeople\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TextSettings.
 *
 * @package Drupal\bc_2movepeople\Form
 */
class TextSettings extends ConfigFormBase {

  public static $config_name = 'bc_2movepeople.text_settings';
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::$config_name,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople_text_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Text fetcher from configuration.
   */
  public static function get($key) {
    $config = \Drupal::config(self::$config_name);
    return $config->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::$config_name);
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'bc_2movepeople') !== 0) {
        continue;
      }
      $config->set($key, $value);
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
