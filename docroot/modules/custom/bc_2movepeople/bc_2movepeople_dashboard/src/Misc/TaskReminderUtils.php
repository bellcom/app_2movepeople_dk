<?php

namespace Drupal\bc_2movepeople_dashboard\Misc;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

/**
 * Controller for js_example pages.
 *
 * @ingroup js_example
 */
class TaskReminderUtils  {
 
  /**
   * main wrapper function for checking and sending reminder to users
   *
   * @params
   *
   * @return
   *
   */
  public static function bulkReminder() {
    
    $config = \Drupal::config('bc_2movepeople_dashboard.AdminSettings');
    $user_tasks = self::getUsersDueDateGoals();
    
    foreach ($user_tasks AS $uid => $goals_ids) {
      
      $user = null;
      foreach ($goals_ids AS $nid) {
        
        $reminder_data = self::getReminderDataDB($nid);
        
        if (sizeof($reminder_data) == 0  || $reminder_data['reminded_count'] < 1) {
         
          // Get objects
          $user = is_null($user) ? User::load($uid) : $user;
          $goal = Node::load($nid);
         
          // Prepare data
          $subject = $config->get('task_reminder_email_subject');
          $body = $config->get('task_reminder_email_body');
          $body = str_replace("@name", $user->getDisplayName(), $body);
          $body = str_replace("@task_title", $goal->get('title')->value, $body);
          $body = str_replace("@due_date", $goal->get('field_due_date')->value, $body);
          
          $to = $user->get('mail')->value;
          $to = 'evgeny@bellcom.ee'; // test HACK

          // Send email
          MovepeopleDashboardController::sendMail([
              'to' => $to,
              'from' => \Drupal::config('system.site')->get('mail'),
              'subject' => $subject,
              'body' => $body,
              'sender' => t('System notify')
          ]);

          // Set and update reminder data
          self::setReminderDataDB($user, $goal, $reminder_data);
        }
      }  
    }
  }
  
  /**
   * compares tasks due date with current reminder date 
   *
   * @params
   *
   * @return associative array (key - user id, value - goals ids)
   *
   */
  public static function getUsersDueDateGoals() {
    
    $result = array();
    $config = \Drupal::config('bc_2movepeople_dashboard.AdminSettings');
    $reminder_days = $config->get('task_reminder_due_date');
       
    // $current_date = new DrupalDateTime('now');
    $current_date_add = new DrupalDateTime('now');
    $current_date_add->modify('+'.$reminder_days.' day');
    
    $goals_query = \Drupal::entityQuery('node');
    
    $time_group = $goals_query->orConditionGroup()
    //  ->condition('field_due_date', $current_date->format(DATETIME_DATE_STORAGE_FORMAT), '<')
      ->condition('field_due_date', $current_date_add->format(DATETIME_DATE_STORAGE_FORMAT), '<=');
    
    $goals_query->condition('status', 1)
      ->condition('type', 'goal')
      ->condition('field_is_manager_task', FALSE)
      ->condition('field_task_complete', FALSE)
      ->condition($time_group);
    $goals_ids = $goals_query->execute();
    
    foreach ($goals_ids AS $nid) {
      $progressions_query = \Drupal::entityQuery('node');
      $progressions_query->condition('status', 1);
      $progressions_query->condition('type', 'progression_target');
      $progressions_query->condition('field_progression_type', 'target_milestone');
      $progressions_query->condition('field_goal_ids', $nid);
      $progressions_ids = $progressions_query->execute();
      
      $progression_id = array_shift(array_values($progressions_ids)); // ???
      
      $progression = Node::load($progression_id);
      $user_id = $progression->get('field_progression_user')->getValue()[0]['target_id'];

      $result[$user_id][] = $nid; 
    }

    return $result;
  }

  /**
   * return reminder data from DB
   *
   * @params
   * $nid - goal id
   *
   * @return array
   *
   */
  public static function getReminderDataDB($nid) {
    
    $result = array();
      
    $query = \Drupal::database()->select('bc_2movepeople_dashboard_task_reminder', 'tr');
    $query->fields('tr', ['nid', 'reminded_count', 'email', 'reminded_date']);
    $query->condition('nid', $nid);
    $query_result = $query->execute();

    while ($row = $query_result->fetchAssoc()) {
      $result = $row;
    }  

    return $result;
  }
  
  /**
   * insert/update reminder data in DB
   *
   * @params
   * $user - User object
   * $goal - Goal object
   * $reminder_data - array
   *
   * @return
   *
   */
  public static function setReminderDataDB($user, $goal, $reminder_data = array()) {
    
    $current_date = new DrupalDateTime('now');
    $current_unixtimestamp = $current_date->getTimestamp();
    $email = $user->get('mail')->value;
    
    // UPDATE
    if ($reminder_data && sizeof($reminder_data) > 0) {

      $reminded_count = $reminder_data['reminded_count'] ? $reminder_data['reminded_count'] : 0;
      $reminded_count++;
      
      $query = \Drupal::database()->update('bc_2movepeople_dashboard_task_reminder');
      $query->fields([
        'email' => $email,
        'reminded_count' => $reminded_count,
        'reminded_date' => $current_unixtimestamp,
      ]);
      $query->condition('nid', $goal->id());
      $query->execute();
    } 
    // INSERT
    else {
      $query = \Drupal::database()->insert('bc_2movepeople_dashboard_task_reminder');
      $query->fields([
        'nid' => $goal->id(),
        'email' => $email,
        'reminded_count' => 1,
        'reminded_date' => $current_unixtimestamp,
      ])->execute();
    }
  }

}