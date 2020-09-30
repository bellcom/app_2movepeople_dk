<?php

namespace Drupal\bc_2movepeople_rate_progression\Controller;

use Drupal\bc_2movepeople\Form\TextSettings;
use Drupal\Core\Controller\ControllerBase;
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
    $rates = array();
    isset($_GET['to']) ? $date_to = strtotime($_GET['to']) : NULL;
    isset($_GET['from']) ? $date_from = strtotime($_GET['from']) : NULL;

    foreach ($goals as $key => $id) {
      $goal_id = $id;
      $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($goal_id);
      $query = \Drupal::database()->select('bc_2movepeople_rate_progression', 'rates');
      $query->fields('rates', array('rate'))
       // ->condition('uid', \Drupal::currentUser()->id(), '=')
        ->condition('goal_id', $goal_id, '=')
        ->condition('progression_target_id', $progression_target_id, '=')
        ->condition('status', TRUE);
      if (isset($date_to)) {
        $query->condition('created', $date_to, '<=');
      }

      if (isset($date_from)) {
        $query->condition('created', $date_from, '>=');
      }

      $query->orderBy('created', 'DESC');
      if (!isset($date_to) && !isset($date_from)) {
        $query->range(0, 5);
      }

      $result = $query->execute()->fetchAll();
      if ($result) {
        $data['series'][$key] = $nodedata->get('title')->value;
      }
      foreach ($result as $row) {
        $rates[$key][] = (int) $row->rate;
      }
    }
    if (is_array($rates) && count($rates) > 0) {
      array_unshift($rates, NULL);
      $rates = array_reverse(call_user_func_array("array_map", $rates));
    }
    foreach ($rates as $key => $val) {
      if (!is_array($val)) {
        $val = array($val);
      }
      $data['values'][$key] = array_merge(array(" "), $val);
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

}
