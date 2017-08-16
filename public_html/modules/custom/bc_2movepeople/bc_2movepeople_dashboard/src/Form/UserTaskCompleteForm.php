<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\user\UserInterface;
//use Drupal\node\Entity\Node;
//use Drupal\Core\Url;

class UserTaskCompleteForm extends FormBase {

  private $node;
  private $user;
  protected $isSaved;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL, NodeInterface $node = NULL) {
    $this->node = $node;
    $this->user = $user;
    
    list($year, $month, $day) = explode('-', $node->get('field_due_date')->value);
    
    $form['info'] = [
      '#markup' => ''
          . '<br/>'
          . '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-title">'.$this->t('Milestone').'</div></div>'
          . '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-box">'.$node->get('title')->value.'</div></div>'
          . '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-title">'.$this->t('Activity').'</div></div>'
          . '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-box">'.$node->get('field_activity_title')->value.'</div></div>'
          . '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-title">'.$this->t('Deadline').'</div></div>'
          . '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-box">'.$day.'-'.$month.'-'.$year.'</div></div>'
          . '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-title">'.$this->t('Evaluation').'</div></div>'
          . '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-box">'.$node->get('field_evaluation')->value.'</div></div><br/>'
    ];
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',  
      '#value' => $this->t('Complete'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxCompleteForm',
        'event' => 'click',
        'progress' => [
        //  'type' => 'none',
        ],
      ],
      '#prefix' => '<div class="row"><div class="col-sm-6">',
      '#suffix' => '</div>',
    ];
    
    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxCloseForm',
        'event' => 'click',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#prefix' => '<div class="col-sm-6 col-right">',
      '#suffix' => '</div></div>',
    ];

    return $form;
  }
  
  /**
   * Simply closes pop-up dialog with ajax
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   */
  public function ajaxCloseForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new CloseModalDialogCommand());
    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-user-task-complete-form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function ajaxCompleteForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse(); 
    if ($this->isSaved == SAVED_UPDATED) {
      $ajax_response->addCommand(new RemoveCommand('#task-row-'.$this->node->id()));
      $ajax_response->addCommand(new CloseModalDialogCommand());
    }
    
    return $ajax_response;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->node->set("field_task_complete",TRUE);
    $this->isSaved = $this->node->save();
    
    if ($this->isSaved == SAVED_UPDATED) {
      //\Drupal::currentUser()->id()
      $query = \Drupal::entityQuery('user');
      $query->condition('status', 1);
      $query->condition('field_connected_users', $this->user->id());
      $mp_admin_ids = $query->execute();
      
      $config = $this->config('bc_2movepeople_dashboard.AdminSettings');
      $body = $config->get('task_complete_email_body');
      $subject = $config->get('task_complete_email_subject');
      
      $body = str_replace("@user", $this->user->get('name')->value, $body);
      $body = str_replace("@task_title", $this->node->get('title')->value, $body);

      foreach ($mp_admin_ids as $mp_id) {
        $mp_admin = \Drupal\user\Entity\User::load($mp_id);
        $to = $mp_admin->get('mail')->value;

        CommonFormUtils::sendMail([
            'to' => $to,
            'from' => $from = \Drupal::config('system.site')->get('mail'),
            'subject' => $subject,
            'body' => $body,
            'sender' => $this->t('System notify')
        ]);
      }
    } // SAVED_UPDATED
  }
       
}
