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
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
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
class UserRatesAddForm extends FormBase {

  protected $user;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = null) {
    $this->user = $user;
    $progression_targets_ids = \Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController::getProgressionTargets($this->user->id());

    foreach ($progression_targets_ids as $key => $target) {
      $progression_target = new Target($target);
      $goals = $progression_target->getAllGoals();
      $form['rates_' . $target] = array(
        '#type' => 'fieldset',
        '#title' => $progression_target->getProgressionTargetTitle(),
      );

      foreach ($goals as $id) {
        $goaldata = \Drupal::entityTypeManager()->getStorage('node')->load($id);
        if (empty($goaldata->get('field_due_date')->value)) {
          $goaltitle = $goaldata->get('title')->value;
          $form['rates_' . $target][$target . '_' . $id] = [
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
    return 'bc_2movepeople-rate-progression-user-rates-add-form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rates = $form_state->getValues(['rates']);

    foreach ($rates as $rate_id => $rate_value) {
      $progression_target_id = array_shift(explode('_', $rate_id));
      $goal_id = array_pop(explode('_', $rate_id));
      $node_exists = isset($goal_id) ? !empty(\Drupal::entityQuery('node')->condition('nid', $goal_id)->execute()) : FALSE;

      if (!empty($rate_value) && $node_exists) {
        \Drupal::database()->insert('bc_2movepeople_rate_progression')
            ->fields(array(
              'progression_target_id' => $progression_target_id,
              'goal_id' => $goal_id,
              'rate' => $rate_value,
              'uid' => $this->user->id(),
              'created' => time(),
            ))
            ->execute();
      }
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
    $progression_targets_ids = \Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController::getProgressionTargets($this->user->id());
    $result = \Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController::getProgressionsTable($progression_targets_ids);

    $build = array(
      "#theme" => "bc_2movepeople_dashboard_progression_total_table",
      "#table_header" => $result['header'],
      "#table_data" => $result['data']
    );
    $response->addCommand(new \Drupal\Core\Ajax\ReplaceCommand("#progression_total_table", \Drupal::service('renderer')->render($build)));

    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand(NULL, 'graphTotalLoad', array('#progression_total_chart')));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  private function getGoals($nodeid) {
    $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($nodeid);
    $subnodes = $nodedata->get('field_subgoal')->getValue();
    $this->goals[] = $nodeid;
    //$subgoals= array();
    foreach ($subnodes as $tid) {
      $this->goals[] = $tid['target_id'];
      $this->getGoals($tid['target_id']);
    }
  }

}
