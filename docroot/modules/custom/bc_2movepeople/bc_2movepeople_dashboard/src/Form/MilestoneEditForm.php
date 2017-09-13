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
  protected $user;
  private $updated_msg = 'Records successfully updated.';
  private $deleted_msg = 'Records successfully deleted.';
  private $wrong_msg = 'Something wrong.';
  
  public function __construct($nodedata, $user) {
    $this->node = $nodedata;
    $this->user = $user;
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
      '#prefix' => '<div class="row"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">',
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
      '#url' => Url::fromRoute('bc_2movepeople_dashboard.milestone.tasks.add', array('node' => $this->node->id())),
      '#prefix' => '<div class="row custom-form-fields dashboard-milestone__control-buttons"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 left-btn-box">',
      '#suffix' => '</div></div>',
      '#attributes' => [
        'class' => ['use-ajax', 'btn', 'btn-default', 'link-btn'],
        'data-dialog-type' => 'modal',
//        'data-dialog-options' => Json::encode([
//          'width' => 700,
//        ]),
      ]
    ];
    
    $form = CommonFormUtils::goalsContainer($form, $this->node, TRUE);
    
    $form['add_manager_mt'] = [ 
      '#type' => 'link',
      '#title' => 'Add new manager task',
      '#name' => 'add_manager_task_btn',
      '#url' => Url::fromRoute('bc_2movepeople_dashboard.milestone.manager.tasks.add', array('node' => $this->node->id())),
      '#prefix' => '<div class="row custom-form-fields dashboard-milestone__control-buttons"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 left-btn-box">',
      '#suffix' => '</div></div>',
      '#attributes' => [
        'class' => ['use-ajax', 'btn', 'btn-default', 'link-btn'],
        'data-dialog-type' => 'modal',
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
        '#prefix' => '<div class="row custom-form-fields dashboard-milestone__control-buttons"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 right-btn-box">',
        '#suffix' => '</div></div>',
        '#attributes' => [
          'class' => ['btn-default'],
        ],
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
      
      $node = Node::load($nid);
      $node->set("field_purpose", $purpose);
      $node->save();
      
      $goals_arr = $form_state->getValue('goals');
      foreach($goals_arr as $gid => $goal) {
        $goal_node = Node::load($gid);
        $goal_node->set("title", $goal['title']);
        $goal_node->set("field_activity_title", $goal['activity_title']);
        $goal_node->set("field_due_date", $goal['due_date']);
        $goal_node->set("field_evaluation", $goal['evaluation']);        
        $goal_node->save();
      }
      // see prefix in CommonFormUtils
      $goals_manager_arr = $form_state->getValue('manager_goals');
      foreach($goals_manager_arr as $gid => $goal) {
        $goal_node = Node::load($gid);
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
      '#message_list' => drupal_get_messages()
    ];
    $messages = \Drupal::service('renderer')->render($message);
    $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $messages));

    return $ajax_response;
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function ajaxGoalComplete(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    
    $goal_id = $form_state->getTriggeringElement()['#attributes']['data_goal_id'];
    $prefix = $form_state->getTriggeringElement()['#attributes']['data_prefix']; 
    $goal = MovepeopleDashboardController::getGoal($goal_id, $this->node);
    $goal_node = Node::load($goal_id);

    if(is_object($goal_node)) {
      
      unset($form[$prefix.'goals'][$goal_id]['complete_btn']['#prefix']);
      unset($form[$prefix.'goals'][$goal_id]['complete_btn']['#suffix']);

      $form[$prefix.'goals'][$goal_id]['complete_btn']['#prefix'] = '<span id="complete_btn_box'.$goal_id.'">';
      $form[$prefix.'goals'][$goal_id]['complete_btn']['#suffix'] = '</span>';
      
      if ($goal['completed']) {
        $goal_node->set("field_task_complete", 0);
        $form[$prefix.'goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['false'];
        $form[$prefix.'goals'][$goal_id]['complete_btn']['#attributes']['class'] = ['btn', 'btn-primary', 'custom-checkbox-ok'];
      } else {
        $goal_node->set("field_task_complete", 1);
        $form[$prefix.'goals'][$goal_id]['complete_btn']['#attributes']['aria-pressed'] = ['true'];
        $form[$prefix.'goals'][$goal_id]['complete_btn']['#attributes']['class'] = ['btn', 'btn-primary', 'custom-checkbox-ok', 'active'];
      } 

      if ($goal_node->save() == SAVED_UPDATED) {
        
        if($goal_node->get('field_task_complete')->value && $this->user->get('mail')->value) {
          
          $config = $this->config('bc_2movepeople_dashboard.AdminSettings');
          $subject = $config->get('task_complete_email_subject');
          $body = $config->get('task_complete_email_body');

          $body = str_replace("@name", $this->user->get('name')->value, $body);
          $body = str_replace("@user", \Drupal::currentUser()->getDisplayName(), $body);
          $body = str_replace("@task_title", $goal_node->get('title')->value, $body);
          
          CommonFormUtils::sendMail([
            'to' => $this->user->get('mail')->value,
            'from' => \Drupal::config('system.site')->get('mail'),
            'subject' => $subject,
            'body' => $body,
            'sender' => $this->t('System notify')
          ]);
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
      $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $messages));
      $ajax_response->addCommand(new ReplaceCommand('#complete_btn_box'.$goal_id, $form[$prefix.'goals'][$goal_id]['complete_btn']));
    }
    
    return $ajax_response;
  }
  
  /**
   * {@inheritdoc}
   */
  public function ajaxGoalDelete(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    
    $goal_id = $form_state->getTriggeringElement()['#attributes']['data_goal_id'];
    //$this->node = Node::load($this->node->id());
    $old_goal_ids = $this->node->get('field_goal_ids')->getValue();

    $is_deleted = 0;
    $new_goal_ids = [];
    foreach($old_goal_ids as $tid) {
      if($tid['target_id'] != $goal_id) {
        $new_goal_ids[] = $tid['target_id'];
      } else {
        $goal_node = Node::load($goal_id);
        $goal_node->delete();
        $is_deleted = 1;
      }
    }
    if ($is_deleted) {
      $this->node->set('field_goal_ids', $new_goal_ids);
      $this->node->save();
      drupal_set_message($this->t($this->deleted_msg));
    } else {
      drupal_set_message($this->t($this->wrong_msg));
    }
    $ajax_response->addCommand(new RemoveCommand('#goal_row_'.$goal_id));
    
    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages()
    ];
    $messages = \Drupal::service('renderer')->render($message);
    $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $messages));
    
    return $ajax_response;
  }
 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
       
}
