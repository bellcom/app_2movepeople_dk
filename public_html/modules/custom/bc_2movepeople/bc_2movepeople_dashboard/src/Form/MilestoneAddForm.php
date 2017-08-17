<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use \Drupal\user\UserInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

class MilestoneAddForm extends FormBase {

  private $user;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $this->user = $user;

    $priority_options = options_allowed_values(FieldStorageConfig::loadByName('node', 'field_priority'));
    $status_options = options_allowed_values(FieldStorageConfig::loadByName('node', 'field_progression_status'));
    
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Milestone'),
      '#required' => TRUE,
    ];
    
    $form['purpose'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Purpose'),
      '#resizable' => 'none',
      '#rows' => 2,
      '#required' => TRUE,
      '#suffix' => '<br/>'
    ];
    
    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#options' => $priority_options,
      '#empty_option' => $this->t('-Select priority-'),
      '#required' => TRUE
    ];
    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => $status_options,
      '#empty_option' => $this->t('-Select status-'),
      '#required' => TRUE,
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

    $node = Node::create(array(
      'status' => 1,
      'type' => 'progression_target',
      'title' => $form_state->getValue('title'),
      'field_purpose' => $form_state->getValue('purpose'),
      'field_priority' => $form_state->getValue('priority'),
      'field_progression_status' => $form_state->getValue('status'),
      'field_progression_user' => $this->user->id(),
      'field_progression_type' => 'target_milestone'
    ));
    
    if ($node->save() == SAVED_NEW) {
      $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_dashboard.user.milestones', ['user' => $this->user->id()]));
    }
  }
       
}
