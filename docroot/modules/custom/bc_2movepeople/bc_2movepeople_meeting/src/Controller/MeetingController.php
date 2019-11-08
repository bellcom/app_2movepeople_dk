<?php

namespace Drupal\bc_2movepeople_meeting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Contains MovepeopleDashboardController.
 */
class MeetingController extends ControllerBase {

  /**
   * Meetings overview implementation.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   User which meetings belong to.
   *
   * @return array
   *   A renderable array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function meetingsOverviewContent(AccountInterface $user) {
    $title = t('Click on each section to expand or collapse the meetings');

    // Build using our theme. This gives us content, which is not a good
    // practice,.
    $entity_ids = self::getUserMeetings($user->id());

    $meetings = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($entity_ids);
    $meetingsRendered = [];

    $view_builder = \Drupal::entityTypeManager()
      ->getViewBuilder('node');

    foreach ($meetings as $meeting) {
      $meetingsRendered[] = $view_builder
        ->view($meeting, 'teaser');
    }

    $build = [
      '#theme' => 'bc_2movepeople_meeting_meetings_overview',
      '#title' => t('Meetings'),
      '#subtitle' => $title,
      '#user' => $user->id(),
      '#meetings' => $meetingsRendered,
    ];
    return $build;
  }

  /**
   * Return progression targets for user.
   *
   * @params
   * $user_id - user uuid
   *
   * @return array
   *   Array with progressions.
   */

  /**
   * Returns ids for user meetings.
   *
   * @param int $user_id
   *   Id of the user.
   *
   * @return array
   *   Array of the meetings associated with the user.
   */
  public static function getUserMeetings($user_id) {
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'meeting');
    $query->condition('field_meeting_user', $user_id);
    $query->sort('field_meeting_start_date', 'DESC');
    $entity_ids = $query->execute();

    return $entity_ids;
  }

  /**
   * Returns ids for user upcoming meetings.
   *
   * @param int $user_id
   *   Id of the user.
   *
   * @return array
   *   Array of the meetings associated with the user.
   */
  public static function getUserUpcomingMeetings($user_id) {
    $now = new DrupalDateTime('now');

    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'meeting');
    $query->condition('field_meeting_user', $user_id);
    $query->condition('field_meeting_start_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>');
    $query->sort('field_meeting_start_date', 'DESC');
    $entity_ids = $query->execute();

    return $entity_ids;
  }



}
