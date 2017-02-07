<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\bc_2movepeople_rate_progression\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RateController extends ControllerBase {

  /**
   * Implementation create note endpoint.
   * Creates a note, saves it in the database and redirects to the read endpoint in order to update a note with generated ID.
   *
   * @return none.
   */
  public function rateGet($progression_target_id) {
    $data = array(
      'chart_title' => '',
      'series' => array(),
      'values' => array(),
    );
    $progression_target_data = \Drupal::entityTypeManager()->getStorage('node')->load($progression_target_id);
    $mtid = $progression_target_data->get('field_progression_target')->getValue();
    $data['chart_title'] = $progression_target_data->get('title')->value;
    $rates = array();

    foreach ($mtid as $key => $tid) {
      $goal_id = $tid['target_id'];
      $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($goal_id);
      //$data['series'][$key] = $nodedata->get('title')->value;
      $query = \Drupal::database()->select('bc_2movepeople_rate_progression', 'rates');
      $query->fields('rates', array('rate'))
        ->condition('uid', \Drupal::currentUser()->id(), '=')
        ->condition('goal_id', $goal_id, '=')
        ->condition('progression_target_id', $progression_target_id, '=')
        ->orderBy('created', 'DESC')
        ->range(0, 5);
      $result = $query->execute()->fetchAll();
      if ($result) 
        $data['series'][$key] = $nodedata->get('title')->value;
      foreach ($result as $row) {
        $rates[$key][] = (int) $row->rate;
      }
    }
    array_unshift($rates, null);
    $rates = array_reverse(call_user_func_array("array_map", $rates));
    foreach ($rates as $key => $val) {
      if (!is_array($val))
        $val = array($val);
      $data['values'][$key] = array_merge(array(" "), $val);
    }

    return new JsonResponse($data);
  }

}
