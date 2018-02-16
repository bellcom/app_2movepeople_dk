<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\CommonFormUtils.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
//use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class CommonFormUtils {

  public static function goalsContainer($form, $node) {


    $goal_ids = $node->get('field_goal_ids')->getValue();
    $user_id = $node->get('field_progression_user')->getValue()[0]['target_id'];

//    $form['goals_header'] = [
//      '#markup' => ''
//          . '<div class="row custom-form-fields custom-form-label">'
//          . '<div class="col-md-3 col-sm-2 col-xs-2">Milestone</div>'
//          . '<div class="col-md-2 col-sm-2 col-xs-2">Activity</div>'
//          . '<div class="col-md-3 col-sm-4 col-xs-4">Deadline</div>'
//          . '<div class="col-md-2 col-sm-2 col-xs-2">Evaluation</div>'
//          . '<div class="col-md-2 col-sm-2 col-xs-2">Actions</div>'
//          . '</div>'
//    ];
    //  use Drupal\Core\Cache\CacheableMetadata;
//   $cacheable_metadata = new \Drupal\Core\Cache\CacheableMetadata();
//   $cacheable_metadata->setCacheMaxAge(86400); // 24h = 86400sec
//   $cacheable_metadata->setCacheTags(['dfsdf', ['24234']]);
//
//   dpm($cacheable_metadata->getCacheTags());

    $form['goals'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'goals-box-' . $node->id()],
    ];

    $remind_types = [
      1 => 'is-remind-warning',
      2 => 'is-remind-expired'
    ];

    foreach ($goal_ids as $tid) {

      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $node);

      $is_remind = MovepeopleDashboardController::isRemindSession($goal['date']);

      $form['goals']['#tree'] = TRUE;

      $form['goals']['header'] = [
        '#markup' => '<div class="row custom-form-fields custom-form-label hidden-xs hidden-sm">'
        . '<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 custom-form-label">' . t('Task') . '</div>'
        . '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 custom-form-label">' . t('Activity') . '</div>'
        . '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 custom-form-label">' . t('Responsible manager') . '</div>'
        . '<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 custom-form-label">' . t('Deadline') . '</div>'
        . '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 custom-form-label">' . t('Actions') . '</div>'
        . '</div>'
      ];

      $form['goals'][$goal_id] = [
        '#type' => 'container'
      ];

      $remind_class = '';
      if ($is_remind) {
        $remind_class = ' ' . $remind_types[$is_remind];
      }

      $form['goals'][$goal_id]['title'] = [
        '#type' => 'textfield',
        '#default_value' => $goal['title'],
        '#prefix' => '<div class="row custom-form-fields div-form' . $remind_class . '" id="goal_row_' . $goal_id . '">'
        . '<div class="visible-xs visible-sm col-sm-12 col-xs-12 custom-form-label">' . t('Task') . '</div>'
        . '<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">',
        '#suffix' => '</div>'
      ];

      $form['goals'][$goal_id]['activity_title'] = [
        '#type' => 'textfield',
        '#default_value' => $goal['activity_title'],
        '#prefix' => '<div class="visible-xs visible-sm col-sm-12 col-xs-12 custom-form-label">' . t('Activity') . '</div>'
        . '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">',
        '#suffix' => '</div>'
      ];

      // Get all managers of target user
      $user_managers_ids = \Drupal::entityQuery('user')
          ->condition('status', 1)
          ->condition('roles', '2mp_manager')
          ->condition('field_connected_users', $user_id, 'CONTAINS')
          ->execute();
      $user_managers = User::loadMultiple($user_managers_ids);

      $options = array();
      if (!empty($user_managers)) {
        foreach ($user_managers as $user_manager) {
          $options[$user_manager->id()] = $user_manager->getDisplayName();
        }
      }

      $form['goals'][$goal_id]['responsible_manager'] = [
        '#type' => 'select',
        '#required' => FALSE,
        '#default_value' => $goal['responsible_manager'],
        '#empty_option' => 'None',
        '#options' => $options,
        '#prefix' => '<div class="visible-xs visible-sm col-sm-12 col-xs-12 custom-form-label">' . t('Responsible manager') . '</div>'
        . '<div class="col-lg-2 col-md-3 col-sm-12 col-xs-12">',
        '#suffix' => '</div>'
      ];

      $form['goals'][$goal_id]['due_date'] = [
        '#type' => 'date',
        '#default_value' => $goal['date'],
        '#prefix' => '<div class="visible-xs visible-sm col-sm-12 col-xs-12 custom-form-label">' . t('Deadline') . '</div>'
        . '<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">',
        '#suffix' => '</div>'
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
          'autocomplete' => ['off']
        ],
        '#ajax' => [
          'event' => 'click',
          'callback' => '::ajaxGoalComplete',
          'progress' => ['type' => 'none']
        ],
        '#prefix' => '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 dashboard-accordion__action-btn">'
        . '<span id="complete_btn_box' . $goal_id . '">',
        '#suffix' => '</span>'
      ];

      if ($goal['completed']) {
        $form['goals'][$goal_id]['complete_btn']['#attributes']['class'][] = 'active';
        $form['goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['true'];
      }

      $form['goals'][$goal_id]['delete_btn'] = [
        '#type' => 'submit',
        '#name' => 'delete_btn' . $goal_id,
        '#attributes' => [
          'data_goal_id' => $goal_id,
          'class' => ['btn', 'btn-default', 'custom-checkbox-trash'],
          'data-toggle' => ['button'],
          'aria-pressed' => ['false'],
          'autocomplete' => ['off']
        ],
        '#ajax' => [
          'event' => 'click',
          'callback' => '::ajaxGoalDelete',
          'progress' => ['type' => 'none']
        ],
      ];

      $form['goals'][$goal_id]['clone_btn'] = [
        '#type' => 'link',
        '#title' => '',
        '#name' => 'clone_btn' . $goal_id,
        '#url' => Url::fromRoute('bc_2movepeople_dashboard.milestone.tasks.clone', array('user' => $user_id, 'node' => $goal_id)),
        '#attributes' => [
          'data_goal_id' => $goal_id,
          'data_prefix' => '',
          'data-dialog-type' => 'modal',
          'class' => ['use-ajax', 'btn', 'btn-default', 'link-btn', 'custom-checkbox-clone'],
          'data-toggle' => ['button'],
          'aria-pressed' => ['false'],
          'autocomplete' => ['off'],
        ],
        '#suffix' => '</div></div>'
//        '#ajax' => [
//          'event' => 'click',
//          'progress' => ['type' => 'none']
//        ],
      ];
    }

    return $form;
  }

  public static function tasksContainer($form, $node, $title = 'Question') {

    $goal_ids = $node->get('field_goal_ids')->getValue();

    $form['goals'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'goals-box-' . $node->id()],
    ];

    foreach ($goal_ids as $tid) {

      $form['goals']['#tree'] = TRUE;

      $form['goals']['header'] = [
        '#markup' => ''
        . '<div class="row custom-form-fields custom-form-label">'
        . '<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 custom-form-label">' . t($title) . '</div>'
        . '<div class="col-lg-3 col-md-3 col-sm-3 col-xs-3 custom-form-label">' . t('Actions') . '</div>'
        . '</div>'
      ];

      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $node);

      $form = self::_getTasksRow($form, $goal);
    }

    return $form;
  }
  
  public static function tasksEvaluateContainer($form, $node, $title = 'Tasks') {

    $goal_ids = $node->get('field_goal_ids')->getValue();

    $form['goals'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'goals-box-' . $node->id()],
    ];

    foreach ($goal_ids as $tid) {

      $form['goals']['#tree'] = TRUE;

      $form['goals']['header'] = [
        '#markup' => ''
        . '<div class="row custom-form-fields custom-form-label">'
        . '<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 custom-form-label">' . t($title) . '</div>'
        . '</div>'
      ];

      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $node);

      $form = self::_getTasksEvaluateRow($form, $goal);
    }

    return $form;
  }

  private static function _getTasksRow($form, $goal, $parent_subgoal_id = 0) {

    $form['goals'][$goal['id']] = [
      '#type' => 'container'
    ];

    $form['goals'][$goal['id']]['title'] = [
      '#type' => 'textfield',
      '#default_value' => $goal['title'],
      '#prefix' => '<div class="row custom-form-fields" id="goal_row_' . $goal['id'] . '">'
      . ($parent_subgoal_id ? ''
      . '<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2"></div>'
      . '<div class="col-lg-7 col-md-7 col-sm-7 col-xs-7">' : '<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9">'),
      '#suffix' => '</div>'
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
        'autocomplete' => ['off']
      ],
      '#ajax' => [
        'event' => 'click',
        'callback' => '::ajaxGoalDelete',
        'progress' => ['type' => 'none']
      ],
      '#prefix' => '<div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">',
      '#suffix' => '</div></div>'
    ];
    if (sizeof($goal['subgoals']) > 0) {
      foreach ($goal['subgoals'] AS $subgoal) {
        $form = self::_getTasksRow($form, $subgoal, $goal['id']);
      }
    }

    return $form;
  }

    private static function _getTasksEvaluateRow($form, $goal, $parent_subgoal_id = 0) {

    $form['goals'][$goal['id']] = [
      '#type' => 'container'
    ];

    $form['goals'][$goal['id']]['field_evaluation'] = [
      '#type' => 'textarea',
      '#title' => $goal['title'],
      '#default_value' => $goal['evaluation'],
      '#prefix' => '<div class="row custom-form-fields" id="goal_row_' . $goal['id'] . '">'
      . ($parent_subgoal_id ? ''
      . '<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2"></div>'
      . '<div class="col-lg-7 col-md-7 col-sm-7 col-xs-7">' : '<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9">'),
      '#suffix' => '</div>'
    ];

    if (sizeof($goal['subgoals']) > 0) {
      foreach ($goal['subgoals'] AS $subgoal) {
        $form = self::_getTasksEvaluateRow($form, $subgoal, $goal['id']);
      }
    }

    return $form;
  }
  
  public static function cleanInput($data) {
    //real_escape_string ?
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }

}
