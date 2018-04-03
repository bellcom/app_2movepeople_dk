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
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $progression_target_id = NULL) {
    $this->node = $progression_target_id;
    $mtid = $this->node->get('field_goal_ids')->getValue();

    if ($this->node->field_progression_type->value == 'progression_feedback') {
      $options = [
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
        9 => '9',
        10 => '10',
      ];
    }
    else {
      $options = [
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5'
      ];
    }
    $form['#prefix'] = '<div id="bc_2movepeople-rate-progression-add-form">';
    $form['#suffix'] = '</div>';
    $form['#tree'] = TRUE;
    $progression_target = new Target($this->node->id());
    $goals = $progression_target->getAllGoals();

    foreach ($goals as $id) {
      $goaldata = \Drupal::entityTypeManager()->getStorage('node')->load($id);
      if (empty($goaldata->get('field_due_date')->value)) {

        // Set drafts values for goal.
        $rates_draft = \Drupal::entityTypeManager()->getStorage('rate')
          ->loadByProperties([
            'progression_target_id' => $this->node->id(),
            'goal_id' => $id,
            'status' => FALSE,
            'rate_autor' => \Drupal::currentUser()->id(),
          ]);

        $default_value = '';
        if (!empty($rates_draft)) {
          $draft = array_shift($rates_draft);
          $form['rates_draft'][$id] = [
            '#type' => 'hidden',
            '#default_value' => $draft->id(),
          ];
          $default_value = $draft->rate->value;

          // Cleanup drafts if there more then one.
          foreach ($rates_draft as $rate) {
            $rate->delete();
          }
        }

        $goaltitle = $goaldata->get('title')->value;
        $form['rate'][$id] = [
          '#type' => 'select',
          '#title' => $goaltitle,
          '#required' => FALSE,
          '#attributes' => ['class' => ['rating']],
          '#empty_option' => 'None',
          '#options' => $options,
        ];

        // Set draft value if not empty.
        if (!empty($default_value)) {
          $form['rate'][$id]['#default_value'] = $default_value;
          $form['rate'][$id]['#wrapper_attributes']['class'][] = 'draft';
        }
        $result['rates'] = bc_2movepeople_rate_progression_get_rates($this->node->id(), $id);
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
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
    ];
    $form['actions']['draft'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save draft'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
      '#submit' => ['::draftSubmitForm', '::submitForm'],
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
    $values = $form_state->getValues();
    $rates = $values['rate'];
    $time = time();
    $status = TRUE;
    $storage = $form_state->getStorage();
    if (!empty($storage['draft'])) {
      $time = NULL;
      $status = FALSE;
    }
    foreach ($rates as $goal_id => $rate_value) {
      $node = \Drupal::entityQuery('node')->condition('nid', $goal_id)->execute();
      if (!empty($rate_value) && !(empty($node))) {
        if (isset($values['rates_draft'][$goal_id])) {
          $rate_draft = \Drupal::entityTypeManager()
            ->getStorage('rate')
            ->load($values['rates_draft'][$goal_id]);
          $rate_draft->rate = $rate_value;
          $rate_draft->status = $status;
          $rate_draft->created = $time;
          $rate_draft->save();
        }
        else {
          \Drupal::entityTypeManager()->getStorage('rate')->create([
            'progression_target_id' => $this->node->id(),
            'goal_id' => $goal_id,
            'rate' => $rate_value,
            'rate_autor' => \Drupal::currentUser()->id(),
            'uid' => $this->node->get('field_progression_user')->getValue()[0]['target_id'],
            'created' => $time,
            'status' => $status,
          ])->save();
        }
      }
    }
  }

  /**
   * Implements the sumbit handler for save the draft.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function draftSubmitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    // Mark submission as draft.
    $storge['draft'] = TRUE;
    $form_state->setStorage($storge);
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
