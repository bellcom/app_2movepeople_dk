<?php
/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\CommonFormUtils.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Drupal\Core\StringTranslation\StringTranslationTrait;


class CommonFormUtils {

  public static function goalsContainer($form, $node, $is_manager = FALSE) {
    
    $prefix = $is_manager ? 'manager_' : '';
    $goal_ids = $node->get('field_goal_ids')->getValue();
 
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

    $form[$prefix.'goals'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $prefix.'goals-box-'.$node->id()],
    ];
            
    foreach ($goal_ids as $tid) {
      
      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $node);
      
      if ($goal['is_manager'] XOR $is_manager) {
        continue;
      }
     
      $form[$prefix.'goals']['#tree'] = TRUE;
      
      $form[$prefix.'goals']['header'] = [
        '#markup' => ($is_manager ? '<h2>'.t('Manager\'s tasks').'</h2>' : '')
            . '<div class="row custom-form-fields custom-form-label hidden-xs hidden-sm">'
            . '<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 custom-form-label">'.t('Task').'</div>'
            . '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 custom-form-label">'.t('Activity').'</div>'
            . '<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 custom-form-label">'.t('Deadline').'</div>'
            . '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 custom-form-label">'.t('Evaluation').'</div>'
            . '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 custom-form-label">'.t('Actions').'</div>'
            . '</div>'
      ];

      $form[$prefix.'goals'][$goal_id] = [
        '#type' => 'container'
      ];

      $form[$prefix.'goals'][$goal_id]['title'] = [
        '#type' => 'textfield',
        '#default_value' => $goal['title'],
        '#prefix' => '<div class="row custom-form-fields div-form" id="goal_row_'.$goal_id.'">'
          . '<div class="visible-xs visible-sm col-sm-12 col-xs-12 custom-form-label">'.t('Task').'</div>'
          . '<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">',
        '#suffix' => '</div>'

      ];

      $form[$prefix.'goals'][$goal_id]['activity_title'] = [
        '#type' => 'textfield',
        '#default_value' => $goal['activity_title'],
        '#prefix' => '<div class="visible-xs visible-sm col-sm-12 col-xs-12 custom-form-label">'.t('Activity').'</div>'
          . '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">',
        '#suffix' => '</div>'
      ];

      $form[$prefix.'goals'][$goal_id]['due_date'] = [
        '#type' => 'date',
        '#default_value' => $goal['date'],
        '#prefix' => '<div class="visible-xs visible-sm col-sm-12 col-xs-12 custom-form-label">'.t('Deadline').'</div>'
          . '<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">',
        '#suffix' => '</div>'

      ];

      $form[$prefix.'goals'][$goal_id]['evaluation'] = [
        '#type' => 'textfield',
        '#default_value' => $goal['evaluation'],
        '#prefix' => '<div class="visible-xs visible-sm col-sm-12 col-xs-12 custom-form-label">'.t('Evaluation').'</div>'
          . '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">',
        '#suffix' => '</div>'

      ];

      $form[$prefix.'goals'][$goal_id]['complete_btn'] = [
        '#type' => 'button',
        '#name' => 'complete_btn'.$goal_id,
        '#attributes' => [
          'data_goal_id' => $goal_id,
          'data_prefix' => $prefix,
          'class' => ['btn', 'btn-default', 'custom-checkbox-ok'],
          'data-toggle'  => ['button'],
          'aria-pressed' => ['false'],
          'autocomplete' => ['off']
        ],
        '#ajax' => [
          'event' => 'click',
          'callback' => '::ajaxGoalComplete',
          'progress' => ['type' => 'none']
        ],  
        '#prefix' => '<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 dashboard-accordion__action-btn">'
          . '<span id="complete_btn_box'.$goal_id.'">',
        '#suffix' => '</span>'
      ];

      if ($goal['completed']) {
        $form[$prefix.'goals'][$goal_id]['complete_btn']['#attributes']['class'][] = 'active';
        $form[$prefix.'goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['true'];
      }
      
      $form[$prefix.'goals'][$goal_id]['delete_btn'] = [
        '#type' => 'submit',
        '#name' => 'delete_btn'.$goal_id,
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
        '#suffix' => '</div></div>'
      ];
      
    }

    return $form;
  }
  
  public static function tasksContainer($form, $node) {
    
    $goal_ids = $node->get('field_goal_ids')->getValue();

    $form['goals'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'goals-box-'.$node->id()],
    ];
            
    foreach ($goal_ids as $tid) {
     
      $form['goals']['#tree'] = TRUE;
      
      $form['goals']['header'] = [
        '#markup' => ''
            . '<div class="row custom-form-fields custom-form-label">'
            . '<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 custom-form-label">'.t('Question').'</div>'
            . '<div class="col-lg-3 col-md-3 col-sm-3 col-xs-3 custom-form-label">'.t('Actions').'</div>'
            . '</div>'
      ];
      
      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $node);
      
      $form = self::_getTasksRow($form, $goal);      
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
      '#prefix' => '<div class="row custom-form-fields" id="goal_row_'.$goal['id'].'">'
        . ($parent_subgoal_id ? ''
            . '<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2"></div>'
            . '<div class="col-lg-7 col-md-7 col-sm-7 col-xs-7">' 
            : '<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9">'),
      '#suffix' => '</div>'

    ];

    $form['goals'][$goal['id']]['delete_btn'] = [
      '#type' => 'submit',
      '#name' => 'delete_btn'.$goal['id'],
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
  
  
  /**
   * Simply send mail function
   *
   * @param array $message with keys
   * - to
   * - from
   * - body
   * - sender
   * - subject
   * @return BOOLEAN
   */  
  public static function sendMail($message) {
    $send_mail = new \Drupal\Core\Mail\Plugin\Mail\PhpMail(); 
    $message['headers'] = array(
      'content-type' => 'text/html',
      'MIME-Version' => '1.0',
      'reply-to' => $message['from'],
      'from' => $message['sender'].' <'.$message['from'].'>'
    );
    return $send_mail->mail($message);
  }
}