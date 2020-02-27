<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Form to add new user.
 */
class NewUserCreateForm extends NewUserCreateFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $current_user = $form_state->get('current_user');

    // Loading user templates.
    $conf_object = $this->configFactory()->getEditable(SaveToTemplateForm::$configName);
    $templates = $conf_object->get('template');
    $list[''] = t('none');
    if (empty($templates)) {
      $templates = [];
    }
    foreach ($templates as $user => $template) {
      $list[$user] = $template['template_name'];
    }

    // Get default progression template id.
    $config = \Drupal::config('bc_2movepeople.settings');
    $default_progression_template_id = $config->get('default_progression_template');
    if ($this->getNewUserRole($current_user->getRoles()) == '2mp_user'
      && $current_user->hasPermission('access category template')
      && !empty($templates)) {
      $form['template_select'] = [
        '#type' => 'select',
        '#title' => $this->t('User template'),
        '#options' => $list,
        '#default_value' => $default_progression_template_id,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-user-create-form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($this->isSaved && $form_state->getValue('template_select')) {
      $user = $form_state->get('new_user');
      // Loading user templates.
      $conf_object = \Drupal::configFactory()->getEditable(SaveToTemplateForm::$configName);
      $template = $conf_object->get('template');
      if (empty($template)) {
        $template = [];
      }
      $template = $template[$form_state->getValue('template_select')];

      // Attach category to the user.
      foreach ($template['categories'] as $progression_type => $categories) {
        foreach ($categories as $category) {
          $category_name = $category['title'];
          $category_node = Node::create([
            'type' => 'progression_target',
            'title' => $category_name,
            'field_progression_user' => $user->id(),
            'field_progression_type' => $progression_type,
            'field_goal_ids' => $this->createGoals($category['goals']),
          ]);
          $category_node->save();
        }
      }
    }
  }

  /**
   * Helper function to create goal nodes.
   *
   * @return array
   *   Array of goal node ids.
   */
  private function createGoals($goals) {
    $result = [];

    foreach ($goals as $goal) {
      if (!empty($goal['subgoals'])) {
        $sub_goal_array = [];
        foreach ($goal['subgoals'] as $subgoal) {
          $sub_goal_node = Node::create([
            'type' => 'goal',
            'title' => $subgoal['title'],
          ]);
          $sub_goal_node->save();
          $sub_goal_array[] = $sub_goal_node->id();
        }
      }
      $goal_node = Node::create([
        'type' => 'goal',
        'title' => $goal['title'],
        'field_subgoal' => $sub_goal_array,
      ]);
      $goal_node->save();
      $result[] = $goal_node->id();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($form_state->getValue('template_select')) {
      // Check template_select.
      $template_select = CommonFormUtils::cleanInput($form_state->getValue('template_select'));
      if (!is_numeric($template_select) && !empty($template_select)) {
        $form_state->setErrorByName('template_select', $this->t('The Template %template_select is not valid.', ['%template_select' => $template_select]));
      }
    }
  }

}
