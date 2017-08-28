<?php

/**
 * @file
 * Contains \Drupal\decreto_content_modify\Form\MeetingsEditForm.
 */

namespace Drupal\bc_2movepeople_rate_progression\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\bc_2movepeople_rate_progression\Progression\Target;

/**
 * Implements the ModalForm form controller.
 *
 * This example demonstrates implementation of a form that is designed to be
 * used as a modal form.  To properly display the modal the link presented by
 * the \Drupal\fapi_example\Controller\Page page controller loads the Drupal
 * dialog and ajax libraries.  The submit handler in this class returns ajax
 * commands to replace text in the calling page after submission .
 *
 * @see \Drupal\Core\Form\FormBase
 */
class RateAddForm extends FormBase {

  protected $node;
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $progression_target_id = null) {
    $this->node = $progression_target_id;
    $mtid = $this->node->get('field_goal_ids')->getValue();

    $form['#prefix'] = '<div id="bc_2movepeople-rate-progression-add-form">';
    $form['#suffix'] = '</div>';
    
    $progression_target = new Target($this->node->id());
    $goals =  $progression_target->getAllGoals();
    
    foreach ($goals as $id){
      $goaldata = \Drupal::entityTypeManager()->getStorage('node')->load($id);
      if (empty($goaldata->get('field_due_date')->value)) {
        $goaltitle = $goaldata->get('title')->value;
        $form['rate'][$id] = [
          '#type' => 'select',
          '#title' => $goaltitle,
          '#required' => FALSE,
          '#empty_option' => 'None',
          '#options' => [
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5'
          ]
        ];     
       
        }
      }     
    
    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-rate-progression-add-form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rates = $form_state->getValues('rate');
    foreach ($rates as $rate_id => $rate_value) {
      $node = \Drupal::entityQuery('node')->condition('nid', $rate_id)->execute();
      if (!empty($rate_value) && !(empty($node)))
        \Drupal::database()->insert('bc_2movepeople_rate_progression')
          ->fields(array(
            'progression_target_id' => $this->node->id(),
            'goal_id' => $rate_id,
            'rate' => $rate_value,
            'uid' => \Drupal::currentUser()->id(),
            'created' => time(),
          ))
          ->execute();
    }
  }

  /**
   * Implements the sumbit handler for the ajax call.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the modal form.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $progression_target = new Target($this->node->id());
    $goals =  $progression_target->getAllGoals();
    foreach ($goals as $id) {
      $rates = implode(' ' , bc_2movepeople_rate_progression_get_rates($this->node->id(), $id));
//      $rates = '<span id="progress_rates_' . $this->node->id(). '_' . $id . '">' . $rates . '</span>';   
//      $response->addCommand(new \Drupal\Core\Ajax\ReplaceCommand('#progress_rates_' . $this->node->id() . '_' . $id, $rates));
    }
    //$('#accordion').activate('activate', elementSelector);
    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand(NULL, 'graphReload', array('#div_chart_' . $this->node->id())));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }
  
 private function getGoals($nodeid){   
    $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($nodeid);      
      $subnodes = $nodedata->get('field_subgoal')->getValue();
      $this->goals[] = $nodeid;
      //$subgoals= array();
      foreach ($subnodes as $tid) {
        $this->goals[] =  $tid['target_id'];
         $this->getGoals($tid['target_id']);
       }  
       
  }       
}
