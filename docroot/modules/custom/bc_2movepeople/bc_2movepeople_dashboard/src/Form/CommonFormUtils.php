<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;

/**
 * Common help methods for other forms.
 *
 * Contains \Drupal\bc_2movepeople_dashboard\Form\CommonFormUtils.
 */
class CommonFormUtils {

  /**
   * Returns form element of node goals.
   *
   * @param array $form
   *   Form.
   * @param object $node
   *   Node.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Context user.
   *
   * @return array
   *   Form element
   */
  public static function goalsContainer(array $form, $node, AccountInterface $user) {

    $goal_ids = $node->get('field_goal_ids')->getValue();
    $user_id = $node->get('field_progression_user')->getValue()[0]['target_id'];

    $form['goals'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'goals-box-' . $node->id()],
    ];

    foreach ($goal_ids as $tid) {
      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $node);
      $form = self::getGoalRow($form, $goal, $user_id);
    }
    return $form;
  }

  /**
   * Returns form element of goals.
   *
   * @param array $form
   *   Form.
   * @param array $goal
   *   Goal.
   * @param int $user_id
   *   User id.
   * @param int $parent_subgoal_id
   *   Parent subgoal id.
   *
   * @return array
   *   Form element
   */
  private static function getGoalRow(array $form, array $goal, $user_id, $parent_subgoal_id = 0) {
    $remind_types = [
      1 => 'is-remind-warning',
      2 => 'is-remind-expired',
    ];

    $is_remind = MovepeopleDashboardController::isRemindSession($goal['date']);

    $form['goals']['#tree'] = TRUE;

    $form['goals']['header'] = [
      '#markup' => '<div class="custom-form-fields custom-form-label hidden-xs hidden-sm hidden-md">'
      . '<div class="custom-form-label-title">' . t('Task') . '</div>'
      . '<div class="custom-form-label-activity-title">' . t('Activity') . '</div>'
      . '<div class="custom-form-label-due-date"><div>' . t('Deadline') . '</div></div>'
      . '<div class="custom-form-label-responsible-manager">' . t('Responsible') . '</div>'
      . '<div class="custom-form-label-complete-btn">' . t('Actions') . '</div>'
      . '</div>',
    ];

    $goal_id = $goal['id'];
    $form['goals'][$goal_id] = [
      '#type' => 'container',
    ];

    if ($parent_subgoal_id) {
      $form['goals'][$goal_id]['#attributes']['class'][] = 'subtask';
    }

    $icon_class = '';
    if ($goal['completed']) {
      $icon_class = ' is-completed';
    }
    elseif ($is_remind) {
      $icon_class = ' ' . $remind_types[$is_remind];
    }

    $form['goals'][$goal_id]['title'] = [
      '#type' => 'textfield',
      '#default_value' => $goal['title'],
      '#prefix' => '<div class="custom-form-fields div-form' . $icon_class . '" id="goal_row_' . $goal_id . '">'
      . '<div class="custom-form-field-title"><div class="visible-xs visible-sm visible-md custom-form-label">' . t('Task') . '</div>',
      '#suffix' => '</div>',
    ];

    $form['goals'][$goal_id]['activity_title'] = [
      '#type' => 'textfield',
      '#default_value' => $goal['activity_title'],
      '#prefix' => '<div class="custom-form-field-activity-title"><div class="visible-xs visible-sm visible-md custom-form-label">' . t('Activity') . '</div>',
      '#suffix' => '</div>',
    ];

    // Get all managers of target user.
    $user_managers_ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', '2mp_manager')
      ->condition('field_connected_users', $user_id, 'CONTAINS')
      ->execute();
    $user_managers = User::loadMultiple($user_managers_ids);

    $managers = [];
    if (!empty($user_managers)) {
      foreach ($user_managers as $user_manager) {
        $managers[$user_manager->id()] = self::getUserName($user_manager);
      }
    }
    $options = ['Managers' => $managers];

    $form['goals'][$goal_id]['due_date'] = [
      '#type' => 'date',
      '#default_value' => $goal['date'],
      '#prefix' => '<div class="custom-form-field-due-date"><div class="visible-xs visible-sm visible-md custom-form-label">' . t('Deadline') . '</div>',
      '#suffix' => '</div>',
    ];

    // By default tasks assigned to current user (empty value).
    // User managers can be responsible for tasks also.
    $form['goals'][$goal_id]['responsible_manager'] = [
      '#type' => 'select',
      '#required' => FALSE,
      '#default_value' => $goal['responsible_manager'],
      '#empty_option' => self::getUserName(User::load($user_id)),
      '#options' => $options,
      '#prefix' => '<div class="custom-form-field-responsible-manager"><div class="visible-xs visible-sm visible-md custom-form-label">' . t('Responsible') . '</div>',
      '#suffix' => '</div>',
    ];

    $form['goals'][$goal_id]['complete_btn'] = [
      '#type' => 'button',
      '#name' => 'complete_btn' . $goal_id,
      '#attributes' => [
        'data_goal_id' => $goal_id,
        'data_prefix' => '',
        'class' => ['btn', 'btn-default', 'custom-checkbox-ok'],
        'data-toggle' => ['button'],
        'aria-pressed' => ['false'],
        'autocomplete' => ['off'],
      ],
      '#ajax' => [
        'event' => 'click',
        'callback' => '::ajaxGoalComplete',
        'progress' => ['type' => 'none'],
      ],
      '#prefix' => '<div class="dashboard-accordion__action-btn">'
      . '<span id="complete_btn_box' . $goal_id . '">',
      '#suffix' => '</span>',
    ];

    if ($goal['completed']) {
      $form['goals'][$goal_id]['complete_btn']['#attributes']['class'][] = 'active';
      $form['goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['true'];
    }

    $form['goals'][$goal_id]['delete_btn'] = [
      '#type' => 'submit',
      '#name' => 'delete_btn' . $goal_id,
      '#attributes' => [
        'data_parent_goal' => $parent_subgoal_id ?: FALSE,
        'data_goal_id' => $goal_id,
        'class' => ['btn', 'btn-default', 'custom-checkbox-trash'],
        'data-toggle' => ['button'],
        'aria-pressed' => ['false'],
        'autocomplete' => ['off'],
      ],
      '#ajax' => [
        'event' => 'click',
        'callback' => '::ajaxGoalDelete',
        'progress' => ['type' => 'none'],
      ],
    ];

    $form['goals'][$goal_id]['clone_btn'] = [
      '#type' => 'link',
      '#title' => '',
      '#name' => 'clone_btn' . $goal_id,
      '#url' => Url::fromRoute('bc_2movepeople_dashboard.milestone.tasks.clone', ['user' => $user_id, 'node' => $goal_id]),
      '#attributes' => [
        'data_goal_id' => $goal_id,
        'data_prefix' => '',
        'data-dialog-type' => 'modal',
        'class' => ['use-ajax',
          'btn',
          'btn-default',
          'link-btn',
          'custom-checkbox-clone',
        ],
        'data-toggle' => ['button'],
        'aria-pressed' => ['false'],
        'autocomplete' => ['off'],
      ],
      '#suffix' => '</div></div>',
    ];

    if (count($goal['subgoals']) > 0) {
      foreach ($goal['subgoals'] as $subgoal) {
        $form = self::getGoalRow($form, $subgoal, $user_id, $goal['id']);
      }
    }

    return $form;
  }

  /**
   * Returns form element of node tasks.
   *
   * @param array $form
   *   Form.
   * @param object $node
   *   Node.
   * @param string $title
   *   Title.
   *
   * @return array
   *   Form element
   */
  public static function tasksContainer(array $form, $node, $title = NULL) {

    if (NULL == $title) {
      $title = t('Question');
    }
    $goal_ids = $node->get('field_goal_ids')->getValue();

    $form['goals'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'goals-box-' . $node->id()],
    ];

    foreach ($goal_ids as $tid) {

      $form['goals']['#tree'] = TRUE;

      $form['goals']['header'] = [
        '#markup' => '<div class="custom-form-fields custom-form-label">'
        . '<div class="custom-form-label"><h2><strong>' . $title . '</strong></h2></div>'
        . '<br>'
        . '<div class="custom-form-label"><strong>' . t('Actions') . '</strong></div></div>',
      ];

      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $node);

      $form = self::getTasksRow($form, $goal);
    }

    return $form;
  }

  /**
   * Returns form element of goal task.
   *
   * @param array $form
   *   Form.
   * @param array $goal
   *   Goal.
   * @param int $parent_subgoal_id
   *   Parent subgoal id.
   *
   * @return array
   *   Form element
   */
  private static function getTasksRow(array $form, array $goal, $parent_subgoal_id = 0) {
    $form['goals'][$goal['id']] = [
      '#type' => 'container',
      '#prefix' => '<div class="row">',
      '#suffix' => '</div>'
    ];

    $form['goals'][$goal['id']]['title'] = [
      '#type' => 'textfield',
      '#default_value' => $goal['title'],
      '#prefix' => '<div class="custom-form-fields" id="goal_row_' . $goal['id'] . '">'
      . ($parent_subgoal_id ? ''
      . '<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">'
      . '<div class="col-lg-7 col-md-7 col-sm-7 col-xs-7">' : '<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9">'),
      '#suffix' => '</div>',
    ];

    $form['goals'][$goal['id']]['delete_btn'] = [
      '#type' => 'submit',
      '#name' => 'delete_btn' . $goal['id'],
      '#attributes' => [
        'data_goal_id' => $goal['id'],
        'data_parent_id' => $parent_subgoal_id,
        'class' => ['btn', 'btn-default', 'custom-checkbox-trash'],
        'data-toggle' => ['button'],
        'aria-pressed' => ['false'],
        'autocomplete' => ['off'],
      ],
      '#ajax' => [
        'event' => 'click',
        'callback' => '::ajaxGoalDelete',
        'progress' => ['type' => 'none'],
      ],
      '#prefix' => '<div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">',
      '#suffix' => '</div>',
    ];
    if (count($goal['subgoals']) > 0) {
      foreach ($goal['subgoals'] as $subgoal) {
        $form = self::getTasksRow($form, $subgoal, $goal['id']);
      }
    }

    return $form;
  }

  /**
   * Sanitize user input data.
   *
   * @param string $data
   *   User input string.
   *
   * @return string
   *   Safe string.
   */
  public static function cleanInput($data) {
    // Rreal_escape_string ?
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }

  /**
   * Returns friendly user name.
   *
   * @param object $user
   *   User.
   *
   * @return string
   *   User name or drupal name
   */
  public static function getUserName($user) {

    $user_name = NULL;

    if (empty($user->field_user_firstname->value) and empty($user->field_user_surname->value)) {
      $user_name = $user->getDisplayName();
    }
    elseif (!empty($user->field_user_firstname->value) and !empty($user->field_user_surname->value)) {
      $user_name = $user->field_user_firstname->value . ' ' . $user->field_user_surname->value;
    }
    else {
      if (!empty($user->field_user_firstname->value)) {
        $user_name = $user->field_user_firstname->value;
      }
      else {
        $user_name = $user->field_user_surname->value;
      }
    }
    return $user_name;
  }

}
