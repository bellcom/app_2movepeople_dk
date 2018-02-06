<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople\Form\ConfigForm.
 */

namespace Drupal\bc_2movepeople\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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
      'bc_2movepeople.settings',
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
      ->save();

    parent::submitForm($form, $form_state);
  }

}
