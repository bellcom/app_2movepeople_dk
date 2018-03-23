<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Cache\Cache;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

class ProgressionTargetMilestoneEvaluateForm extends FormBase {

  private $node;
  private $limit;
  private $updated_msg = 'Records successfully updated.';
  private $deleted_msg = 'Records successfully deleted.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-progression-edit-form';
  }
    
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $limit = NULL) {
    $this->node = $node;
    $this->limit = $limit;
    
    $form['#prefix'] = '<div class="dashboard-overview">';
    $form['#suffix'] = '</div>';

    $form = CommonFormUtils::tasksEvaluateContainer($form, $this->node);

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    $user = $this->node->get('field_progression_user')->getValue();

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
      '#attributes' => [
        'class' => ['btn-default'],
      ],
      '#prefix' => '<div class="col-md-6 col-sm-6 col-xs-12 right-btn-box">',
        //'#suffix' => '</div>',
    ];


    return $form;
  }

  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    if (!$form_state->getErrors()) {

      $goals_arr = $form_state->getValue('goals');

      foreach ($goals_arr as $gid => $goal) {
        $goal_node = \Drupal\node\Entity\Node::load($gid);
        $goal_node->set("field_evaluation", $goal['field_evaluation']);
        $goal_node->save();
      }

      if ($this->node->save() == SAVED_UPDATED) {

        drupal_set_message($this->t($this->updated_msg));
        $user = $this->node->get('field_progression_user')->getValue();
        //  $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user[0]['target_id']]));

        // Invalidate navigation block cachetag.
        Cache::invalidateTags(array('feedback:' . $user[0]['target_id']));
      }
      $ajax_response->addCommand(new CloseModalDialogCommand());
    }
    else {
      drupal_set_message($this->t($this->wrong_msg));
    }

    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
//      '#status_headings' => [
//        'status' => $this->t('Status message'),
//        'error'  => $this->t('Error message'),
//        'warning'=> $this->t('Warning message'),
//      ],
    ];
    $messages = \Drupal::service('renderer')->render($message);
    $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $messages));

    return $ajax_response;
  }

}
