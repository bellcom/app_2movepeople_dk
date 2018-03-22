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
      '#markup' => '<h1 class="page-header">' . $this->t('Chose the action') .
        '</h1>'
    ];
    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
    ];

    // Loading user templates.
    $conf_object = \Drupal::configFactory()->getEditable(SaveToTemplateForm::$configName);
    $templates = $conf_object->get('template');
    $options[0] = t('none');
    if (empty($templates)) {
      $templates = array();
    }
    foreach ($templates as $user => $template) {
      $options[$user] = $template['template_name'];
    }

    $form['template_mode'] = [
      '#type' => 'radios',
      '#options' => [
        'new' => t('Create new template'),
        'update' => t('Update extisting template'),
      ],
      '#default_value' => 'new',
    ];
    $form['template_id'] = [
      '#type' => 'select',
      '#placeholder' => $this->t('Chose existing template'),
      '#options' => $options,
      '#states' => [
        'visible' => [
          'input[name="template_mode"]' => array('value' => 'update'),
        ],
      ],
    ];

    $form['template_name'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('Template name'),
      '#size' => 40,
      '#states' => [
        'visible' => [
          'input[name="template_mode"]' => array('value' => 'new'),
        ],
      ],
    ];

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Save'),
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
        foreach ($categories_nodes as $categories_node) {
          $result_array[] = [
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

      if ($form_state->getValue('template_id')) {
        $template[$form_state->getValue('template_id')]['categories'] = $result_array;
      }
      else {
        $template[] = [
          'template_name' => $template_name,
          'categories' => $result_array,
        ];
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('template_mode') == 'update'
      && empty($form_state->getValue('template_id'))) {
      $form_state->setErrorByName('template_id', $this->t('You have to choose the template.'));
    }
  }

}
