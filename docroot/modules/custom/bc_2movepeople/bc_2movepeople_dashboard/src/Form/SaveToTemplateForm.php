<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\SaveToTemplateForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\node\Entity\Node;

class SaveToTemplateForm extends FormBase {

  protected $isSaved;
  public static $configName = 'user_template.settings';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['title'] = [
      '#markup' => '<h1 class="page-header">' . $this->t('Create template') . '</h1>'
    ];

    $form['template_name'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('Template name'),
      '#required' => TRUE,
    ];

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Create template'),
      '#attributes' => [
        'class' => ['btn-submit-default'],
      ],
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
    return 'bc_2movepeople-dashboard-user-create-form';
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function ajaxSubmitForm(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    if ($this->isSaved == TRUE) {
      $ajax_response->addCommand(new CloseModalDialogCommand());
    }
    else {
      $message = [
        '#theme' => 'status_messages',
        '#message_list' => drupal_get_messages(),
      ];
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $message));
    }

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (!$form_state->getErrors()) {
      $user = \Drupal::request()->get('user');
      $template_name = $form_state->getValue('template_name');
      $type = 'progression_target';
      $categories_nids = \Drupal::entityQuery('node')
          ->condition('type', $type)
          ->condition('field_progression_user', $user)
          ->execute();

      $categories_nodes = Node::loadMultiple($categories_nids);

      if (!empty($categories_nodes)) {
        $result_array[$user]['template_name'] = $template_name;
        foreach ($categories_nodes as $categories_node) {
          $result_array[$user]['categories'][] = [
            'title' => $categories_node->get('title')->getValue()[0]['value'],
            'goals' => $this->_getGoalsTitlesByCategory($categories_node),
          ];
        }
      }
      $conf_object = \Drupal::configFactory()->getEditable(self::$configName);

      $template = $conf_object->get('template');
      if (empty($template)) {
        $template = array();
      }
      foreach ($result_array as $user => $categories) {
        $template[$user] = $categories;
      }
      $conf_object->set('template', $template);
      $conf_object->save();

      $this->isSaved = TRUE;
    }
  }

  /**
   * Helper get function to collect information about goals from category node.
   *
   * @return array
   *   Goals array.
   */
  private function _getGoalsTitlesByCategory($categories_node) {

    $goals = $categories_node->field_goal_ids->referencedEntities();
    if (is_array($goals)) {
      $result = array();
      foreach ($goals as $goal) {
        $sub_goals_names = array();
        if (!empty($goal->field_subgoal)) {
          $sub_goals = $goal->field_subgoal->referencedEntities();
          foreach ($sub_goals as $sub_goal) {
            $sub_goals_names[] = $sub_goal->get('title')->getValue()[0]['value'];
          }
        }
        $result[] = array('title' => $goal->get('title')->getValue()[0]['value'], 'subgoals' => $sub_goals_names);
      }
      return $result;
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

}
