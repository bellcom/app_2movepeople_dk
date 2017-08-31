<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

class ProgressionEditForm extends FormBase {

  private $node;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $this->node = $node;
    
    $form['#prefix'] = '<div class="dashboard-overview">';
    $form['#suffix'] = '</div>';
    
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Progression'),
      '#default_value' => $this->node->get('title')->value,
      '#required' => TRUE,
    ];
    
    // Disable caching on this form.
    $form_state->setCached(FALSE);
    
    $user = $this->node->get('field_progression_user')->getValue();
    
    $form['actions']['#type'] = 'actions'; 
    
    $form['actions']['add_task'] = [ 
      '#type' => 'link',
      '#title' => 'Add new task',
      '#name' => 'add_task_btn',
      '#url' => Url::fromRoute('bc_2movepeople_dashboard.progression.tasks.add', array('node' => $this->node->id())),
      '#prefix' => '<div class="row custom-form-fields"><div class="col-md-6 col-sm-6 col-xs-6">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => ['use-ajax', 'btn', 'btn-default', 'link-btn'],
        'data-dialog-type' => 'modal',
      ]
    ];
    
    $form = CommonFormUtils::tasksContainer($form, $this->node);
    
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',  
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
//      '#attributes' => [
//          'class' => ['btn-default'],
//        ],
      '#prefix' => '<div class="col-md-6 col-sm-6 col-xs-6 text-right">',
      //'#suffix' => '</div>',
    ];
    
    $form['actions']['cancel'] = [
      '#title' => $this->t('Cancel'),
      '#type' => 'link',
      '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user[0]['target_id']]),
      '#attributes' => array(
        'class' => ['btn', 'btn-default', 'link-btn'],
      ),
    //  '#prefix' => '<div class="col-md-2 col-sm-2 col-xs-2 text-right">',
      '#suffix' => '</div></div>',
    ];  

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-progression-edit-form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function ajaxGoalDelete(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    
    $goal_id = $form_state->getTriggeringElement()['#attributes']['data_goal_id'];
    $parent_task_id = $form_state->getTriggeringElement()['#attributes']['data_parent_id'];
    
    $goal_node = Node::load($goal_id);
    $node = $parent_task_id ? Node::load($parent_task_id) : $this->node;
    $field_name = $parent_task_id ? 'field_subgoal' : 'field_goal_ids';
    $old_goal_ids = $node->get($field_name)->getValue();
    
    // Delete subgoals
    if (!$parent_task_id) {
      $subgoal_ids = $goal_node->get('field_subgoal')->getValue();    
      foreach ($subgoal_ids as $tid) {
        Node::load($tid['target_id'])->delete();
      }
    }
    
    $is_deleted = 0;
    $new_goal_ids = [];
    foreach($old_goal_ids as $tid) {
      if($tid['target_id'] != $goal_id) {
        $new_goal_ids[] = $tid['target_id'];
      } else {
        $goal_node->delete();
        $is_deleted = 1;
      }
    }
    if ($is_deleted) {
      $node->set($field_name, $new_goal_ids);
      $node->save();
    } else {
      drupal_set_message($this->t($this->wrong_msg));
    }
    $ajax_response->addCommand(new RemoveCommand('#goal_row_'.$goal_id));
    
    return $ajax_response;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    if (!$form_state->getErrors()) {

      $title = $form_state->getValue('title');
      
      $this->node->set("title", $title);
      $this->node->save();
      
      $goals_arr = $form_state->getValue('goals');

      foreach($goals_arr as $gid => $goal) {
        $goal_node = \Drupal\node\Entity\Node::load($gid);
        $goal_node->set("title", $goal['title']);       
        $goal_node->save();
      }

      if ($this->node->save() == SAVED_UPDATED) {
        
        drupal_set_message($this->t($this->updated_msg));
        
        $user = $this->node->get('field_progression_user')->getValue();
        $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user[0]['target_id']]));
      }
    } else {
      drupal_set_message($this->t($this->wrong_msg));
    }

  }
       
}
