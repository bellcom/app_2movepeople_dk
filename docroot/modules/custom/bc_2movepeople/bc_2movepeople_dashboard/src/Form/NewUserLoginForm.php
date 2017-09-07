<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\NewUserLoginForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

//use Drupal\Core\Url;
//use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\node\NodeInterface;
//use Drupal\Core\Ajax\AjaxResponse;
//use Drupal\Core\Ajax\HtmlCommand;
//use Drupal\Core\Ajax\RemoveCommand;
//use Drupal\Core\Ajax\ReplaceCommand;
//use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
//use Drupal\node\Entity\Node;
use Drupal\user\Form\UserLoginForm;


/**
 * Provides a user login form.
 */
class NewUserLoginForm extends UserLoginForm {
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    
    $form['name']['#placeholder'] = $form['name']['#title'];
    $form['pass']['#placeholder'] = $form['pass']['#title'];
    unset($form['name']['#title']);
    unset($form['pass']['#title']);
    unset($form['name']['#description']);
    unset($form['pass']['#description']);
    
    $form['pass']['#suffix'] = '<div class="forgot-pass-box">'.$this->t('Forgot Password').'?</div>';
    
    $form['actions']['submit']['#value'] = $this->t('Login');
    $form['actions']['submit']['#attributes'] = [
      'class' => ['btn-default']
    ];

    return $form;
  }
}