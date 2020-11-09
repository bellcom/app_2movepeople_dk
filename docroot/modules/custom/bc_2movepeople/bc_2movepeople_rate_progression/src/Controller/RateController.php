<?php

namespace Drupal\bc_2movepeople_rate_progression\Controller;

use Drupal\bc_2movepeople\Form\TextSettings;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\bc_2movepeople_rate_progression\Progression\Target;

/**
 * Class RateController.
 *
 * @package Drupal\bc_2movepeople_rate_progression\Controller
 */
class RateController extends ControllerBase {

  /**
   * Implementation create note endpoint.
   *
   * Creates a note, saves it in the database and redirects to the read
   * endpoint in order to update a note with generated ID.
   *
   * @param int $progression_target_id
   *   Id of progression.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON Object responce.
   */
  public function rateGet($progression_target_id) {
    $data = array(
      'chart_title' => '',
      'series' => array(),
      'values' => array(),
    );

    $progression_target = new Target($progression_target_id);
    $progression_target_data = $progression_target->getProgressionTarget();
    $goals = $progression_target->getAllGoals();

    $data['chart_title'] = $progression_target_data->get('title')->value;
    $date_to = empty($_GET['to']) ? NULL : strtotime($_GET['to']);
    $date_from = empty($_GET['from']) ? NULL : strtotime($_GET['from']);
    $rates = [];
    $dates = [];
    foreach ($goals as $key => $id) {
      $goal_id = $id;
      $result = self::getTargetAveragePoints($progression_target_id, $goal_id, $date_from, $date_to, 5);
      if (empty($result)) {
        continue;
      }
      foreach ($result as $row) {
        $rates[$id][$row->dates] = round($row->avg_rates, 2);
        if (!in_array($row->dates, $dates)) {
          $dates[] = $row->dates;
        }
      }
    }
    $dates = array_reverse($dates);
    $data = [
      'dates' => $dates,
      'goals' => [],
    ];
    foreach ($rates as $gid => $goal_rates) {
      $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($gid);

      $arr = [
        'label' => $nodedata->get('title')->value,
        'values' => [],
      ];
      foreach ($dates as $date) {
        $arr['values'][] = isset($goal_rates[$date]) ? $goal_rates[$date] : NULL;
      }
      $data['goals'][] = $arr;
    }
    return new JsonResponse($data);
  }

  /**
   * Dialogue rating pagecallback.
   */
  public function dialogueRate() {
    $user = \Drupal::currentUser();
    $form = \Drupal::formBuilder()->getForm('Drupal\bc_2movepeople_rate_progression\Form\UserRatesAddForm', $user->getAccount(), 'feedback');
    return $form;
  }

  /**
   * Self rating page title callback.
   */
  public function selfRateTitle() {
    return TextSettings::get('bc_2movepeople_rate_progresion.self_rates_add_title');
  }

  /**
   * Self rating pagecallback.
   */
  public function selfRate() {
    $user = \Drupal::currentUser();
    $form = \Drupal::formBuilder()->getForm('Drupal\bc_2movepeople_rate_progression\Form\UserRatesAddForm', $user->getAccount());
    return $form;
  }

  /**
   * Get average target rate.
   *
   * @params
   * $target_id - progression target id
   *
   * @return array
   *   Average value of rate.
   */
  public static function getTargetAveragePoints($target_id, $goal_id = NULL, $date_from = NULL, $date_to = NULL, $limit = NULL) {
    $query = \Drupal::database()->select('bc_2movepeople_rate_progression', 'rates');
    if ($goal_id) {
      $query->condition('goal_id', $goal_id, '=');
    }
    $query->condition('progression_target_id', $target_id, '=');
    $query->isNotNull('created');
    $query->addExpression("FROM_UNIXTIME(created,  '%d.%m')", 'dates');
    $query->addExpression("AVG(rate)", 'avg_rates');
    $query->addExpression("MAX(created)", 'created');
    $query->GroupBy('dates');
    $query->orderBy('created', 'DESC');
    if (isset($date_to)) {
      $query->condition('created', $date_to, '<=');
    }

    if (isset($date_from)) {
      $query->condition('created', $date_from, '>=');
    }

    if (!isset($date_to) && !isset($date_from) && $limit) {
      $query->range(0, $limit);
    }

    $result = $query->execute()->fetchAll();
    return $result;
  }

}
