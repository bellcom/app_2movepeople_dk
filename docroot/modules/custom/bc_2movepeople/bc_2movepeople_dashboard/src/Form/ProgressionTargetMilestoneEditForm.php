<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\node\NodeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form to edit progression target milestones.
 */
class ProgressionTargetMilestoneEditForm extends ProgressionTargetEditForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-progression-edit-form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {

    $this->node = $node;

    $form = parent::buildForm($form, $form_state, $node);

    unset($form['for_user_feedback']);
    $form['title']['#title'] = t('Milestone');
    $user = $this->node->get('field_progression_user')->getValue();
    $return_url = Url::fromRoute('<current>')->toString();
    $form = CommonFormUtils::tasksContainer($form, $this->node, t('Task'));
    $form['actions']['add_task']['#title'] = t('Add new task');
    $form['actions']['add_task']['#url'] = Url::fromRoute('bc_2movepeople_dashboard.milestone.tasks.add', [
      'node' => $this->node->id(),
      'return_url' => $return_url,
    ]);
    $form['actions']['back']['#url'] = Url::fromRoute('bc_2movepeople_dashboard.user.milestones', ['user' => $user[0]['target_id']]);

    $form['actions']['delete_category']['#value'] = t('Delete milestone');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCategory(array &$form, FormStateInterface $form_state) {
    $user = $this->node->get('field_progression_user')->getValue();
    parent::deleteCategory($form, $form_state);
    $form_state->setRedirect('bc_2movepeople_dashboard.user.milestones', [
      'user' => $user[0]['target_id'],
    ]);
  }

}
