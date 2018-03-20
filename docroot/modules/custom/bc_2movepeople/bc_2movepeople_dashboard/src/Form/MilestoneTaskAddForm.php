<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskAddForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
//use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\node\Entity\Node;
use \Drupal\user\Entity\User;

class MilestoneTaskAddForm extends FormBase {

  protected $parent_node;
  protected $isSaved;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {

    $this->return_url = \Drupal::request()->query->get('return_url');
    $this->parent_node = $node;

    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-milestone-task-add-form">';
    $form['#suffix'] = '</div>';

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Task'),
      '#placeholder' => $this->t('Task'),
      '#required' => TRUE,
    ];

    // Get all managers of target user
    $user = $this->parent_node->get('field_progression_user')->getValue();
    $user_managers_ids = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('roles', '2mp_manager')
        ->condition('field_connected_users', $user[0]['target_id'], 'CONTAINS')
        ->execute();
    $user_managers = User::loadMultiple($user_managers_ids);

    $options = array();
    if (!empty($user_managers)) {
      foreach ($user_managers as $user_manager) {
        $options[$user_manager->id()] = $user_manager->getDisplayName();
      }
    }

    $form['responsible_manager'] = [
      '#type' => 'select',
      '#title' => t('Responsible manager'),
      '#required' => FALSE,
      '#empty_option' => 'None',
      '#options' => $options,
    ];

    $form['activity_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Activity'),
      '#placeholder' => $this->t('Activity'),
      '#required' => TRUE,
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
    // that it gets styled correctly, and so that other modules may add actions to the form.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    // Add a submit button that handles the submission of the form.
//    $form['actions']['submit'] = [
//      '#type' => 'submit',
//      '#value' => $this->t('Save'),
//      '#attributes' => [
//        'class' => ['btn', 'btn-info'],
//      ],
//      '#ajax' => [
//        'callback' => '::ajaxSubmitForm',
//        'event' => 'click',
//      ],
//    ];

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
    //return 'bc_2movepeople-dashboard-milestone-priority-add-form'. '_' . $this->parent_node->id();

    return 'bc_2movepeople-dashboard-milestone-task-add-form';
  }

  /**
   * {@inheritdoc}
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
//        $goals = CommonFormUtils::goalsContainer(array(), $this->parent_node);
//        $renderer = \Drupal::service('renderer');
//        $response->addCommand(new ReplaceCommand("#goals-box-".$this->parent_node->id(), $renderer->render($goals)));
        $response->addCommand(new CloseModalDialogCommand());
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $title = $form_state->getValue('title');
    $activity_title = $form_state->getValue('activity_title');
    $due_date = $form_state->getValue('due_date');
    $responsible_manager = $form_state->getValue('responsible_manager');

    $node = Node::create(array(
          'type' => 'goal',
          'status' => 1,
          'title' => $title,
          'field_activity_title' => $activity_title,
          'field_due_date' => $due_date,
          'field_responsible_manager' => $responsible_manager,
    ));

    if ($node->save() == SAVED_NEW) {

      $old_goal_ids = $this->parent_node->get('field_goal_ids')->getValue();
      $new_goal_ids = [];
      foreach ($old_goal_ids as $tid) {
        $new_goal_ids[] = $tid['target_id'];
      }
      $new_goal_ids[] = $node->id();
      $this->parent_node->set('field_goal_ids', $new_goal_ids);
      $this->isSaved = $this->parent_node->save();

      $user = $this->parent_node->get('field_progression_user')->getValue();

      if (isset($this->return_url)) {
        $form_state->setRedirectUrl(Url::fromUri('internal:' . $this->return_url));
      }
      else {
        $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_dashboard.user.milestones', ['user' => $user[0]['target_id']], ['fragment' => $this->parent_node->id()]));
      }
    }
  }

}
