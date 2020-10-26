<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\bc_2movepeople_rate_progression\Progression;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter;
use Drupal\field\Entity\FieldStorageConfig;

class Target {

  protected $goals;
  protected $node;

  public function __construct($progression_target_id) {
    $this->node = \Drupal::entityTypeManager()->getStorage('node')->load($progression_target_id);
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($progression_target_id);
    $mtid = $node->get('field_goal_ids')->getValue();
    foreach ($mtid as $tid) {
      $this->getGoals($tid['target_id']);
    }
  }

  private function getGoals($nodeid) {
    $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($nodeid);
    $subnodes = $nodedata->get('field_subgoal')->getValue();
    $this->goals[] = $nodeid;
    foreach ($subnodes as $tid) {
      $this->getGoals($tid['target_id']);
    }
  }

  public function getAllGoals() {
    return $this->goals;
  }

  public function getProgressionTarget() {
    return $this->node;
  }
  public function getProgressionTargetTitle() {
    return $this->node->get('title')->value;
  }
   public function getProgressionTargetRelatedTasks() {
      $related_tasks = false;
      if (!empty($this->node->field_related_tasks)) {
        $related_tasks = $this->node->get('field_related_tasks')->referencedEntities();
      }
    return $related_tasks;
  }

}
