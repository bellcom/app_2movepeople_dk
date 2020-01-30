<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\bc_2movepeople_dashboard\Misc\Utils;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * User managers edit form.
 */
class UserManagersEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $form_state->set('user', $user);

    $this->return_url = \Drupal::request()->query->get('return_url');

    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-user-managers-edit-form">';
    $form['#suffix'] = '</div>';

    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => -100,
    ];

    $managers = $this->getManagers($user);
    $options = [];
    if (!empty($managers)) {
      foreach ($managers as $user_manager) {
        $options[$user_manager->id()] = CommonFormUtils::getUserName($user_manager);
      }
    }

    $user_managers_ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', '2mp_manager')
      ->condition('field_connected_users', $user->id(), 'CONTAINS')
      ->execute();

    $default_value = [];
    if (!empty($user_managers_ids)) {
      foreach ($user_managers_ids as $uid) {
        $default_value[$uid] = $uid;
      }
    }

    // By default tasks assigned to current user (empty value).
    // User managers can be responsible for tasks also.
    $form['managers'] = [
      '#type' => 'checkboxes',
      '#title' => t('Managers'),
      '#required' => FALSE,
      '#empty_option' => CommonFormUtils::getUserName(User::load($user->id())),
      '#options' => $options,
      '#default_value' => $default_value,
    ];
    $form_state->set('managers', $default_value);

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Save'),
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
    return 'bc_2movepeople-dashboard-user-managers-edit-form';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $managers = array_filter($values['managers'], function ($value) {
      return !empty($value);
    });
    $old_managers_value = $form_state->get('managers');

    if ($managers == $old_managers_value) {
      $form_state->setErrorByName('managers', $this->t('No changes to save.'));
    }
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function ajaxSubmitForm(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
    ];

    if (!$form_state->hasAnyErrors()) {
      $ajax_response->addCommand(new CloseModalDialogCommand());
      $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $message));
    }
    else {
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $message));
    }

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $user = $form_state->get('user');
    $managers = $this->getManagers($user);

    if (!empty($values['managers']) && !empty($managers)) {
      $managers = $values['managers'];
      foreach ($this->getManagers($user) as $manager) {
        $connected_users_values = $manager->field_connected_users->getValue();
        // Remove current user from field_connected_users values.
        if (empty($managers[$manager->id()])) {
          foreach ($connected_users_values as $key => $value) {
            if ($value['target_id'] == $user->id()) {
              unset($manager->field_connected_users[$key]);
              break;
            }
          }
        }
        // Add current user to field_connected_users values.
        else {
          $to_add = TRUE;
          foreach ($connected_users_values as $key => $value) {
            if ($value['target_id'] == $user->id()) {
              $to_add = FALSE;
              break;
            }
          }
          if ($to_add) {
            $manager->field_connected_users[] = ['target_id' => $user->id()];
          }
        }

        $manager->save();
      }
    }

    drupal_set_message($this->t('Managers have been upated'));
  }

  /**
   * Helper function to get all managers.
   *
   * @return array
   *   Array of user entities.
   */
  private static function getManagers(UserInterface $user = NULL) {
    $organization_tids = Utils::getUserOrganizations($user);
    // Get all managers of target user.
    $managers_ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', '2mp_manager')
      ->condition('field_organisation', $organization_tids, 'IN')
      ->execute();
    return User::loadMultiple($managers_ids);
  }

}
