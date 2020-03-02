<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople\Form\ConfigForm.
 */

namespace Drupal\bc_2movepeople\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bc_2movepeople_dashboard\Form\SaveToTemplateForm;

/**
 * Class ConfigForm.
 *
 * @package Drupal\bc_2movepeople\Form
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::getConfigName(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function getConfigName() {
    return 'bc_2movepeople.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bc_2movepeople.settings');

    $form['functionality'] = array(
      '#type' => 'details',
      '#title' => $this->t('Available functionality'),
      '#open' => TRUE,
    );

    $form['functionality']['enable_milestones'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable milestones/tasks'),
      '#default_value' => $config->get('enable_milestones'),
    ];

    $form['functionality']['email_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User email is required'),
      '#default_value' => $config->get('email_required'),
    ];

    $form['functionality']['rates_separately'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show feedback rates and progressions rates separately'),
      '#default_value' => $config->get('rates_separately'),
    ];

    // Loading user templates.
    $conf_object = \Drupal::configFactory()->getEditable(SaveToTemplateForm::$configName);
    $templates = $conf_object->get('template');
    $list[''] = t('none');
    if (empty($templates)) {
      $templates = array();
    }
    foreach ($templates as $user => $template) {
      $list[$user] = $template['template_name'];
    }
    $form['functionality']['default_progression_template'] = [
      '#type' => 'select',
      '#title' => $this->t('New users default progression template'),
      '#options' => $list,
      '#default_value' => $config->get('default_progression_template'),
    ];

    // User.
    $user_settings = \Drupal::config('user.settings');
    $anonymous_name = $user_settings->get('anonymous');

    $form['user'] = array(
      '#type' => 'details',
      '#title' => $this->t('User handling'),
      '#open' => TRUE,
    );

    // User cancellation.
    $form['user']['user_cancel_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('When cancelling the account'),
      '#default_value' => $config->get('user_cancel_method') ? $config->get('user_cancel_method') : 'user_cancel_delete',
      '#options' => [
        'user_cancel_block' => t('Disable the account and keep its content.'),
        'user_cancel_block_unpublish' => t('Disable the account and unpublish its content.'),
        'user_cancel_reassign' => t('Delete the account and make its content belong to the %anonymous-name user.', ['%anonymous-name' => $anonymous_name]),
        'user_cancel_delete' => t('Delete the account and its content.'),
      ]
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('bc_2movepeople.settings')
      ->set('enable_milestones', $form_state->getValue('enable_milestones'))
      ->set('email_required', $form_state->getValue('email_required'))
      ->set('rates_separately', $form_state->getValue('rates_separately'))
      ->set('default_progression_template', $form_state->getValue('default_progression_template'))
      ->set('user_cancel_method', $form_state->getValue('user_cancel_method'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
