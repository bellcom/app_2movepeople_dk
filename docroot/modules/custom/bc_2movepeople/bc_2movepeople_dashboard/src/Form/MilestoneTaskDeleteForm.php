<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\bc_2movepeople_dashboard\Misc\Utils;
/**
 * User managers edit form.
 */
class MilestoneTaskDeleteForm extends FormBase {

  protected $parentNode;
  protected $isDeleted;
  protected $goal;
  protected $return_url;
  private $deleted_msg = 'Task is successfully deleted.';
  private $wrong_msg = 'Something wrong.';
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, NodeInterface $task_node = NULL) {
    $this->goal = $task_node;
    $this->node = $node;
    $this->return_url =\Drupal::request()->server->get('HTTP_REFERER');
      $form['description'] = [
        '#type' => 'item',
        '#markup' => $this->t('Are you sure that you want to cancel the goal %title?', array('%title' => $this->goal->getTitle())),
      ];


    $form['actions']['#type'] = 'actions';
   $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Remove'),
      '#button_type' => 'primary',

      '#ajax' => [
        'event' => 'click',
        'callback' => '::ajaxSubmitForm',
        'progress' => ['type' => 'none'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-task-delete-form';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function ajaxSubmitForm(array $form, FormStateInterface $form_state) {

   $response = new AjaxResponse();
   $user = $this->node->get('field_progression_user')->getValue();
   $response->addCommand(new CloseModalDialogCommand());
   $response->addCommand(new RedirectCommand( $this->return_url));

     return $response;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $goal_id = $this->goal->id();
    $parent_goal_id = Utils::getParentGoalId($this->goal);
    $node = $parent_goal_id ? Node::load($parent_goal_id) : $this->node;
    $field_name = $parent_goal_id ? 'field_subgoal' : 'field_goal_ids';
    $old_goal_ids = $node->get($field_name)->getValue();
    $this->isDeleted = 0;
    $new_goal_ids = [];
    foreach ($old_goal_ids as $tid) {
      if ($tid['target_id'] != $goal_id) {
        $new_goal_ids[] = $tid['target_id'];
      }
      else {
        $goal_node = Node::load($goal_id);
        $goal_node->delete();
        $this->isDeleted = 1;
      }
    }
    if ($this->isDeleted) {
      $node->set($field_name, $new_goal_ids);
      $node->save();
      drupal_set_message(t($this->deleted_msg));
    }
    else {
      drupal_set_message(t($this->wrong_msg));
    }


  }
}
