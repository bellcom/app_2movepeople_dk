<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\user\UserInterface;

/**
 * Form to edit user.
 */
class UserEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $form_state->set('user', $user);
    $form_state->setCached(FALSE);

    $config = \Drupal::config('bc_2movepeople.settings');

    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-user-edit-form">';
    $form['#suffix'] = '</div>';

    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => -100,
    ];

    // SSN.
    $form['social_security_number'] = [
      '#title' => $this->t('Social security number'),
      '#type' => 'textfield',
      '#default_value' => $user->get('field_social_security_number')->value,
    ];

    // Firstname.
    $form['firstname'] = [
      '#title' => $this->t('Firstname'),
      '#type' => 'textfield',
      '#default_value' => $user->get('field_user_firstname')->value,
    ];

    // Lastname.
    $form['surname'] = [
      '#title' => $this->t('Surname'),
      '#type' => 'textfield',
      '#default_value' => $user->get('field_user_surname')->value,
    ];

    // Username.
    $form['username'] = [
      '#title' => $this->t('Username'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $user->get('name')->value,
    ];

    // E-mail address.
    $email_required = $config->get('email_required');
    $form['email'] = [
      '#title' => $this->t('Email'),
      '#type' => 'email',
      '#required' => $email_required,
      '#default_value' => $user->get('mail')->value,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => [
        'class' => ['btn-submit-default'],
      ],
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
    return 'bc_2movepeople-dashboard-user-edit-form';
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
    if (!$form_state->getErrors()) {
      $user = $form_state->get('user');

      // Mandatory.
      $user->setEmail($form_state->getValue('email'));
      $user->setUsername($form_state->getValue('username'));

      // Optional.
      $user->set('field_social_security_number', $form_state->getValue('social_security_number'));
      $user->set('field_user_firstname', $form_state->getValue('firstname'));
      $user->set('field_user_surname', $form_state->getValue('surname'));

      // Update user account.
      $user->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->clearErrors();
    $user = $form_state->get('user');

    // Check Firstname.
    $firstname = CommonFormUtils::cleanInput($form_state->getValue('firstname'));
    if (strlen($firstname) < 2) {
      $form_state->setErrorByName('firstname', $this->t('The First Name %firstname is not valid.', ['%firstname' => $firstname]));
    }

    // Check Lastname.
    $surname = CommonFormUtils::cleanInput($form_state->getValue('surname'));
    if (strlen($surname) < 2) {
      $form_state->setErrorByName('surname', $this->t('The Surname %surname is not valid.', ['%surname' => $surname]));
    }

    // Check Username.
    $username = CommonFormUtils::cleanInput($form_state->getValue('username'));
    if (strlen($username) < 2) {
      $form_state->setErrorByName('username', $this->t('The Username %username is not valid.', ['%username' => $username]));
    }
    if ($user->get('name')->value !== $username) {
      if (!empty(user_load_by_name($username))) {
        $form_state->setErrorByName('username',
          $this->t('The Username %username already exists.',
            ['%username' => $username]));
      }
    }

    // Check Email.
    $email = CommonFormUtils::cleanInput(trim($form_state->getValue('email')));
    if ($form['email']['#required_but_empty']) {
      $form_state->setErrorByName('email', $this->t('The Email address is required'));
    }
    elseif (!\Drupal::service('email.validator')->isValid($email) and !empty($email)) {
      $form_state->setErrorByName('email', $this->t('The Email address %mail is not valid.', ['%mail' => $email]));
    }
    if ($user->get('mail')->value !== $email) {
      if (!empty(user_load_by_mail($email))) {
        $form_state->setErrorByName('email', $this->t('The Email address %mail already exists.', ['%mail' => $email]));
      }
    }
  }

}
