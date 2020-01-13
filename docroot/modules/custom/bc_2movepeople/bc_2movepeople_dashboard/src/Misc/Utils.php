<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Misc\TaskReminderUtils.
 */

namespace Drupal\bc_2movepeople_dashboard\Misc;

use Drupal\node\Entity\Node;

/**
 * General utils wrapper.
 *
 * @package Drupal\bc_2movepeople_dashboard\Misc
 */
class Utils {

  /**
   * Gets Milestone node by goal node id.
   *
   * @param $goal_id
   *   Goal node id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public static function getMilestoneByGoal($goal_id) {
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'progression_target');
    $query->condition('field_goal_ids', $goal_id, 'CONTAINS');
    $query->range(0, 1);
    $result = $query->execute();
    return empty($result) ? NULL : Node::load(array_pop($result));
  }

  public static function getMilestoneByUserId($user_id) {
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'progression_target');
    $query->condition('field_progression_type', 'target_milestone');
    $query->condition('field_progression_user', $user_id);
    $result = $query->execute();
    return empty($result) ? NULL : Node::loadMultiple($result);
  }

}
