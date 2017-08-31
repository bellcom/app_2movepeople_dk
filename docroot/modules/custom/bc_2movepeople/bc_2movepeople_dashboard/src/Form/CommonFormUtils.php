<?php
/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\CommonFormUtils.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Drupal\Core\StringTranslation\StringTranslationTrait;


class CommonFormUtils {

  public static function goalsContainer($form, $node) {
    
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

    $form['goals'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'goals-box-'.$node->id()],
    ];
            
    foreach ($goal_ids as $tid) {      
     
      $form['goals']['#tree'] = TRUE;
      
      $form['goals']['header'] = [
        '#markup' => ''
            . '<div class="row custom-form-fields custom-form-label">'
            . '<div class="col-md-3 col-sm-2 col-xs-2 custom-form-label">'.t('Task').'</div>'
            . '<div class="col-md-2 col-sm-2 col-xs-2 custom-form-label">'.t('Activity').'</div>'
            . '<div class="col-md-3 col-sm-4 col-xs-4 custom-form-label">'.t('Deadline').'</div>'
            . '<div class="col-md-2 col-sm-2 col-xs-2 custom-form-label">'.t('Evaluation').'</div>'
            . '<div class="col-md-2 col-sm-2 col-xs-2 custom-form-label">'.t('Actions').'</div>'
            . '</div>'
      ];
      
      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $node);

      $form['goals'][$goal_id] = [
        '#type' => 'container'
      ];

      $form['goals'][$goal_id]['title'] = [
        '#type' => 'textfield',
        '#default_value' => $goal['title'],
        '#prefix' => '<div class="row custom-form-fields" id="goal_row_'.$goal_id.'">'
          . '<div class="col-md-3 col-sm-2 col-xs-2">',
        '#suffix' => '</div>'

      ];

      $form['goals'][$goal_id]['activity_title'] = [
        '#type' => 'textfield',
        '#default_value' => $goal['activity_title'],
        '#prefix' => '<div class="col-md-2 col-sm-2 col-xs-2">',
        '#suffix' => '</div>'
      ];

      $form['goals'][$goal_id]['due_date'] = [
        '#type' => 'date',
        '#default_value' => $goal['date'],
        '#prefix' => '<div class="col-md-3 col-sm-4 col-xs-4">',
        '#suffix' => '</div>'

      ];

      $form['goals'][$goal_id]['evaluation'] = [
        '#type' => 'textfield',
        '#default_value' => $goal['evaluation'],
        '#prefix' => '<div class="col-md-2 col-sm-2 col-xs-2">',
        '#suffix' => '</div>'

      ];

      $form['goals'][$goal_id]['complete_btn'] = [
        '#type' => 'button',
        '#name' => 'complete_btn'.$goal_id,
        '#attributes' => [
          'data_goal_id' => $goal_id,
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
        '#prefix' => '<div class="col-md-2 col-sm-2 col-xs-2"><span id="complete_btn_box'.$goal_id.'">',
        '#suffix' => '</span>'
      ];

      if ($goal['completed']) {
        $form['goals'][$goal_id]['complete_btn']['#attributes']['class'][] = 'active';
        $form['goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['true'];
      }
      
      $form['goals'][$goal_id]['delete_btn'] = [
        '#type' => 'button',
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
            . '<div class="col-md-10 col-sm-10 col-xs-10 custom-form-label">'.t('Task').'</div>'
            . '<div class="col-md-2 col-sm-2 col-xs-2 custom-form-label">'.t('Actions').'</div>'
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
        . ($parent_subgoal_id ? '<div class="col-md-2 col-sm-2 col-xs-2"></div><div class="col-md-8 col-sm-8 col-xs-8">' 
            : '<div class="col-md-10 col-sm-10 col-xs-10">'),
      '#suffix' => '</div>'

    ];

    $form['goals'][$goal['id']]['delete_btn'] = [
      '#type' => 'button',
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
      '#prefix' => '<div class="col-md-2 col-sm-2 col-xs-2">',
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