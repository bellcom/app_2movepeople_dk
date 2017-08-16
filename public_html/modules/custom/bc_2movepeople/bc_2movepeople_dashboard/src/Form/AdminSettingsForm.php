<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminSettingsForm.
 *
 * @package Drupal\bc_2movepeople\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bc_2movepeople_dashboard.AdminSettings',
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
    $config = $this->config('bc_2movepeople_dashboard.AdminSettings');
    
    $form['task_complete_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task complete\'s email subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('task_complete_email_subject'),
    ];
    
    $form['task_complete_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Task complete\'s email body'),
      '#default_value' => $config->get('task_complete_email_body'),
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

    $this->config('bc_2movepeople_dashboard.AdminSettings')
      ->set('task_complete_email_subject', $form_state->getValue('task_complete_email_subject'))
      ->set('task_complete_email_body', $form_state->getValue('task_complete_email_body'))
      ->save();
  }

}