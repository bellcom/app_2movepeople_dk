<?php

namespace Drupal\bc_webform;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Drupal\bc_2movepeople_dashboard\Form\AdminSettingsForm;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\webform\WebformInterface;

/**
 * Handle bc webform functionality.
 */
class BcWebformHandler {

  use StringTranslationTrait;

  /**
   * Webform entity.
   *
   * @var \Drupal\webform\WebformInterface
   */
  public $webform;

  public function __construct(WebformInterface $webform) {
    $this->webform = $webform;
  }

  /**
   * Returns array with user ids that should submit webform.
   *
   * @return array|int
   */
  public function getTargetUsers() {
    $organisationTid = $this->webform->getThirdPartySetting('bc_webform', 'organisation_tid');
    if (empty($organisationTid)) {
      $user = $this->webform->getOwner();
      $values = $user->get('field_organisation')->getValue();
      if (empty($values[0]['target_id'])) {
        return [];
      }
      $organisationTid = $values[0]['target_id'];
    }
    $roles = $this->webform->getThirdPartySetting('bc_webform', 'roles', unserialize(BC_WEBFORM_ROLES));
    $user_ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', $roles, 'IN')
      ->condition('field_organisation', $organisationTid)
      ->execute();
    return $user_ids;
  }

  /**
   * Check user access to this form.
   *
   * @return bool
   */
  public function matchOrganization($user_id) {
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($user_id);
    $values = $user->get('field_organisation')->getValue();
    $userOrganisationTids = [];
    foreach ($values as $value) {
      $userOrganisationTids[] = $value['target_id'];
    }
    $organisationTid = $this->webform->getThirdPartySetting('bc_webform', 'organisation_tid');
    if (empty($organisationTid)) {
      $webformOwnerOrganisationTid = $this->webform->getOwner()->get('field_organisation')->getValue();
      $organisationTid = empty($webformOwnerOrganisationTid[0]['target_id']) ? NULL : $webformOwnerOrganisationTid[0]['target_id'];
    }

    return in_array($organisationTid, $userOrganisationTids);
  }

  /**
   * Check if user alloed to submit form.
   *
   * @return bool
   */
  public function isAllowedSubmit($user_id) {
    /** @var \Drupal\user\UserInterface $user */
    $user = User::load($user_id);
    $roles = $this->webform->getThirdPartySetting('bc_webform', 'roles', unserialize(BC_WEBFORM_ROLES));
    return array_intersect($roles,$user->getRoles());
  }

  public function registerUserSubmission($user_id, $token) {
    $userSubmissions = $this->webform->getThirdPartySetting('bc_webform', 'user_submissions', []);
    if ($user_id == 0 || !empty($userSubmissions[$user_id])) {
      return;
    }
    $userSubmissions[$user_id] = $token;
    $this->webform->setThirdPartySetting('bc_webform', 'user_submissions', $userSubmissions);
    $this->webform->save();
  }

  public function userHasSubmittion($user_id) {
    if ($user_id == 0) {
      return TRUE;
    }
    $userSubmissions = $this->webform->getThirdPartySetting('bc_webform', 'user_submissions', []);
    return isset($userSubmissions[$user_id]);
  }

  /**
   * Gets users id that has no task for submit form.
   *
   * @return array
   *   Array with user ids.
   */
  public function userWithoutTask() {
    $users_list = [];
    $userTaskData = $this->webform->getThirdPartySetting('bc_webform', 'user_task_data', []);
    foreach ($this->getTargetUsers()as $uid) {
      if ($this->userHasSubmittion($uid) || !empty($userTaskData[$uid])) {
        continue;
      }
      $users_list[] = $uid;
    }
    return $users_list;
  }

  /**
   * Updates user tasks data.
   *
   * @param array $userTasksData
   *   Array with user task data.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateTasksData($userTasksData) {
    $oldUserTasksData = $this->webform->getThirdPartySetting('bc_webform', 'user_task_data', []);
    $this->webform->setThirdPartySetting('bc_webform', 'user_task_data', $userTasksData + $oldUserTasksData);
    $this->webform->save();
  }

  /**
   * Removes user tasks data.
   *
   * @param int $user_id
   *   User id to remove data for.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeUserTasksData($user_id) {
    $userTasksData = $this->webform->getThirdPartySetting('bc_webform', 'user_task_data', []);
    unset($userTasksData[$user_id]);
    $this->webform->setThirdPartySetting('bc_webform', 'user_task_data', $userTasksData);
    $this->webform->save();
  }

  /**
   * Resets submittions for user(s).
   *
   * @param int|null $uid
   *   User id to remove data for.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function resetSubmissions($uid = NULL) {
    $userSubmissions = $this->webform->getThirdPartySetting('bc_webform', 'user_submissions', []);
    if (!empty($uid)) {
      unset($userSubmissions[$uid]);
    }
    else {
      $userSubmissions = [];
    }
    $this->webform->setThirdPartySetting('bc_webform', 'user_submissions', $userSubmissions);
    $this->webform->save();
  }

  /**
   * Adding message to users tasks.
   *
   * @return array
   *   Array with users who got assigned task.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addUserTasks()
  {
    $users = $this->getTargetUsers();
    $user_task_data = $this->webform->getThirdPartySetting('bc_webform', 'user_task_data', []);
    $current_timestamp = time();
    $priority_options = options_allowed_values(FieldStorageConfig::loadByName('node', 'field_priority'));
    $status_options = options_allowed_values(FieldStorageConfig::loadByName('node', 'field_progression_status'));
    $results = [];
    foreach ($users as $uid) {
      if (!empty($user_task_data[$uid])) {
        continue;
      }

      $task = Node::create([
        'type' => 'goal',
        'status' => 1,
        'title' => $this->webform->toUrl('canonical', ['absolute' => TRUE])->toString(),
        'field_activity_title' => $this->t('Submit webform'),
        'field_due_date' => date('Y-m-d', strtotime('now + 1 week')),
        ]
      );

      if ($task->save() == SAVED_NEW) {
        $milestone_nids = MovepeopleDashboardController::getProgressionTargets($uid, 'target_milestone');
        if (empty($milestone_nids)) {
          $milestone = Node::create(array(
            'status' => 1,
            'type' => 'progression_target',
            'title' => $this->t('Weforms'),
            'field_purpose' => $this->t('Gathering feedback'),
            'field_priority' => reset($priority_options),
            'field_progression_status' => reset($status_options),
            'field_progression_user' => $uid,
            'field_progression_type' => 'target_milestone',
          ));
          $milestone->save();
        }
        else {
          $milestone = Node::load(reset($milestone_nids));
        }

        $new_goal_ids = [];
        foreach ($milestone->get('field_goal_ids')->getValue() as $tid) {
          $new_goal_ids[] = $tid['target_id'];
        }
        $new_goal_ids[] = $task->id();
        $milestone->set('field_goal_ids', $new_goal_ids);
        $milestone->save();

        $user_task_data[$uid] = $current_timestamp;
        $results[] = $uid;
      }
    }
    $this->webform->setThirdPartySetting('bc_webform', 'user_task_data', $user_task_data);
    $this->webform->save();
    return $results;
  }

}
