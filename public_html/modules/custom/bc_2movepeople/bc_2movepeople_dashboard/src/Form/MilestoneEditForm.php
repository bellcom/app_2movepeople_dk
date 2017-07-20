<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;


class MilestoneEditForm extends FormBase {

  protected $node;
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $milestone_node = null) {
    $this->node = $milestone_node;
    
//    $form['#attached']['library'][] = 'core/drupal.ajax';
//    $form['#attached']['library'][] = 'core/drupal.dialog';
//    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    
    $purpose = $milestone_node->get('field_purpose')->value;
    
    $form['purpose'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Purpose'),
      '#resizable' => 'none',
      '#rows' => 2,
      '#default_value' => $purpose,
      '#prefix' => '<div class="row"><div class="col-sm-12">',
      '#suffix' => '</div></div>'
          . '<div class="row custom-form-fields custom-form-label">'
          . '<div class="col-sm-3">'.$this->t('Milestone').'</div>'
          . '<div class="col-sm-3">'.$this->t('Activity').'</div>'
          . '<div class="col-sm-3">'.$this->t('Deadline').'</div>'
          . '<div class="col-sm-3">'.$this->t('Evaluation').'</div>'
          . '</div>'
    ];

    $raw_goal_ids = $milestone_node->get('field_goal_ids')->getValue();
     
    foreach ($raw_goal_ids as $tid) {
      $form['goals']['#tree'] = TRUE;
      
      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $milestone_node);

      $form['goals'][$goal_id]['title'] = [
        '#type' => 'textfield',
       // '#title' => $this->t('Milestone'),
        '#default_value' => $goal['title'],
        '#prefix' => '<div class="row custom-form-fields"><div class="col-sm-3">',
        '#suffix' => '</div>'

      ];

      $form['goals'][$goal_id]['activity_title'] = [
        '#type' => 'textfield',
       // '#title' => $this->t('Activity'),
        '#default_value' => $goal['activity_title'],
        '#prefix' => '<div class="col-sm-3">',
        '#suffix' => '</div>'
      ];

      $form['goals'][$goal_id]['due_date'] = [
        '#type' => 'date',
       // '#title' => $this->t('Deadline'),
        '#default_value' => $goal['date'],
        '#prefix' => '<div class="col-sm-3">',
        '#suffix' => '</div>'

      ];

      $form['goals'][$goal_id]['evaluation'] = [
        '#type' => 'textfield',
      //  '#title' => $this->t('Evaluation'),
        '#default_value' => $goal['evaluation'],
        '#prefix' => '<div class="col-sm-3">',
        '#suffix' => '</div></div>'

      ];
    }
    
//    $form['system_messages'] = [
//      '#markup' => '<div id="form-system-messages"></div>',
//      '#weight' => -100,
//    ];
    
    $form['actions'] = [
      '#type'  => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#name' => 'submit',  
        '#value' => $this->t('Update'),
        '#button_type' => 'primary',
        '#prefix' => '<div class="pull-right custom-form-fields">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::ajaxSubmitForm',
          'event' => 'click',
          'progress' => [
            'type' => 'throbber',
          ],
        ]
      )
    ];    

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-milestone-edit-form';
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $nid = $this->node->id();  
   
    if (!$form_state->getErrors()) {

      $purpose = $form_state->getValue('purpose');
      
      $node = \Drupal\node\Entity\Node::load($nid);
      $node->set("field_purpose", $purpose);
      $node->save();
      
      $goals_arr = $form_state->getValue('goals');

      foreach($goals_arr as $gid => $goal) {
        $goal_node = \Drupal\node\Entity\Node::load($gid);
        $goal_node->set("title", $goal['title']);
        $goal_node->set("field_activity_title", $goal['activity_title']);
        $goal_node->set("field_due_date", $goal['due_date']);
        $goal_node->set("field_evaluation", $goal['evaluation']);        
        $goal_node->save();
      }      
      drupal_set_message($this->t('Records successfully updated.'));
    }
    
    $message = [
        '#theme' => 'status_messages',
        '#message_list' => drupal_get_messages(),
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error'  => $this->t('Error message'),
          'warning'=> $this->t('Warning message'),
        ],
      ];
    $messages = \Drupal::service('renderer')->render($message);
    $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $messages));

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
       
}
