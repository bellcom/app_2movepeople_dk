<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use \Drupal\user\UserInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

class ProgressionAddForm extends FormBase {

  private $user;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $this->user = $user;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category'),
      '#required' => TRUE,
    ];

    $form['for_user_feedback'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('For user feedback'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-milestone-add-form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $progression_type = 'progression';
    if ($form_state->getValue('for_user_feedback')) {
      $progression_type = 'progression_feedback';
    }

    $node = Node::create(array(
      'status' => 1,
      'type' => 'progression_target',
      'title' => $form_state->getValue('title'),
      'field_progression_user' => $this->user->id(),
      'field_progression_type' => $progression_type,
    ));

    if ($node->save() == SAVED_NEW) {
      $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $this->user->id()]));
    }

    // Invalidate navigation block cachetag.
    Cache::invalidateTags(array('feedback:' . $this->user->id()));
  }

}
