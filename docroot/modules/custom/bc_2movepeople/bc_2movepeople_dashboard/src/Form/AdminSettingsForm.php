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
    
    //Complete email options
    $form['complete_email_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Complete email options'),
    //  '#description' => '',
      '#open' => TRUE,
    );
    
    $form['complete_email_options']['task_complete_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task complete\'s email subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('task_complete_email_subject'),
    ];
    
    $form['complete_email_options']['task_complete_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Task complete\'s email body'),
      '#default_value' => $config->get('task_complete_email_body'),
      '#description' => '
        @name = '.$this->t('The name of the user who will get this email').'<br />
        @user = '.$this->t('The name of the user that finished the task').'<br />
        @task_title  = '.$this->t('The title of the task that is being complete')
    ];
    
    //Email options of responsible manager notification
    $form['responsible_manager_email_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Email options of responsible manager notification'),
    //  '#description' => '',
      '#open' => TRUE,
    );
    
    $form['responsible_manager_email_options']['responsible_manager_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Responsible manager\'s email subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('responsible_manager_email_subject'),
    ];
    
    $form['responsible_manager_email_options']['responsible_manager_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Responsible manager\'s email body'),
      '#default_value' => $config->get('responsible_manager_email_body'),
      '#description' => '
        @manager = '.$this->t('The name of the manager who will get this email').'<br />
        @user = '.$this->t('The name of the user').'<br />
        @task_title  = '.$this->t('The name of the task assigned to manager')
    ];
    
    //Reminder options
    $form['reminder_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Reminder options'),
    //  '#description' => '',
      '#open' => FALSE,
    );

    $form['reminder_options']['task_reminder_due_date'] = [
      '#type' => 'textfield',
      '#maxlength' => 2,
      '#size' => 2,
      '#title' => $this->t('Task reminder due date'),
      '#default_value' => $config->get('task_reminder_due_date'),
      '#description' => 'Allowable value is integer: 1, 2, .. n'.'<br />
        Value define the number of days.'
    ];
    
    $form['reminder_options']['task_reminder_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task reminder\'s email subject'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('task_reminder_email_subject'),
    ];
    
    $form['reminder_options']['task_reminder_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Task reminder\'s email body'),
      '#default_value' => $config->get('task_reminder_email_body'),
      '#description' => '
        @name = '.$this->t('The name of the user who will get this email').'<br />
        @task_title  = The titles of the tasks ...........'.'<br />
        @due_date  = Task due date.'
    ];

    // Milestone evaluation options.
    $form['milestone_evaluation'] = [
      '#type' => 'details',
      '#title' => $this->t('Milestone evaluation options'),
      '#open' => TRUE,
    ];

    $header_node_reference = NULL;
    if (!empty($config->get('milestone_evaluation_header_nid'))) {
      $header_node_reference = \Drupal::entityTypeManager()->getStorage('node')
        ->load($config->get('milestone_evaluation_header_nid'));
    }
    $form['milestone_evaluation']['milestone_evaluation_header_nid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Node reference to PDF Milestone evaluation header'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $header_node_reference,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    
    // check task_reminder_due_date
    if ($form_state->getValue('task_reminder_due_date')) {
      $task_reminder_due_date = (integer)CommonFormUtils::cleanInput($form_state->getValue('task_reminder_due_date'));
      $form_state->setValue('task_reminder_due_date', $task_reminder_due_date);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('bc_2movepeople_dashboard.AdminSettings')
      ->set('task_complete_email_subject', $form_state->getValue('task_complete_email_subject'))
      ->set('task_complete_email_body', $form_state->getValue('task_complete_email_body'))
      ->set('task_reminder_due_date', $form_state->getValue('task_reminder_due_date'))
      ->set('task_reminder_email_subject', $form_state->getValue('task_reminder_email_subject'))
      ->set('task_reminder_email_body', $form_state->getValue('task_reminder_email_body'))
      ->set('responsible_manager_email_subject', $form_state->getValue('responsible_manager_email_subject'))
      ->set('responsible_manager_email_body', $form_state->getValue('responsible_manager_email_body'))
      ->set('milestone_evaluation_header_nid', $form_state->getValue('milestone_evaluation_header_nid'))
      ->save();
  }

}
