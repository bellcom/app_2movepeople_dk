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

  public static function getMilestoneByGoal($goal_id) {
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'progression_target');
    $query->condition('field_goal_ids', $goal_id, 'CONTAINS');
    $query->range(0, 1);
    $result = $query->execute();
    return empty($result) ? NULL : Node::load(array_pop($result));
  }

}
