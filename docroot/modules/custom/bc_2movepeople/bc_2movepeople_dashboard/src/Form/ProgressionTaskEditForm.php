<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\ProgressionTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

class ProgressionTaskEditForm extends FormBase {

  protected $parent_node;
  protected $isSaved;
  protected $limit;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $limit = NULL) {
    $this->parent_node = $node;
    $this->limit = $limit;
    
    $goals_options = MovepeopleDashboardController::getProgressionGoalsList($this->parent_node);  
    
    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-progression-task-edit-form">';
    $form['#suffix'] = '</div>';
    
    $form['parent_task_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent question'),
      '#options' => $goals_options,
      '#empty_option' => $this->t('-Select parent question-'),
      '#required' => FALSE
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Question'),
      '#placeholder' => $this->t('Question'),
      '#required' => TRUE,
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
    ];    

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-progression-task-edit-form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $title = $form_state->getValue('title');
    $parent_task_id = $form_state->getValue('parent_task_id');
   
    $new_node = Node::create(array(
      'type' => 'goal',
      'status' => 1,
      'title' => $title,
    ));

    if ($new_node->save() == SAVED_NEW) {
      
      $node = $parent_task_id ? Node::load($parent_task_id) : $this->parent_node;
      $field_name = $parent_task_id ? 'field_subgoal' : 'field_goal_ids';
      $old_goal_ids = $node->get($field_name)->getValue();
      
      $new_goal_ids = [];
      foreach($old_goal_ids as $tid) {
        $new_goal_ids[] = $tid['target_id'];
      }
      $new_goal_ids[] = $new_node->id();
      
      $node->set($field_name, $new_goal_ids);
      
      if ($node->save() == SAVED_UPDATED) {
        $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_dashboard.progressions.edit', ['node' => $this->parent_node->id(), 'limit' => $this->limit]));
      } else {
        drupal_set_message($this->t($this->wrong_msg));
      }
    } else {
      drupal_set_message($this->t($this->wrong_msg));
    }
  }
       
}
