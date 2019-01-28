<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * User managers edit form.
 */
class UserCancelForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $form_state->set('user', $user);

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Are you sure that you want to cancel the user?'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Remove'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-user-cancel-form';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function ajaxSubmitForm(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
    ];

    // Success.
    if (!$form_state->hasAnyErrors()) {
      $url = Url::fromRoute('bc_2movepeople_dashboard.main');

      // Reload page.
      $ajax_response->addCommand(new RedirectCommand($url->toString()));
    }

    // Errors.
    else {
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $message));
    }

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $form_state->get('user');

    // Get settings from our 2movepeople settings.
    $config = $this->config('bc_2movepeople.settings');
    $user_cancel_method = $config->get('user_cancel_method');
    $edit = [
      'user_cancel_notify' => false,
    ];

    if ($user_cancel_method != 'user_cancel_delete') {
      // Allow modules to add further sets to this batch.
      \Drupal::moduleHandler()->invokeAll('user_cancel', [$edit, $user, $user_cancel_method]);
    }

    // Cancel the user.
    _user_cancel($edit, $user, $user_cancel_method);
  }
}
