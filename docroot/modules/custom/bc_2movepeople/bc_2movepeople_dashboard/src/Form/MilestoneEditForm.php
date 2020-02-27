<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
/**
 * Use Drupal\node\NodeInterface;.
 */
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Form to edit milestones.
 */
class MilestoneEditForm extends FormBase {

  protected $node;
  protected $user;
  private $updatedMsg;
  private $deletedMsg;
  private $wrongMsg;

  /**
   * {@inheritdoc}
   */
  public function __construct($nodedata, $user) {
    $this->node = $nodedata;
    $this->user = $user;
    $this->updatedMsg = $this->t('Records successfully updated.');
    $this->deletedMsg = $this->t('Records successfully deleted.');
    $this->wrongMsg = $this->t('Something wrong.');
  }

  /**
   * Build MilestoneEditForm render representing array.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'bc_2movepeople_dashboard/bc_2movepeople_dashboard.milestone-edit';
    $purpose = $this->node->get('field_purpose')->value;
    $form['purpose'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Purpose'),
      '#resizable' => 'none',
      '#rows' => 2,
      '#default_value' => $purpose,
      '#prefix' => '<div class="row purpose"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">',
      '#suffix' => '</div></div>',
    ];
    $form = CommonFormUtils::goalsContainer($form, $this->node, $this->user);
    $form['add_mt'] = [
      '#type' => 'link',
      '#title' => '',
      '#name' => 'add_task_btn',
      '#url' => Url::fromRoute('bc_2movepeople_dashboard.milestone.tasks.add', ['node' => $this->node->id()]),
      '#prefix' => '<div class="row custom-form-fields dashboard-milestone__control-buttons"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">',
      '#suffix' => '</div></div>',
      '#attributes' => [
        'class' => ['use-ajax', 'btn', 'btn-default', 'link-btn', 'glyphicon', 'glyphicon-plus'],
        'data-dialog-type' => 'modal',
        'data-toggle' => 'tooltip',
		'title' => $this->t('Add new task'),
      ],
    ];

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    $form['actions'] = [
      '#type'  => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#name' => 'submit',
        '#value' => '',
        '#button_type' => 'primary',
        '#prefix' => '<div class="row custom-form-fields dashboard-milestone__control-buttons"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">',
        '#suffix' => '</div></div>',
        '#attributes' => [
          'class' => ['btn-default', 'glyphicon', 'glyphicon-refresh'],
		  'title' => $this->t('Update'),
        ],
        '#ajax' => [
          'callback' => '::ajaxSubmitForm',
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-milestone-edit-form-' . $this->node->id();
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $nid = $this->node->id();

    if (!$form_state->getErrors()) {

      $purpose = $form_state->getValue('purpose');

      $node = Node::load($nid);
      $node->set("field_purpose", $purpose);
      $node->save();

      $goals_arr = $form_state->getValue('goals');
      foreach ($goals_arr as $gid => $goal) {
        $goal_node = Node::load($gid);
        $goal_node->set("title", $goal['title']);
        $goal_node->set("field_activity_title", $goal['activity_title']);
        $goal_node->set("field_due_date", $goal['due_date']);
        $goal_node->set("field_responsible_manager", ['target_id' => $goal['responsible_manager']]);
        $goal_node->save();
      }
      drupal_set_message($this->updatedMsg);
    }

    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
    ];
    $messages = \Drupal::service('renderer')->render($message);
    $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $messages));

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxGoalComplete(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $goal_id = $form_state->getTriggeringElement()['#attributes']['data_goal_id'];
    $prefix = $form_state->getTriggeringElement()['#attributes']['data_prefix'];
    $goal = MovepeopleDashboardController::getGoal($goal_id, $this->node);
    $goal_node = Node::load($goal_id);

    if (is_object($goal_node)) {

      unset($form[$prefix . 'goals'][$goal_id]['complete_btn']['#prefix']);
      unset($form[$prefix . 'goals'][$goal_id]['complete_btn']['#suffix']);

      $form[$prefix . 'goals'][$goal_id]['complete_btn']['#prefix'] = '<span id="complete_btn_box' . $goal_id . '">';
      $form[$prefix . 'goals'][$goal_id]['complete_btn']['#suffix'] = '</span>';

      if ($goal['completed']) {
        $goal_node->set("field_task_complete", 0);
        $form[$prefix . 'goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['false'];
        $form[$prefix . 'goals'][$goal_id]['complete_btn']['#attributes']['class'] =
            ['btn', 'btn-default', 'custom-checkbox-ok'];
      }
      else {
        $goal_node->set("field_task_complete", 1);
        $form[$prefix . 'goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['true'];
        $form[$prefix . 'goals'][$goal_id]['complete_btn']['#attributes']['class'] =
            ['btn', 'btn-default', 'custom-checkbox-ok', 'active'];
      }

      if ($goal_node->save() == SAVED_UPDATED) {

        if ($goal_node->get('field_task_complete')->value && $this->user->get('mail')->value) {

          $config = $this->config('bc_2movepeople_dashboard.AdminSettings');
          $subject = $config->get('task_complete_email_subject');
          $body = $config->get('task_complete_email_body');
          $dashboardMailer = \Drupal::service('2movepeople_dashboard.mailer');
          $body = str_replace("@name", $this->user->get('name')->value, $body);
          $dashboardMailer->replaceDefaultTokens($body, [
            'recipient' => $this->user,
          ]);
          $body = str_replace("@user", \Drupal::currentUser()->getDisplayName(), $body);
          $body = str_replace("@task_title", $goal_node->get('title')->value, $body);

          $dashboardMailer->sendMail([
            'to' => $this->user->get('mail')->value,
            'from' => \Drupal::config('system.site')->get('mail'),
            'subject' => $subject,
            'body' => $body,
            'sender' => $this->t('System notify'),
            'wpn_to' => $this->user,
          ]);
        }
        drupal_set_message($this->updatedMsg);
      }

      $message = [
        '#theme' => 'status_messages',
        '#message_list' => drupal_get_messages(),
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error'  => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ];

      $messages = \Drupal::service('renderer')->render($message);
      $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $messages));
      $ajax_response->addCommand(new ReplaceCommand('#complete_btn_box' . $goal_id, $form[$prefix . 'goals'][$goal_id]['complete_btn']));

      // Update icon class.
      $icon_class = '';
      $remind_types = [
        1 => 'is-remind-warning',
        2 => 'is-remind-expired',
      ];
      $is_remind = MovepeopleDashboardController::isRemindSession($goal['date']);

      if (! (bool) $goal['completed']) {
        $icon_class = ' is-completed';
      }
      elseif ($is_remind) {
        $icon_class = ' ' . $remind_types[$is_remind];
      }

      $ajax_response->addCommand(new InvokeCommand(NULL, 'alterClass', ['#goal_row_' . $goal_id, $icon_class]));
    }

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxGoalDelete(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $goal_id = $form_state->getTriggeringElement()['#attributes']['data_goal_id'];
    $parent_goal_id = $form_state->getTriggeringElement()['#attributes']['data_parent_goal'];
    $node = $parent_goal_id ? Node::load($parent_goal_id) : $this->node;
    $field_name = $parent_goal_id ? 'field_subgoal' : 'field_goal_ids';
    $old_goal_ids = $node->get($field_name)->getValue();

    $is_deleted = 0;
    $new_goal_ids = [];
    foreach ($old_goal_ids as $tid) {
      if ($tid['target_id'] != $goal_id) {
        $new_goal_ids[] = $tid['target_id'];
      }
      else {
        $goal_node = Node::load($goal_id);
        $goal_node->delete();
        $is_deleted = 1;
      }
    }
    if ($is_deleted) {
      $node->set($field_name, $new_goal_ids);
      $node->save();
      drupal_set_message($this->deletedMsg);
    }
    else {
      drupal_set_message($this->wrongMsg);
    }
    $ajax_response->addCommand(new RemoveCommand('#goal_row_' . $goal_id));

    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
    ];
    $messages = \Drupal::service('renderer')->render($message);
    $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $messages));

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
