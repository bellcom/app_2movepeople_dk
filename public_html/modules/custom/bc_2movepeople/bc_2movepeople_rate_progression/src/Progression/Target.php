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
   
      public function __construct($progression_target_id){
        
        $this->node = \Drupal::entityTypeManager()->getStorage('node')->load($progression_target_id);
        $mtid = $this->node->get('field_progression_target')->getValue();
        foreach ($mtid as $tid) {
            $this->getGoals($tid['target_id']);
          }   
      }
 
     private function getGoals($nodeid){   
    $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($nodeid);      
      $subnodes = $nodedata->get('field_subgoal')->getValue();
      $this->goals[] = $nodeid;
      //$subgoals= array();
      foreach ($subnodes as $tid) {
         $this->getGoals($tid['target_id']);
       }  
       
  }       
    
   public function getAllGoals(){
     return $this->goals;     
   }
   public function getProgressionTarget(){
     return $this->node;     
   }
   
}