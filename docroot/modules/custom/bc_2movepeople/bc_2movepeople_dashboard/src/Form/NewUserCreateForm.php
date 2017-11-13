<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\ProgressionTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
//use Drupal\node\NodeInterface;
use Drupal\Core\Url;
//use Drupal\node\Entity\Node;
//use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
//use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
//use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\user\Entity\User;

class NewUserCreateForm extends FormBase {

  protected $isSaved;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    //$goals_options = MovepeopleDashboardController::getProgressionGoalsList($this->parent_node);  
    
    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-user-create-form">';
    $form['#suffix'] = '</div>';
    
    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => -100,
    ];
    
    $form['title'] = [
      '#markup' => '<h1 class="page-header">'.$this->t('Create Account').'</h1>'
    ];

    $form['firstname'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('First Name'),
      //'#required' => TRUE,
//      '#ajax' => [
//        'callback' => '::ajaxFullnameValidate',
//        'event' => 'blur',
//        'progress' => ['type' => 'none', 'message' => NULL],
//      ],
    ];
    
    $form['surname'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('Surname'),
      //'#required' => TRUE,
    ];
    
    $form['username'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('Username'),
      //'#required' => TRUE,
    ];     
    $form['email'] = [
      '#type' => 'email',
      '#placeholder' => $this->t('Email'),
      //'#required' => TRUE,       
    ];
    $form['password'] = array(
      '#type' => 'password',
      '#placeholder' => $this->t('Password'),
      //'#required' => TRUE,
      '#size' => 10,
    );
    $form['password_confirm'] = array(
      '#type' => 'password',
      '#placeholder' => $this->t('Confirm Password'),
      //'#required' => TRUE,
      '#size' => 10,
    );
    
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
    
//    $form['#validate'][] = '::validateUsername';

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
      //$ajax_response->addCommand(new CloseModalDialogCommand());
      $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('<front>')->toString()));
    } else {
      $message = [
        '#theme' => 'status_messages',
        '#message_list' => drupal_get_messages(),
      ];
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $message));
      // $ajax_response->addCommand(new ReplaceCommand('#bc_2movepeople-dashboard-user-create-form', $form));
    }
    //$form_state->setRebuild(true);

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  
    //$url = \Drupal\Core\Url::fromInternalUrl('<front>');
    //$url = Url::fromRoute('<front>');
    // $form_state->setRedirect(Url::fromInternalUri('<front>'));
     
    if (!$form_state->getErrors()) { // $form_state->hasAnyErrors()
      
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
      //$user->set('field_connected_users', $current_user->id());
      $current_user_roles = $current_user->getRoles();
      if (in_array('2mp_supervisor', $current_user_roles)) {
        $user->addRole('2mp_manager');
      }
      elseif (in_array('2mp_manager', $current_user_roles)) {
        $user->addRole('2mp_user');
      }
      $user->activate();

      // Save user account.
      $this->isSaved = $user->save();
      
      if ($this->isSaved != SAVED_NEW) {
        drupal_set_message($this->wrong_msg, 'error');
      }else{
        //If user saved, update current user
        $current_user = \Drupal\user\Entity\User::load($current_user->id());
        $current_user->field_connected_users[] = $user;
        $current_user->save();
      }
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // check Firstname
    $firstname = CommonFormUtils::cleanInput($form_state->getValue('firstname'));
    if (strlen($firstname) < 4) {
      $form_state->setErrorByName('firstname', $this->t('The First Name %firstname is not valid.', array('%firstname' => $firstname)));
    }
    
    // check Lastname
    $surname = CommonFormUtils::cleanInput($form_state->getValue('surname'));
    if (strlen($surname) < 4) {
      $form_state->setErrorByName('surname', $this->t('The Surname %surname is not valid.', array('%surname' => $surname)));
    }
    
    // check Username
    $username = CommonFormUtils::cleanInput($form_state->getValue('username'));
    if (strlen($username) < 4) {
      $form_state->setErrorByName('username', $this->t('The Username %username is not valid.', array('%username' => $username)));
    }
    
    // check Email
    $email = trim($form_state->getValue('email'));
    // $form_state->setValueForElement(['#email'], $email);
    if (!\Drupal::service('email.validator')->isValid($email)) {
      // $form_state->setError(['#email'], t('The email address %mail is not valid.', array('%mail' => $value)));
      $form_state->setErrorByName('email', $this->t('The Email address %mail is not valid.', array('%mail' => $email)));
    }
    
    // check Password
    $password = CommonFormUtils::cleanInput($form_state->getValue('password'));
    $password_confirm = CommonFormUtils::cleanInput($form_state->getValue('password_confirm'));
    if (strlen($password) < 4 || $password != $password_confirm) {
      $form_state->setErrorByName('password', $this->t('The passwords do not match.'));
    } 
    
  }
}
