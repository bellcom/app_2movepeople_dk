<?php

namespace Drupal\bc_2movepeople_dashboard\Plugin\views\area;

use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Views area create button handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("bc_2movepeople_dashboard_create_button")
 */
class CreateButton extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['wrapper_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper classes'),
      '#default_value' => isset($this->options['wrapper_classes']) ? $this->options['wrapper_classes'] : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['wrapper_classes'] = ['default' => ''];

    return $options;
  }
  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $argument = reset($this->view->argument);
    $user = NULL;
    $buttons = [];
    if (!empty($argument) && !empty($argument->getValue())) {
      $user = User::load($argument->getValue());
    }
    MovepeopleDashboardController::addCreateButton($buttons, $user);
    $build['wrapper'] = [
      '#type' => 'container',
      'buttons' => MovepeopleDashboardController::getControlButtons($buttons, ['class' => ['create-user-view-area']]),
    ];
    if (!empty($this->options['wrapper_classes'])) {
      $build['wrapper']['#attributes']['class'] = $this->options['wrapper_classes'];
    }
    return $build;
  }

}
