<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
// Use Drupal\node\NodeInterface;.
use Drupal\Core\Url;
// Use Drupal\node\Entity\Node;
// use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
// use Drupal\Core\Ajax\CloseModalDialogCommand;.
use Drupal\Core\Ajax\RedirectCommand;
// Use Drupal\Core\Ajax\ReplaceCommand;.
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;

/**
 * Form to add new user.
 */
class NewUserCreateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_user = \Drupal::currentUser();

    // Loading user templates.
    $conf_object = \Drupal::configFactory()->getEditable(SaveToTemplateForm::$configName);
    $templates = $conf_object->get('template');
    $list[''] = t('none');
    if (empty($templates)) {
      $templates = [];
    }
    foreach ($templates as $user => $template) {
      $list[$user] = $template['template_name'];
    }

    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-user-create-form">';
    $form['#suffix'] = '</div>';

    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => -100,
    ];

    $form['title'] = [
      '#markup' => '<h1 class="page-header">' . $this->t('Create Account') . '</h1>',
    ];

    $form['firstname'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('First Name'),

    ];

    $form['surname'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('Surname'),
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('Username'),
    ];

    $config = \Drupal::config('bc_2movepeople.settings');
    $email_required = $config->get('email_required');

    $form['email'] = [
      '#type' => 'email',
      '#placeholder' => $this->t('Email'),
      '#required' => $email_required,
    ];

    // Get default progression template id.
    $config = \Drupal::config('bc_2movepeople.settings');
    $default_progression_template_id = $config->get('default_progression_template');
    if ($current_user->hasPermission('access category template') && !empty($templates)) {
      $form['template_select'] = [
        '#type' => 'select',
        '#title' => $this->t('User template'),
        '#options' => $list,
        '#default_value' => $default_progression_template_id,
      ];
    }

    $form['password'] = [
      '#type' => 'password',
      '#placeholder' => $this->t('Password'),
      '#size' => 10,
    ];
    $form['password_confirm'] = [
      '#type' => 'password',
      '#placeholder' => $this->t('Confirm Password'),
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
      '#value' => $this->t('Create Account'),
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
      $current_user = \Drupal::currentUser();

      // Mandatory.
      $user->setEmail($form_state->getValue('email'));
      $user->setUsername($form_state->getValue('username'));
      $user->setPassword($form_state->getValue('password'));
      $user->enforceIsNew();

      // Optional.
      $user->set('field_user_firstname', $form_state->getValue('firstname'));
      $user->set('field_user_surname', $form_state->getValue('surname'));

      // Set current user organisations to new user.
      $current_user_obj = User::load($current_user->id());
      $organisations = $current_user_obj->get('field_organisation')->getValue();
      $user->set('field_organisation', $organisations);

      $current_user_roles = $current_user->getRoles();
      if (in_array('administrator', $current_user_roles)) {
        $user->addRole('2mp_supervisor');
      }
      elseif (in_array('2mp_supervisor', $current_user_roles)) {
        $user->addRole('2mp_manager');
      }
      elseif (in_array('2mp_manager', $current_user_roles)) {
        $user->addRole('2mp_user');
      }
      $user->activate();

      // Loading user templates.
      $conf_object = \Drupal::configFactory()->getEditable(SaveToTemplateForm::$configName);
      $template = $conf_object->get('template');
      if (empty($template)) {
        $template = [];
      }
      $template = $template[$form_state->getValue('template_select')];

      // Save user account.
      $this->isSaved = $user->save();

      // Attach category to the user.
      foreach ($template['categories'] as $progression_type => $categories) {
        foreach ($categories as $category) {
          $category_name = $category['title'];
          $category_node = Node::create([
            'type' => 'progression_target',
            'title' => $category_name,
            'field_progression_user' => $user->id(),
            'field_progression_type' => $progression_type,
            'field_goal_ids' => $this->createGoals($category['goals']),
          ]);
          $category_node->save();
        }
      }

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
   * Helper function to create goal nodes.
   *
   * @return array
   *   Array of goal node ids.
   */
  private function createGoals($goals) {
    $result = [];

    foreach ($goals as $goal) {
      if (!empty($goal['subgoals'])) {
        $sub_goal_array = [];
        foreach ($goal['subgoals'] as $subgoal) {
          $sub_goal_node = Node::create([
            'type' => 'goal',
            'title' => $subgoal['title'],
          ]);
          $sub_goal_node->save();
          $sub_goal_array[] = $sub_goal_node->id();
        }
      }
      $goal_node = Node::create([
        'type' => 'goal',
        'title' => $goal['title'],
        'field_subgoal' => $sub_goal_array,
      ]);
      $goal_node->save();
      $result[] = $goal_node->id();
    }
    return $result;
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

    // Check template_select.
    $template_select = CommonFormUtils::cleanInput($form_state->getValue('template_select'));
    if (!is_numeric($template_select) && !empty($template_select)) {
      $form_state->setErrorByName('template_select', $this->t('The Template %template_select is not valid.', ['%template_select' => $template_select]));
    }

    // Check Password.
    $password = CommonFormUtils::cleanInput($form_state->getValue('password'));
    $password_confirm = CommonFormUtils::cleanInput($form_state->getValue('password_confirm'));
    if (strlen($password) < 2 || $password != $password_confirm) {
      $form_state->setErrorByName('password', $this->t('The passwords do not match.'));
    }
  }

}
