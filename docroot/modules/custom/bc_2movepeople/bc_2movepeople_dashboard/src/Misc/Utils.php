<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Misc\TaskReminderUtils.
 */

namespace Drupal\bc_2movepeople_dashboard\Misc;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\user\UserInterface;

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
    // For subgoals getting parent goal id.
    while ($parent_goal_id = self::getParentGoalId(Node::load($goal_id))) {
      $goal_id = $parent_goal_id;
    }

    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'progression_target');
    $query->condition('field_goal_ids', $goal_id, 'CONTAINS');
    $query->range(0, 1);
    $result = $query->execute();
    return empty($result) ? NULL : Node::load(array_pop($result));
  }

  /**
   * Gets goal node by goal node id.
   *
   * @param EntityInterface $goal_node
   *   Goal node object.
   *
   * @return bool
   */
  public static function getParentGoalId(EntityInterface $goal_node) {
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'goal');
    $query->condition('field_subgoal', $goal_node->id(), 'CONTAINS');
    $query->range(0, 1);
    $result = $query->execute();
    return empty($result) ? FALSE : array_pop($result);
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

  /**
   * Gets user organizations.
   *
   * @param UserInterface $user
   *
   * @return array
   */
  public static function getUserOrganizations(UserInterface $user) {
    $user_organization_tids = [];
    foreach ($user->get('field_organisation') as $item) {
      if ($value = $item->getValue()) {
        $user_organization_tids[] = $value['target_id'];
      }
    }
    return $user_organization_tids;
  }

}
