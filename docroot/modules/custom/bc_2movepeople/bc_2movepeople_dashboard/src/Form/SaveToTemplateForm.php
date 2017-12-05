<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\ProgressionTaskEditForm.
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
      //$ajax_response->addCommand(new CloseModalDialogCommand());
      //drupal_set_message(t('Saved'), 'status');
      $ajax_response->addCommand(new CloseModalDialogCommand());
    }
    else {
      $message = [
        '#theme' => 'status_messages',
        '#message_list' => drupal_get_messages(),
      ];
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $message));
      // $ajax_response->addCommand(new ReplaceCommand('#bc_2movepeople-dashboard-user-create-form', $form));
    }
    //$form_state->setRebuild(true);

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
        //print_r($nodes);
        foreach ($categories_nodes as $categories_node) {

          $result_array[$user][$template_name][] = array($categories_node->get('title')->getValue()[0]['value'] => $this->getGoalsTitlesByCategory($categories_node));

          //$goal_nids = $categories_node->get('field_goal_ids')->getValue();
          //$test = $this->getGoalsTitlesByCategoryNid($categories_node->get('field_goal_ids')->getValue());
        }
      }

      $confObject = \Drupal::configFactory()->getEditable(self::$configName);

      $template = $confObject->get('template');
      if(empty($template)){
        $template = array();
      }
      foreach($result_array as $user => $categories){
        $template[$user] = $categories;
      }
      $confObject->set('template', $template);
      $confObject->save();
      
      
      $this->isSaved = TRUE;
      //print_r($goal_nids);
    }
  }

  private function getGoalsTitlesByCategory($categories_node) {

    $goal_nids = $categories_node->get('field_goal_ids')->getValue();
    foreach ($goal_nids as $goal_nid) {
      $goals_array[] = $goal_nid['target_id'];
    }
    $goals = Node::loadMultiple($goals_array);
    if (is_array($goals)) {
      foreach ($goals as $goal) {
        $output[] = $goal->get('title')->getValue()[0]['value'];
      }
      return $output;
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }

}
