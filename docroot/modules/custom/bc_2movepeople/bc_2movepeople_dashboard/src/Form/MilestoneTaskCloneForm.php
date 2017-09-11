<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

class MilestoneTaskCloneForm extends FormBase {

  private $node;
  private $user;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL, NodeInterface $node = NULL) {
    $this->node = $node;
    $this->user = $user;
    
    $progression_options = array();
    $entity_ids = MovepeopleDashboardController::getProgressionTargets($user->id());
    $progression_nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
    
    foreach ($progression_nodes as $progrdata) {
      $progression_options[$progrdata->id()] = $progrdata->get('title')->value;
    }
    
    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-task-clone-form">';
    $form['#suffix'] = '</div>';
    
    $form['progression_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $progression_options,
      '#empty_option' => $this->t('-Select category-'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxGetQuestionsForm',
        'event' => 'change',
      //  'progress' => ['type' => 'none', 'message' => NULL],
      ],
    ];
    $form['parent_task_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent question'),
      '#options' => array(),
      '#empty_option' => $this->t('-Select parent question-'),
      '#required' => FALSE,
      '#prefix' => '<div id="parent_task_box">',
      '#suffix' => '</div>'
    ];
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',  
      '#value' => $this->t('Save'),
      '#button_type' => 'primary'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-task-clone-form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function ajaxGetQuestionsForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    
    $progression_id = $form_state->getValue('progression_id');
    if ($progression_id) {
      $progression = Node::load($progression_id);
      $goals_options = MovepeopleDashboardController::getProgressionGoalsList($progression);
      
      if (sizeof($goals_options) < 1) {
        $goals_options = array(0 => $this->t('-Select parent question-'));
      }

      $form['parent_task_id']['#options'] = $goals_options;
      $form['parent_task_id']['#empty_option'] = $this->t('-Select parent question-');
      $ajax_response->addCommand(new ReplaceCommand('#parent_task_box', $form['parent_task_id']));
    }
    
    return $ajax_response;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $title = $this->node->get('title')->value;
    $progression_id = $form_state->getValue('progression_id');
    $parent_task_id = $form_state->getValue('parent_task_id');
   
    $new_node = Node::create(array(
      'type' => 'goal',
      'status' => 1,
      'title' => $title,
    ));

    if ($new_node->save() == SAVED_NEW) {
      
      $node = $parent_task_id ? Node::load($parent_task_id) : Node::load($progression_id);
      $field_name = $parent_task_id ? 'field_subgoal' : 'field_goal_ids';
      $old_goal_ids = $node->get($field_name)->getValue();
      
      $new_goal_ids = [];
      foreach($old_goal_ids as $tid) {
        $new_goal_ids[] = $tid['target_id'];
      }
      $new_goal_ids[] = $new_node->id();
      
      $node->set($field_name, $new_goal_ids);
      
      if ($node->save() == SAVED_UPDATED) {
        $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $this->user->id()]));
      } else {
        drupal_set_message($this->t($this->wrong_msg));
      }
      
    } else {
      drupal_set_message($this->t($this->wrong_msg));
    }
  }
       
}
