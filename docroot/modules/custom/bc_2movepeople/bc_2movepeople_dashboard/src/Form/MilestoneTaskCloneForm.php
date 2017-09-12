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
//use Drupal\Core\Url;
use Drupal\Core\Ajax\HtmlCommand;
//use Drupal\Core\Ajax\AppendCommand;
//use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

class MilestoneTaskCloneForm extends FormBase {

  protected $node;
  protected $user;
  protected $isSaved;
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
    
    $form['system_messages'] = [
      '#markup' => '<div id="clone-task-form-system-messages"></div>',
      '#weight' => -100,
    ];
    
    $form['progression_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $progression_options,
      '#empty_option' => $this->t('-Select category-'),
      '#required' => FALSE,
      '#ajax' => [
        'callback' => '::changeParentQuestionOptionsAjax',
        'event' => 'change',
        'progress' => ['type' => 'throbber', 'message' => ''],
        'wrapper' => 'parent_task_wrapper',
      ],
    ];
    $form['parent_task_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent question'),
      '#options' => $this->getParentQuestionOptions($form_state),
      '#empty_option' => $this->t('-Select parent question-'),
      '#required' => FALSE,
      '#prefix' => '<div id="parent_task_wrapper">',
      '#suffix' => '</div>',
    ];
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',  
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
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
    return 'bc_2movepeople-dashboard-task-clone-form';
  }
  
  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function ajaxSubmitForm(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
   
    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
    ];
    
    if ($this->isSaved == SAVED_UPDATED) {
      $ajax_response->addCommand(new CloseModalDialogCommand());
      $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $message));
    } else {
      $ajax_response->addCommand(new HtmlCommand('#clone-task-form-system-messages', $message));
    }

    return $ajax_response;
  }
  
  /**
   * Ajax callback to change options for Parent Question.
   */
  public function changeParentQuestionOptionsAjax(array &$form, FormStateInterface $form_state) {
	return $form['parent_task_id'];
  }
  
  /**
   * Get options for Parent Question.
   */
  public function getParentQuestionOptions(FormStateInterface $form_state) {
    
    $options = array();
    $progression_id = $form_state->getValue('progression_id');

	if ($progression_id) {
      $progression = Node::load($progression_id);
      $options = MovepeopleDashboardController::getProgressionGoalsList($progression);
    }

	return $options;
  }


  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

//    if ($form_state->getErrors()) {
//      return false;
//    }

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
      $this->isSaved = $node->save();
      
      if ($this->isSaved == SAVED_UPDATED) {
        drupal_set_message($this->t($this->updated_msg));
      } else {
        drupal_set_message($this->t($this->wrong_msg));
      }
    } else {
      drupal_set_message($this->t($this->wrong_msg));
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('progression_id')) {
      $form_state->setErrorByName('progression_id', $this->t('Category field is required.'));
    }
  }
}
