<?php

/**
 * @file
 * Contains \Drupal\decreto_content_modify\Form\MeetingsEditForm.
 */

namespace Drupal\bc_2movepeople_rate_progression\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Session\AccountInterface;
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
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL, $progression_type = 'progression') {
    $this->user = $user;
    $progression_targets_ids = \Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController::getProgressionTargets($this->user->id(), $progression_type);

    if (!empty($progression_targets_ids)) {
      $form['tabs_start'] = [
        '#markup' => ''
          . '<div class="modal-body__progression-tabs modal-form">'
          . '<div class="col-sm-4 col-xs-2">'
          . '<ul class="nav nav-tabs tabs-left vertical-text" role="tablist">'
      ];

      $is_active = 0;
      foreach ($progression_targets_ids as $key => $target) {
        $progression_target = new Target($target);
        $goals = $progression_target->getAllGoals();
        $title = $progression_target->getProgressionTargetTitle();

        $form['tabs_start']['#markup'] .= ''
          . '<li class="' . ($is_active ? '' : 'active') . '" role="presentation">'
          . '<a href="#tab_' . $target . '" aria-controls="tab_' . $target . '" role="tab" data-toggle="tab">'
          . $title
          . '</a></li>';

        $form['rates_' . $target] = [
          '#markup' => '<div class="tab-pane' . ($is_active ? '' : ' active')
            . '" id="tab_' . $target . '" role="tabpanel">'
            . '<h2 class="visible-xs">' . $title . '</h2>'
        ];
        $is_active = 1;

        $last_goal_id = 0;
        foreach ($goals as $goal_id) {
          $goaldata = \Drupal::entityTypeManager()->getStorage('node')->load($goal_id);
          if (empty($goaldata->get('field_due_date')->value)) {
            $goaltitle = $goaldata->get('title')->value;

            $last_goal_id = $goal_id;
            $form['rates_' . $target][$target . '_' . $goal_id] = [
              '#type' => 'select',
              '#title' => $goaltitle,
              '#required' => FALSE,
              '#empty_option' => $this->t('None'),
              '#suffix' => '',
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
        $form['rates_' . $target][$target . '_' . $last_goal_id]['#suffix'] = '</div>';
      }
      $form['tabs_start']['#markup'] .= ''
        . '</ul>'
        . '</div>'
        . '<div class="col-sm-8 col-xs-10">'
        . '<div class="tab-content">';

      $form['tabs_end'] = [
        '#markup' => ''
        . '</div>'
        . '</div>'
        . '</div>'
        . '<div class="clearfix"></div>'
      ];


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
    }
    else {
      $form['empty_message'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => t("You don't have created categories for feedback. Please contact with your manager about it."),
      ];
    }

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
      $node = \Drupal::entityQuery('node')->condition('nid', $goal_id)->execute();
      $node_exists = isset($goal_id) ? !empty($node) : FALSE;

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
      "#table_data" => $result['data'],
    );

    $form_build_info = $form_state->getBuildInfo();
    if (!in_array('feedback', $form_build_info['args'])) {
      $response->addCommand(new \Drupal\Core\Ajax\ReplaceCommand("#progression_total_table", \Drupal::service('renderer')->render($build)));
      $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand(NULL, 'graphTotalLoad', array('#progression_total_chart')));
    }
    else {
      drupal_set_message('Your feedback succesfuly saved');
      $response->addCommand(new RedirectCommand('/user'));
    }
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
