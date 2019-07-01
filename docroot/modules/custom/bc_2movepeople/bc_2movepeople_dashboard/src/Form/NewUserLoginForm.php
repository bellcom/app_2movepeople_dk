<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\NewUserLoginForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
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

    $form['forgot_pass'] = [
      '#type' => 'link',
      '#title' => $this->t('Forgot Password?'),
      '#name' => 'forgot_pass_link',
      '#url' => Url::fromRoute('user.pass'),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
      ],
      '#prefix' => '<div class="row"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 forgot-pass-box">',
      '#suffix' => '</div></div>'
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Login'),
      '#attributes' => [
        'class' => ['btn-default']
      ]
    ];
    return $form;
  }

}
