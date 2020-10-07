<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Form to add milestone sub tasks.
 *
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneSubtaskAddForm.
 */
class MilestoneSubtaskAddForm extends MilestoneTaskAddForm {

  /**
   * Build MilestoneTaskAddForm render representing array.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param \Drupal\node\NodeInterface $node
   *   Parent node.
   * @param \Drupal\node\NodeInterface $task_node
   *   Parent task node.
   *
   * @return array
   *   Array of ajax commands to execute on submit of the modal form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $task_node = NULL) {
    $form = parent::buildForm($form, $form_state, $node);
    $form['parent_task_id']['#value'] = $task_node->id();
    $form['title']['#type'] = 'hidden';
    $form['title']['#required'] = FALSE;
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-milestone-subtask-add-form';
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('title', $form_state->getValue('activity_title'));
    parent::submitForm($form, $form_state);
  }

}
