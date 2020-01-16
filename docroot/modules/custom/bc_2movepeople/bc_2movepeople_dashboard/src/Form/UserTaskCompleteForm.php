<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\bc_2movepeople_dashboard\Bc2movepeopleDashboardMailerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\user\UserInterface;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Symfony\Component\DependencyInjection\ContainerInterface;

//use Drupal\node\Entity\Node;
//use Drupal\Core\Url;

class UserTaskCompleteForm extends FormBase {

  private $node;
  private $user;
  protected $isSaved;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * Dashboard mailer service.
   *
   * @var Bc2movepeopleDashboardMailerInterface
   */
  protected $dashboardMailer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('2movepeople_dashboard.mailer')
    );
  }

  /**
   * MeetingAddForm constructor object.
   */
  public function __construct(Bc2movepeopleDashboardMailerInterface $dashboard_mailer) {
    $this->dashboardMailer = $dashboard_mailer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL, NodeInterface $node = NULL) {
    $this->node = $node;
    $this->user = $user;

    list($year, $month, $day) = explode('-', $node->get('field_due_date')->value);

    //We show Evaluation only for specific roles
    $current_user_roles = \Drupal::currentUser()->getRoles();
    $evaluation_enable_roles = array('2mp_manager');

    if(!empty(array_intersect($evaluation_enable_roles, $current_user_roles))){
      $evalution_markup = '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-title">'.$this->t('Evaluation').'</div></div>'
          . '<div class="row task-complete-ajax-form-row">'
          . '<div class="col-sm-12 task-complete-ajax-form-box">'.$node->get('field_evaluation')->value.'</div></div>';
    }else{
      $evalution_markup = NULL;
    }

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
          . $evalution_markup
          . '<br/>'
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

      $body = str_replace("@name", \Drupal::currentUser()->getDisplayName(), $body);
      $body = str_replace("@user", $this->user->get('name')->value, $body);
      $body = str_replace("@task_title", $this->node->get('title')->value, $body);

      foreach ($mp_admin_ids as $mp_id) {
        $mp_admin = \Drupal\user\Entity\User::load($mp_id);
        $to = $mp_admin->get('mail')->value;

        $this->dashboardMailer->sendMail([
            'to' => $to,
            'from' => \Drupal::config('system.site')->get('mail'),
            'subject' => $subject,
            'body' => $body,
            'sender' => $this->t('System notify')
        ]);
      }
    } // SAVED_UPDATED
  }

}
