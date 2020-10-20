<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Misc\TaskReminderUtils.
 */

namespace Drupal\bc_2movepeople_dashboard\Misc;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\user\UserInterface;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

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

  /**
   * Renders PDF file
   *
   * @param $data
   * @param $filename
   */
  public static function downloadPdfFile($data, $filename) {
    header('Content-Description: File Transfer');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: public, must-revalidate, max-age=0');
    header('Pragma: public');
    header('X-Generator: mPDF ' . Mpdf::VERSION);
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Content-Type: application/pdf');

    if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
      // don't use length if server using compression
      header('Content-Length: ' . strlen($data));
    }

    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $data;
    return exit;
  }

  /**
   * Renders markup with absolute URLs.
   */
  public static function renderMarkup($build) {
    $html = \Drupal::service('renderer')->renderRoot($build);
    return Html::transformRootRelativeUrlsToAbsolute($html, \Drupal::request()->getSchemeAndHttpHost());
  }

  /**
   * Render PDF source.
   *
   * @param array $build
   *   Build array.
   *
   * @return string
   *   Rendered PDF string.
   * @throws \Mpdf\MpdfException
   */
  public static function renderPdfFileSource($build) {
    $html = self::renderMarkup($build);
    $mpdf = new Mpdf(['tempDir' => 'sites/default/files/tmp']);
    $mpdf->WriteHTML($html);
    return $mpdf->Output('evaluation.pdf', Destination::STRING_RETURN);
  }

}
