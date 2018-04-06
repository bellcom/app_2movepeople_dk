<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Cache\Cache;
use Drupal\node\Entity\Node;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

/**
 * Contains definition of ProgressionTargetMilestoneEvaluateForm.
 */
class ProgressionTargetMilestoneEvaluateForm extends FormBase {

  private $node;
  private $limit;
  private $updatedMsg = 'Records successfully updated.';
  private $wrongMsg = 'Something wrong.';
  protected $renreder;

  /**
   * Class constructor.
   */
  public function __construct(RendererInterface $renreder) {
    $this->renreder = $renreder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-progression-edit-form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $limit = NULL) {
    $this->node = $node;
    $this->limit = $limit;
    $goal_ids = $node->get('field_goal_ids')->getValue();

    $form = [
      'goals' => [
        '#type' => 'container',
        '#attributes' => ['id' => 'goals-box-' . $node->id()],
      ],
    ];

    foreach ($goal_ids as $tid) {
      $form['goals']['#tree'] = TRUE;
      $goal_id = $tid['target_id'];
      $goal = MovepeopleDashboardController::getGoal($goal_id, $node);

      $form['goals'][$goal['id']] = [
        '#type' => 'container'
      ];

      $form['goals'][$goal['id']]['field_evaluation'] = [
        '#type' => 'textarea',
        '#title' => $goal['title'],
        '#default_value' => $goal['evaluation'],
        '#suffix' => '<br/>',
      ];
    }

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    $user = $this->node->get('field_progression_user')->getValue();

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
      '#attributes' => [
        'class' => ['btn-default'],
      ],
    ];

    $request = $this->getRequest();
    if ($request->get('modal')) {
      $form_state->setStorage(['modal' => TRUE]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $storage = $form_state->getStorage();
    $user_id = $this->node->get('field_progression_user')->getValue();
    $user_id = empty($user_id[0]['target_id']) ? NULL : $user_id[0]['target_id'];
    if (!$form_state->getErrors()) {

      $goals_arr = $form_state->getValue('goals');

      foreach ($goals_arr as $gid => $goal) {
        $goal_node = Node::load($gid);
        $goal_node->set("field_evaluation", $goal['field_evaluation']);
        $goal_node->save();
      }

      if ($this->node->save() == SAVED_UPDATED) {

        drupal_set_message($this->t($this->updatedMsg));

        // Invalidate navigation block cachetag.
        Cache::invalidateTags(array('feedback:' . $user_id));
      }
    }
    else {
      drupal_set_message($this->t($this->wrongMsg));
    }

    $messages = [
      '#theme' => 'status_messages',
      '#message_list' => drupal_get_messages(),
    ];
    $messages = $this->renreder->render($messages);

    if (isset($storage['modal'])) {
      $evaluation = MovepeopleDashboardController::getMilestoneEvaluations(User::load($user_id));
      $evaluation['#message'] = $messages;
      $ajax_response->addCommand(new OpenModalDialogCommand('Milestone evaluations', $this->renreder->render($evaluation)));
    }
    else {
      $ajax_response->addCommand(new CloseModalDialogCommand());
    }

    $ajax_response->addCommand(new HtmlCommand('#custom-form-system-messages', $messages));

    return $ajax_response;
  }

}
