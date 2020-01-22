<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Misc\TaskReminderUtils.
 */

namespace Drupal\bc_2movepeople_dashboard\Misc;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

/**
 * Class TaskReminderUtils.
 *
 * @package Drupal\bc_2movepeople_dashboard\\Misc
 */
class TaskReminderUtils {

  /**
   * Main wrapper function for checking and sending reminder to users and managers.
   */
  public static function bulkReminder() {
    $config = \Drupal::config('bc_2movepeople_dashboard.AdminSettings');
    $user_tasks = self::getDueDateGoals();

    foreach ($user_tasks as $uid => $goals_ids) {
      $user = NULL;
      foreach ($goals_ids as $nid) {
        $reminder_data = self::getReminderDataDB($nid);
        if (count($reminder_data) == 0 || $reminder_data['reminded_count'] < 1) {
          // Get objects.
          $user = is_null($user) ? User::load($uid) : $user;
          $to = $user->get('mail')->value;
          if (empty($to)) {
            continue;
          }
          $goal = Node::load($nid);

          // Prepare data.
          $subject = $config->get('task_reminder_email_subject');
          $body = $config->get('task_reminder_email_body');
          $body = str_replace("@name", $user->getDisplayName(), $body);
          $dashboardMailer = \Drupal::service('2movepeople_dashboard.mailer');
          $dashboardMailer->replaceDefaultTokens($body, [
            'recipient' => $user,
          ]);
          $body = str_replace("@task_title", $goal->get('title')->value, $body);
          $body = str_replace("@due_date", $goal->get('field_due_date')->value, $body);

          // Send email.
          $dashboardMailer->sendMail([
              'to' => $to,
              'from' => \Drupal::config('system.site')->get('mail'),
              'subject' => $subject,
              'body' => $body,
              'sender' => t('System notify'),
              'wpn_to' => $user,
          ]);

          // Set and update reminder data.
          self::setReminderDataDB($user, $goal, $reminder_data);
        }
      }
    }
  }

  /**
   * Returns a list of the tasks.
   *
   * Returns a list of the tasks for both user and managers which need to be
   * reminded about.
   * Compares tasks due date with current reminder date.
   *
   * @return array
   *   array(
   *     'user_uid' => array(
   *       '0' => goal_1_nid,
   *       '1' => goal_2_nid,
   *       ...
   *     );
   */
  public static function getDueDateGoals() {
    $result = array();

    $config = \Drupal::config('bc_2movepeople_dashboard.AdminSettings');
    $reminder_days = $config->get('task_reminder_due_date');

    $reminderDate = new DrupalDateTime('now');
    $reminderDate->modify('+' . $reminder_days . ' day');

    // Getting list of goals.
    $goals_query = \Drupal::entityQuery('node');
    $time_group = $goals_query->orConditionGroup()
      ->condition('field_due_date', $reminderDate->format(DateTimeItemInterface::DATE_STORAGE_FORMAT), '<=');
    $goals_query->condition('status', 1)
      ->condition('type', 'goal')
      ->condition('field_task_complete', FALSE)
      ->condition($time_group);
    $goals_ids = $goals_query->execute();
    $goals = Node::loadMultiple($goals_ids);

    // Looping though goals.
    foreach ($goals as $goal) {
      // Is manager goal.
      if (!$goal->get('field_responsible_manager')->isEmpty() && $goal->get('field_responsible_manager')->first()->getValue()['target_id'] != 0) {
        $manager_uid = $goal->get('field_responsible_manager')->first()->getValue()['target_id'];
        $result[$manager_uid][] = $goal->id();
      }
      // Is user goal.
      else {
        $progressions_query = \Drupal::entityQuery('node');
        $progressions_query->condition('status', 1);
        $progressions_query->condition('type', 'progression_target');
        $progressions_query->condition('field_progression_type', 'target_milestone');
        $progressions_query->condition('field_goal_ids', $goal->id());
        $progressions_ids = $progressions_query->execute();
        $ids = array_values($progressions_ids);
        $progression_id = reset($ids);

        if ($progression_id) {
          $progression = Node::load($progression_id);
          if (empty($progression)) {
            continue;
          }
          $user_id = $progression->get('field_progression_user')->getValue()[0]['target_id'];
          $result[$user_id][] = $goal->id();
        }
      }
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
