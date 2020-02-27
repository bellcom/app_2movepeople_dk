<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminButtonsTitle.
 *
 * @package Drupal\bc_2movepeople\Form
 */
class AdminButtonsTitle extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bc_2movepeople_dashboard.admin_buttons_title',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_buttons_title';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('bc_2movepeople_dashboard.admin_buttons_title');
    $form['#tree'] = TRUE;
    foreach (_bc_2movepeople_dashboard_buttons() as $section => $buttons) {
      $form[$section] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t($section),
      ];
      foreach ($buttons as $key => $button) {
        $form[$section][$key] = [
          '#type' => 'textfield',
          '#title' => $button,
          '#default_value' => $config->get($section . '.' . $key),
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('bc_2movepeople_dashboard.admin_buttons_title');
    foreach (_bc_2movepeople_dashboard_buttons() as $section => $buttons) {
      foreach ($buttons as $key => $button) {
        $config->set($section . '.' . $key, $values[$section][$key]);
      }
    }
    $config->save();
  }

}
