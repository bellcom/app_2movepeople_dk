<?php

namespace Drupal\bc_2movepeople_rate_progression\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\bc_2movepeople_rate_progression\Progression\Target;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

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
    $progression_targets_ids = MovepeopleDashboardController::getProgressionTargets($this->user->id(), $progression_type);
    $form['#tree'] = TRUE;

    if (!empty($progression_targets_ids)) {
      $form['tabs_start'] = [
        '#markup' => '<div class="modal-body__progression-tabs modal-form">'
          . '<div class="col-sm-4 col-xs-2">'
          . '<ul class="nav nav-tabs tabs-left vertical-text" role="tablist">'
      ];

      $is_active = 0;
      foreach ($progression_targets_ids as $key => $target) {
        $progression_target = new Target($target);
        $goals = $progression_target->getAllGoals();
        $title = $progression_target->getProgressionTargetTitle();

        $form['tabs_start']['#markup'] .= '<li class="' . ($is_active ? '' : 'active') . '" role="presentation">'
          . '<a href="#tab_' . $target . '" aria-controls="tab_' . $target . '" role="tab" data-toggle="tab">'
          . $title
          . '</a></li>';

        $form['rates'][$target] = [
          '#markup' => '<div class="tab-pane' . ($is_active ? '' : ' active')
            . '" id="tab_' . $target . '" role="tabpanel"><h2 class="visible-xs">' . $title . '</h2>'
        ];
        $is_active = 1;

        $last_goal_id = 0;
        foreach ($goals as $goal_id) {
          $goaldata = \Drupal::entityTypeManager()->getStorage('node')->load($goal_id);
          if (empty($goaldata->get('field_due_date')->value)) {
            // Set drafts values for goal.
            $rates_draft = \Drupal::entityTypeManager()->getStorage('rate')
              ->loadByProperties([
                'progression_target_id' => $target,
                'goal_id' => $goal_id,
                'status' => FALSE,
                'rate_autor' => \Drupal::currentUser()->id(),
              ]);

            $default_value = '';
            if (!empty($rates_draft)) {
              $draft = array_shift($rates_draft);
              $form['rates_draft'][$goal_id] = [
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
            $last_goal_id = $goal_id;
            $form['rates'][$target][$goal_id] = [
              '#type' => 'select',
              '#title' => $goaltitle,
              '#required' => FALSE,
              '#empty_option' => $this->t('None'),
              '#attributes' => ['class' => ['rating']],
              '#suffix' => '',
              '#options' => [
                1 => '1',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5'
              ]
            ];

            // Set draft value if not empty.
            if (!empty($default_value)) {
              $form['rates'][$target][$goal_id]['#default_value'] = $default_value;
              $form['rates'][$target][$goal_id]['#wrapper_attributes']['class'][] = 'draft';
            }
          }
        }
        $form['rates'][$target][$last_goal_id]['#suffix'] = '</div>';
      }
      $form['tabs_start']['#markup'] .= '</ul></div><div class="col-sm-8 col-xs-10"><div class="tab-content">';

      $form['tabs_end'] = [
        '#markup' => '</div></div></div><div class="clearfix"></div>',
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
    }
    else {
      $form['empty_message'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => t("You don't have created categories for feedback. Please contact with your manager about it."),
      ];
    }

    // Get referer URL to be able redirect to correct page after submit.
    $request = \Drupal::request();
    if ($request->get('navigation')) {
      $headers = \Drupal::request()->server->getHeaders();
      $referer_url = isset($headers['REFERER']) ? $headers['REFERER'] : '';
      if (strpos($referer_url, $request->getSchemeAndHttpHost()) === 0) {
        $form_state->set('ajax_redirect_path', str_replace($request->getBaseUrl(), '', $referer_url));
      }
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
    $values = $form_state->getValues();
    $rates = $values['rates'];
    $time = time();
    $status = 1;
    $storage = $form_state->getStorage();
    if (!empty($storage['draft'])) {
      $time = NULL;
      $status = 0;
    }
    foreach ($rates as $progression_target_id => $goals) {
      foreach ($goals as $goal_id => $rate_value) {
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
              'progression_target_id' => $progression_target_id,
              'goal_id' => $goal_id,
              'rate' => $rate_value,
              'uid' => $this->user->id(),
              'rate_autor' => \Drupal::currentUser()->id(),
              'created' => $time,
              'status' => $status,
            ])->save();
          }
        }
      }
    }
  }

  /**
   * Implements the sumbit handler for draft.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function draftSubmitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    // Mark submission as draft.
    $storage['draft'] = TRUE;
    $form_state->setStorage($storage);
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
    $progression_targets_ids = MovepeopleDashboardController::getProgressionTargets($this->user->id(), 'progression');
    $result = MovepeopleDashboardController::getProgressionsTable($progression_targets_ids);

    $build = array(
      "#theme" => "bc_2movepeople_dashboard_progression_total_table",
      "#table_header" => $result['header'],
      "#table_data" => $result['data'],
    );

    if (\Drupal::currentUser()->id() != $this->user->id()) {
      $response->addCommand(new ReplaceCommand("#progression_total_table", \Drupal::service('renderer')->render($build)));
      $response->addCommand(new InvokeCommand(NULL, 'graphTotalLoad', array('#progression_total_chart')));
    }
    else {
      drupal_set_message('Your submission succesfuly saved');
    }
    $response->addCommand(new CloseModalDialogCommand());

    // Redirect to referer page if there are.
    if ($ajax_redirect_path = $form_state->get('ajax_redirect_path')) {
      $response->addCommand(new RedirectCommand($ajax_redirect_path));
    }

    return $response;
  }

  /**
   * Get goal function.
   */
  private function getGoals($nodeid) {
    $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($nodeid);
    $subnodes = $nodedata->get('field_subgoal')->getValue();
    $this->goals[] = $nodeid;
    foreach ($subnodes as $tid) {
      $this->goals[] = $tid['target_id'];
      $this->getGoals($tid['target_id']);
    }
  }

}
