<?php

namespace Drupal\bc_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminSettingsForm.
 *
 * @package Drupal\bc_settings\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bc_settings.AdminSettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bc_settings.AdminSettings');
    $form['demo_textfield'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Demo textfield'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('demo_textfield'),
    ];
    $form['default_textarea'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Demo textarea'),
      '#default_value' => $config->get('demo_textarea'),
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

    $this->config('bc_settings.AdminSettings')
      ->set('demo_textfield', $form_state->getValue('demo_textfield'))
      ->set('default_textarea', $form_state->getValue('default_textarea'))
      ->save();
  }

}
