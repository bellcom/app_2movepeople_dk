<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\user\Entity\User;

/**
 * Abstract class for the add new user form.
 */
abstract class NewUserCreateFormBase extends FormBase {

  /**
   * Boolean flag that stores state of user.
   *
   * @var bool $isSaved
   */
  protected $isSaved;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();

    // Allow supervisor create user for managers.
    $create_for = \Drupal::request()->query->get('create-for');
    if (in_array('2mp_supervisor', $current_user->getRoles())
      && !empty($create_for)
      && $create_for_user = User::load($create_for)) {
      $current_user = $create_for_user;
    }
    $form_state->set('current_user', $current_user);

    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-user-create-form">';
    $form['#suffix'] = '</div>';

    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => -100,
    ];

    $form['social_security_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Social security number'),
    ];

    $form['firstname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Firstname'),
    ];

    $form['surname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Surname'),
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
    ];

    $config = \Drupal::config('bc_2movepeople.settings');
    $email_required = $config->get('email_required');

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => $email_required,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 10,
    ];
    $form['password_confirm'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirm Password'),
      '#size' => 10,
    ];

    // Disable caching on this form.
    $form_state->setCached(FALSE);

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
    return 'bc_2movepeople-dashboard-user-create-form';
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function ajaxSubmitForm(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    if ($this->isSaved == SAVED_NEW) {
      $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('<front>')->toString()));
    }
    else {
      $message = [
        '#theme' => 'status_messages',
        '#message_list' => drupal_get_messages(),
      ];
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $message));
    }

    return $ajax_response;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (!$form_state->getErrors()) {
      $user = User::create();
      $form_state->set('new_user', $user);
      $current_user = $form_state->get('current_user');

      // Mandatory.
      $user->setEmail($form_state->getValue('email'));
      $user->setUsername($form_state->getValue('username'));
      $user->setPassword($form_state->getValue('password'));
      $user->enforceIsNew();

      // Optional.
      $user->set('field_social_security_number', $form_state->getValue('social_security_number'));
      $user->set('field_user_firstname', $form_state->getValue('firstname'));
      $user->set('field_user_surname', $form_state->getValue('surname'));

      // Set current user organisations to new user.
      $current_user_obj = User::load($current_user->id());
      $organisations = $current_user_obj->get('field_organisation')->getValue();
      $user->set('field_organisation', $organisations);

      $new_user_role = $this->getNewUserRole($current_user->getRoles());
      if ($new_user_role) {
        $user->addRole($new_user_role);
      }
      $user->activate();

      // Save user account.
      $this->isSaved = $user->save();

      if ($this->isSaved != SAVED_NEW) {
        drupal_set_message($this->wrong_msg, 'error');
      }
      else {
        // If user saved, update current user.
        $current_user = User::load($current_user->id());
        $current_user->field_connected_users[] = $user;
        $current_user->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->clearErrors();

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
    if (!empty(user_load_by_name($username))) {
      $form_state->setErrorByName('username', $this->t('The Username %username already exists.', ['%username' => $username]));
    }

    // Check Email.
    $email = CommonFormUtils::cleanInput(trim($form_state->getValue('email')));
    if ($form['email']['#required_but_empty']) {
      $form_state->setErrorByName('email', $this->t('The Email address is required'));
    }
    elseif (!\Drupal::service('email.validator')->isValid($email) and !empty($email)) {
      $form_state->setErrorByName('email', $this->t('The Email address %mail is not valid.', ['%mail' => $email]));
    }
    if (!empty(user_load_by_mail($email))) {
      $form_state->setErrorByName('email', $this->t('The Email address %mail already exists.', ['%mail' => $email]));
    }

    // Check Password.
    $password = CommonFormUtils::cleanInput($form_state->getValue('password'));
    $password_confirm = CommonFormUtils::cleanInput($form_state->getValue('password_confirm'));
    if (strlen($password) < 2 || $password != $password_confirm) {
      $form_state->setErrorByName('password', $this->t('The passwords do not match.'));
    }
  }

  protected function getNewUserRole($current_user_roles) {
    if (in_array('administrator', $current_user_roles)
      || in_array('2mp_admin', $current_user_roles)) {
      return '2mp_supervisor';
    }
    elseif (in_array('2mp_supervisor', $current_user_roles)) {
      return '2mp_manager';
    }
    else {
      return '2mp_user';
    }
  }
}
