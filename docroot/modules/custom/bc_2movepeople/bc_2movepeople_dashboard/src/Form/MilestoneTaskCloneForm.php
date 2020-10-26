<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
//use Drupal\Core\Url;
use Drupal\Core\Ajax\HtmlCommand;
//use Drupal\Core\Ajax\AppendCommand;
//use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

class MilestoneTaskCloneForm extends FormBase {

  protected $node;
  protected $user;
  protected $isSaved;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL, NodeInterface $node = NULL) {
    $this->node = $node;
    $this->user = $user;

    $progression_options = array();
    $entity_ids = MovepeopleDashboardController::getProgressionTargets($user->id());
    $progression_nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);

    foreach ($progression_nodes as $progrdata) {
      $progression_options[$progrdata->id()] = $progrdata->get('title')->value;
    }
    if (!empty($progression_options)) {
      $progression_options['other'] = $this->t('Anden ...');
      $default_category = array_search($this->node->getTitle(), $progression_options);
    }
    $counter = $form_state->getValue('counter');

    $form['#prefix'] = '<div id="bc_2movepeople-dashboard-task-clone-form">';
    $form['#suffix'] = '</div>';

    $form['system_messages'] = [
      '#markup' => '<div id="clone-task-form-system-messages"></div>',
      '#weight' => -100,
    ];
    if (empty($counter)) {
      $counter = $form_state->setValue('counter', 1);
    }

    $form['progression_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $progression_options,
      '#empty_option' => $this->t('-Select category-'),
      '#default_value' => $default_category ? $default_category : 'other',
      '#required' => FALSE,
      '#ajax' => [
        'callback' => '::changeParentQuestionOptionsAjax',
        'event' => 'change',
        'progress' => ['type' => 'throbber', 'message' => ''],
        'wrapper' => 'parent_task_wrapper',
      ],
    ];
     $form['counter'] = [
      '#type' => 'value',
      '#value' => $counter,
    ];


      $form['progression_other_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nyt kategori'),
      '#default_value' => $this->node->getTitle(),
      '#states' => [
        'visible' => [
          ':input[name=progression_id]' => ['value' => 'other'],
        ],
        'required' => [
          ':input[name=progression_id]' => ['value' => 'other'],
        ],
      ]
    ];
    $form['progerssion_tasks'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="progerssion_tasks-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $counter; $i++) {
      $form['progerssion_tasks']['task_title'][$i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Spørgsmål'),
        '#default_value' => ($i == 0 ) ? $this->node->get('field_activity_title')->value : ''
      ];
    }
  $form['add-more'] = [
      '#value' => t('Tilføj mere spørgsmål'),
      '#name' => 'add more',
      '#ajax' => [
        'wrapper' => 'progerssion_tasks-wrapper',
        'callback' => '::ajaxAddmoreCallback',
        'event' => 'click',
      ],
      '#submit' => ['::submitAddMore'],
      '#type' => 'submit',
      '#prefix' => '<div class="add-more-elements">',
      '#suffix' => '</div>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
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
    return 'bc_2movepeople-dashboard-task-clone-form';
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function ajaxSubmitForm(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $message = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
    ];

    if ($this->isSaved == SAVED_UPDATED) {
      $ajax_response->addCommand(new CloseModalDialogCommand());
      $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $message));
    } else {
      $ajax_response->addCommand(new HtmlCommand('#clone-task-form-system-messages', $message));
    }

    return $ajax_response;
  }

  /**
   * Ajax callback to change options for Parent Question.
   */
  public function changeParentQuestionOptionsAjax(array &$form, FormStateInterface $form_state) {
	return $form['parent_task_id'];
  }

  /**
   * Get options for Parent Question.
   */
  public function getParentQuestionOptions(FormStateInterface $form_state) {

    $options = array();
    $progression_id = $form_state->getValue('progression_id');
    if ($progression_id && $progression_id != 'other') {
      $progression = Node::load($progression_id);
      $options = MovepeopleDashboardController::getProgressionGoalsList($progression);
    }

    return $options;
  }



  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $category_id = $form_state->getValue('progression_id');
    $category_title = $form_state->getValue('progression_other_title');
    $tasks = $form_state->getValue('progerssion_tasks');
    // Creating new progression category.
      if ($category_id == 'other') {
        $progression_node = Node::create(array(
          'status' => 1,
          'type' => 'progression_target',
          'title' => $category_title,
          'field_progression_user' => $this->user->id(),
          'field_progression_type' => 'progression',
        ));
        $progression_node->get('field_related_tasks')->appendItem($this->node->id());
        $progression_node->save();
      }
      else {
        $progression_node = Node::load($category_id);
        $related_tasks = $progression_node->get('field_related_tasks')->referencedEntities();
        $is_new = TRUE;
        foreach($related_tasks as $task) {
          if ($task->id() == $this->node->id()) {
            $is_new = FALSE;
            break;
          }
        }
        if ($is_new) {
          $progression_node->get('field_related_tasks')->appendItem($this->node->id());
        }
      }
      foreach ($tasks['task_title'] as $task) {
        $new_goal = Node::create(array(
          'type' => 'goal',
          'status' => 1,
          'title' => $task,
        ));
        $new_goal->save();
        $progression_node->get('field_goal_ids')->appendItem($new_goal->id());
      }

      $this->isSaved = $progression_node->save();

      if ($this->isSaved == SAVED_UPDATED) {
        drupal_set_message($this->t($this->updated_msg));
      } else {
        drupal_set_message($this->t($this->wrong_msg));
      }

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('progression_id')) {
      $form_state->setErrorByName('progression_id', $this->t('Category field is required.'));
    }
  }

 /**
   * Ajax bullet point update function.
   *
   * @param array $form
   *   Form API form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form API form.
   *
   * @return array
   *   Form array.
   */
  public function ajaxAddmoreCallback(array $form, FormStateInterface $form_state) {
    return $form['progerssion_tasks'];
  }

 public function submitAddMore(array &$form, FormStateInterface $form_state) {
  $form_state->setValue('counter', $form_state->getValue('counter') + 1);
    $form_state->setRebuild();

  }

}
