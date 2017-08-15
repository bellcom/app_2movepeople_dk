<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Drupal\node\Entity\Node;


class MilestoneEditForm extends FormBase {

  protected $node;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';
  
  public function __construct($nodedata) {
    $this->node = $nodedata;
  }  
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['#attached']['library'][] = 'bc_2movepeople_dashboard/bc_2movepeople_dashboard.milestone-edit';   
//    $form['#attached']['library'][] = 'core/drupal.ajax';
//    $form['#attached']['library'][] = 'core/drupal.dialog';
//    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    
    $purpose = $this->node->get('field_purpose')->value;
    
    $form['purpose'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Purpose'),
      '#resizable' => 'none',
      '#rows' => 2,
      '#default_value' => $purpose,
      '#prefix' => '<div class="row"><div class="col-sm-12">',
      '#suffix' => '</div></div>'
//          . '<div class="row custom-form-fields custom-form-label">'
//          . '<div class="col-md-3 col-sm-2 col-xs-2">'.$this->t('Milestone').'</div>'
//          . '<div class="col-md-2 col-sm-2 col-xs-2">'.$this->t('Activity').'</div>'
//          . '<div class="col-md-3 col-sm-4 col-xs-4">'.$this->t('Deadline').'</div>'
//          . '<div class="col-md-2 col-sm-2 col-xs-2">'.$this->t('Evaluation').'</div>'
//          . '<div class="col-md-2 col-sm-2 col-xs-2">'.$this->t('Actions').'</div>'
//          . '</div>'
    ];
    
    $form = CommonFormUtils::goalsContainer($form, $this->node);
    
//    $form['system_messages'] = [
//      '#markup' => '<div id="form-system-messages"></div>',
//      '#weight' => -100,
//    ];
//    
    $form['add_mt'] = [ 
      '#type' => 'link',
      '#title' => 'Add new task',
      '#name' => 'add_task_btn',
      '#url' => Url::fromRoute('bc_2movepeople_dashboard_milestone.task_add', array('node' => $this->node->id())),
      '#prefix' => '<div class="row custom-form-fields"><div class="col-md-10 col-sm-10 col-xs-10">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => ['use-ajax', 'btn', 'btn-info', 'link-btn'],
        'data-dialog-type' => 'modal',
//        'data-dialog-options' => Json::encode([
//          'width' => 700,
//        ]),
      ]
    ];
    
    // Disable caching on this form.
    $form_state->setCached(FALSE);
    
    $form['actions'] = [
      '#type'  => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#name' => 'submit',  
        '#value' => $this->t('Update'),
        '#button_type' => 'primary',
        '#prefix' => '<div class="col-md-2 col-sm-2 col-xs-2">',
        '#suffix' => '</div></div>',
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
    return 'bc_2movepeople-dashboard-milestone-edit-form-'.$this->node->id();
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
      drupal_set_message($this->t($this->updated_msg));
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
  public function ajaxGoalComplete(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    
    $goal_id = $form_state->getTriggeringElement()['#attributes']['data_goal_id']; 
    $goal = MovepeopleDashboardController::getGoal($goal_id, $this->node);
    $goal_node = \Drupal\node\Entity\Node::load($goal_id);

    if(is_object($goal_node)) {
      
      unset($form['goals'][$goal_id]['complete_btn']['#prefix']);
      unset($form['goals'][$goal_id]['complete_btn']['#suffix']);

      $form['goals'][$goal_id]['complete_btn']['#prefix'] = '<span id="complete_btn_box'.$goal_id.'">';
      $form['goals'][$goal_id]['complete_btn']['#suffix'] = '</span>';
      
      if ($goal['completed']) {
        $goal_node->set("field_task_complete", 0);
        $form['goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['false'];
        $form['goals'][$goal_id]['complete_btn']['#attributes']['class'] = ['btn', 'btn-primary', 'custom-checkbox-ok'];
      } else {
        $goal_node->set("field_task_complete", 1);
        $form['goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['true'];
        $form['goals'][$goal_id]['complete_btn']['#attributes']['class'] = ['btn', 'btn-primary', 'custom-checkbox-ok', 'active'];
      }      
      $goal_node->save();
      
      drupal_set_message($this->t($this->updated_msg));
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
      $ajax_response->addCommand(new ReplaceCommand('#complete_btn_box'.$goal_id, $form['goals'][$goal_id]['complete_btn']));
    }
    
    return $ajax_response;
  }
  
  /**
   * {@inheritdoc}
   */
  public function ajaxGoalDelete(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    
    $goal_id = $form_state->getTriggeringElement()['#attributes']['data_goal_id'];
    $this->node = Node::load($this->node->id());
    $old_goal_ids = $this->node->get('field_goal_ids')->getValue();

    $is_deleted = 0;
    $new_goal_ids = [];
    foreach($old_goal_ids as $tid) {
      if($tid['target_id'] != $goal_id) {
        $new_goal_ids[] = $tid['target_id'];
      } else {
        $is_deleted = 1;
      }
    }
    if ($is_deleted) {
      $this->node->set('field_goal_ids', $new_goal_ids);
      $this->node->save();
    } else {
      drupal_set_message($this->t($this->wrong_msg));
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
    }
    $ajax_response->addCommand(new RemoveCommand('#goal_row_'.$goal_id));
    
    return $ajax_response;
  }
 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
       
}
