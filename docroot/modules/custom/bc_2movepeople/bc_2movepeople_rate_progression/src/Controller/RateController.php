<?php

namespace Drupal\bc_2movepeople_rate_progression\Controller;

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
   * Returns progression target rates.
   *
   * Used for charts drawing.
   *
   * @param int $progression_target_id
   *   Id of progression.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON object build as:
   *   {
   *     label: [
   *       "Label 1",
   *       "Label 2"
   *       ...
   *     ],
   *     datasets: [
   *       {
   *          values: [0,1,3]
   *       },
   *       {
   *          values: [2,3,4]
   *       },
   *       ...
   *     ]
   *   }
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function rateGet($progression_target_id) {
    $data = array(
      'labels' => array(),
      'datasets' => array(),
    );

    $progression_target = new Target($progression_target_id);
    $goals = $progression_target->getAllGoals();

    $rates = array();
    isset($_GET['to']) ? $date_to = strtotime($_GET['to']) : NULL;
    isset($_GET['from']) ? $date_from = strtotime($_GET['from']) : NULL;

    foreach ($goals as $goalDelta => $goalId) {
      $goalNode = \Drupal::entityTypeManager()->getStorage('node')->load($goalId);
      $query = \Drupal::database()->select('bc_2movepeople_rate_progression', 'rates');
      $query->fields('rates', array('rate'))
        ->condition('goal_id', $goalId, '=')
        ->condition('progression_target_id', $progression_target_id, '=')
        ->condition('status', TRUE);
      if (isset($date_to)) {
        $query->condition('created', $date_to, '<=');
      }

      if (isset($date_from)) {
        $query->condition('created', $date_from, '>=');
      }

      $query->orderBy('created', 'ACS');
      if (!isset($date_to) && !isset($date_from)) {
        $query->range(0, 5);
      }

      $result = $query->execute()->fetchAll();
      if ($result) {
        $data['labels'][$goalDelta] = $goalNode->get('title')->value . ' ' . $goalNode->id();
      }
      foreach ($result as $row) {
        $rates[$goalDelta][] = (int) $row->rate;
      }
    }

    foreach ($rates as $goalDelta => $goalRates) {
      if (!is_array($goalRates)) {
        $goalRates = array($goalRates);
      }
      $data['datasets'][$goalDelta]['values'] = $goalRates;
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
   * Self rating pagecallback.
   */
  public function selfRate() {
    $user = \Drupal::currentUser();
    $form = \Drupal::formBuilder()->getForm('Drupal\bc_2movepeople_rate_progression\Form\UserRatesAddForm', $user->getAccount());
    return $form;
  }

}
