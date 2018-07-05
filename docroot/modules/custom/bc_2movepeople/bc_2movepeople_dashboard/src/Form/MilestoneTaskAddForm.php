<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

/**
 * Form to add milestone tasks.
 *
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskAddForm.
 */
class MilestoneTaskAddForm extends FormBase {

  protected $parentNode;
  protected $isSaved;

  /**
   * Build MilestoneTaskAddForm render representing array.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param \Drupal\node\NodeInterface $node
   *   Parent node.
   *
   * @return array
   *   Array of ajax commands to execute on submit of the modal form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {

    $this->return_url = \Drupal::request()->query->get('return_url');
    $this->parentNode = $node;

    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-milestone-task-add-form">';
    $form['#suffix'] = '</div>';

    $goals_options = MovepeopleDashboardController::getProgressionGoalsList($this->parentNode);
    $form['parent_task_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent task'),
      '#options' => $goals_options,
      '#empty_option' => $this->t('-Select parent task-'),
      '#required' => FALSE
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task'),
      '#placeholder' => $this->t('Task'),
      '#required' => TRUE,
    ];

    // Get all managers of target user.
    $user = $this->parentNode->get('field_progression_user')->getValue();
    $user_managers_ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', '2mp_manager')
      ->condition('field_connected_users', $user[0]['target_id'], 'CONTAINS')
      ->execute();
    $user_managers = User::loadMultiple($user_managers_ids);

    $managers = [];
    if (!empty($user_managers)) {
      foreach ($user_managers as $user_manager) {
        $managers[$user_manager->id()] = CommonFormUtils::getUserName($user_manager);
      }
    }
    $options = ['Managers' => $managers];

    // By default tasks assigned to current user (empty value).
    // User managers can be responsible for tasks also.
    $form['responsible_manager'] = [
      '#type' => 'select',
      '#title' => t('Responsible'),
      '#required' => FALSE,
      '#empty_option' => CommonFormUtils::getUserName(User::load($user[0]['target_id'])),
      '#options' => $options,
    ];

    $form['activity_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Activity'),
      '#placeholder' => $this->t('Activity'),
      '#required' => TRUE,
    ];
    $form['evaluation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Evaluation'),
      '#placeholder' => $this->t('Evaluation'),
    ];
    $form['due_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Deadline'),
      '#placeholder' => $this->t('Deadline'),
      '#required' => TRUE,
    ];

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules
    // may add actions to the form.
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
   * Returns form id.
   *
   * @return string
   *   Form id
   */
  public function getFormId() {

    return 'bc_2movepeople-dashboard-milestone-task-add-form';
  }

  /**
   * Implements the submit handler for the ajax call.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the modal form.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix']);
      unset($form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#bc_2movepeople-dashboard-milestone-task-add-form', $form));
    }
    else {
      if ($this->isSaved == SAVED_UPDATED) {
        $response->addCommand(new CloseModalDialogCommand());
      }
    }
    return $response;
  }

  /**
   * Implements the submit handler.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $title = $form_state->getValue('title');
    $activity_title = $form_state->getValue('activity_title');
    $evaluation = $form_state->getValue('evaluation');
    $due_date = $form_state->getValue('due_date');
    $responsible_manager = $form_state->getValue('responsible_manager');
    $parent_task_id = $form_state->getValue('parent_task_id');

    $new_node = Node::create([
      'type' => 'goal',
      'status' => 1,
      'title' => $title,
      'field_activity_title' => $activity_title,
      'field_due_date' => $due_date,
      'field_evaluation' => $evaluation,
      'field_responsible_manager' => $responsible_manager,
    ]);

    if ($new_node->save() == SAVED_NEW) {
      $node = $parent_task_id ? Node::load($parent_task_id) : $this->parentNode;
      $field_name = $parent_task_id ? 'field_subgoal' : 'field_goal_ids';
      $old_goal_ids = $node->get($field_name)->getValue();
      $new_goal_ids = [];
      foreach ($old_goal_ids as $tid) {
        $new_goal_ids[] = $tid['target_id'];
      }
      $new_goal_ids[] = $new_node->id();

      $node->set($field_name, $new_goal_ids);

      $this->isSaved = $node->save();

      $user = $this->parentNode->get('field_progression_user')->getValue();

      if (isset($this->return_url)) {
        $form_state->setRedirectUrl(Url::fromUri('internal:' . $this->return_url));
      }
      else {
        $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_dashboard.user.milestones', ['user' => $user[0]['target_id']], ['fragment' => $this->parentNode->id()]));
      }
    }
  }

}
