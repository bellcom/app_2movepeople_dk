<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\bc_2movepeople_dashboard\Misc\Utils;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Url;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
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


    $organisation_tids = Utils::getUserOrganizations(User::load(\Drupal::currentUser()->id()));
    $terms = Term::loadMultiple($organisation_tids);
    $options = [];
    foreach ($terms as $term) {
      $options[$term->id()] = $term->label();
    }

    $form['organisations'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Organisations'),
      '#options' => $options,
      '#default_value' => Utils::getUserOrganizations($user),
      '#required' => TRUE,
      '#size' => 10,
    ];

    $form['edit_actions'] = [
      '#type' => 'radios',
      '#options' => [
        'no_actions' => $this->t('No actions'),
        'send_login_url' => $this->t('Send user email with one-time login link'),
        'reset_password' => $this->t('Reset user password'),
      ],
      '#default_value' => 'no_actions',
      'send_login_url' => [
        '#states' => [
          'disabled' => [
            'input[name=email]' => ['empty' => TRUE],
          ],
        ],
      ],
    ];

    $password_states = [
      'visible' => [
        'input[name=edit_actions]' => ['value' => 'reset_password'],
      ],
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 10,
      '#states' => $password_states,
    ];

    $form['password_confirm'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirm Password'),
      '#size' => 10,
      '#states' => $password_states,
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

    // Success.
    if (!$form_state->hasAnyErrors()) {
      $url = Url::fromRoute('bc_2movepeople_dashboard.main');
      // Reload page.
      $ajax_response->addCommand(new RedirectCommand($url->toString()));
    }

    // Errors.
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
      $user = $form_state->get('user');

      // Mandatory.
      $user->setEmail($form_state->getValue('email'));
      $user->setUsername($form_state->getValue('username'));

      // Optional.
      $user->set('field_social_security_number', $form_state->getValue('social_security_number'));
      $user->set('field_user_firstname', $form_state->getValue('firstname'));
      $user->set('field_user_surname', $form_state->getValue('surname'));

      switch ($form_state->getValue('edit_actions')) {
        case 'reset_password':
          $user->setPassword($form_state->getValue('password'));
          break;

        case 'send_login_url':
          $mail = _user_mail_notify('password_reset', $user);
          if (!empty($mail)) {
            $message = $this->t('Password reset instructions mailed to %name at %email.', [
              '%name' => $user->getAccountName(),
              '%email' => $user->getEmail(),
            ]);
            $this->logger('user')->notice($message);
            $this->messenger()->addStatus($message);
          }
          else {
            $this->messenger()->addWarning($this->t('Password reset message was not sent. Contact administrator to check email settings.'));
          }
          break;
      }

      $field_organisation = $user->field_organisation;
      $current_user_organisation_tids = Utils::getUserOrganizations(User::load(\Drupal::currentUser()->id()));
      $organisation_tids = array_filter($form_state->getValue('organisations'));
      $new_field_organisation = [];
      // Remove unchecked organisations.
      foreach($field_organisation as $delta => $item) {
        $value = $item->getValue();
        $tid = $value['target_id'];
        // Skip if current user don't allowed to edit organisation.
        if (array_search($tid, $current_user_organisation_tids) === FALSE) {
          $new_field_organisation[] = ['target_id' => $tid];
          continue;
        }

        // Skip if user has organisation as checked.
        if ($key = array_search($tid, $organisation_tids)) {
          unset($organisation_tids[$key]);
          $new_field_organisation[] = ['target_id' => $tid];
        }
      }

      // Add new organisations.
      foreach ($organisation_tids as $tid) {
        $new_field_organisation[] = ['target_id' => $tid];
      }

      $user->field_organisation = $new_field_organisation;

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

    // Check Password.
    if ($form_state->getValue('edit_actions') == 'reset_password') {
      $password = CommonFormUtils::cleanInput($form_state->getValue('password'));
      $password_confirm = CommonFormUtils::cleanInput($form_state->getValue('password_confirm'));
      if (strlen($password) < 2 || $password != $password_confirm) {
        $form_state->setErrorByName('password', $this->t('The passwords do not match.'));
      }
    }
  }

}
