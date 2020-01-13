<?php

namespace Drupal\bc_webform;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Drupal\bc_2movepeople_dashboard\Misc\Utils;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Handle bc webform functionality.
 */
class BcWebformHandler {

  use StringTranslationTrait;
  use MessengerTrait;

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
    $userTaskData = $this->getUserTasksData();
    foreach ($this->getTargetUsers()as $uid) {
      if ($this->userHasSubmittion($uid) || !empty($userTaskData[$uid])) {
        continue;
      }
      $users_list[] = $uid;
    }
    return $users_list;
  }

  /**
   * Sets user task data array.
   *
   * @param $userTaskData
   *
   * @return WebformInterface
   */
  private function setUserTasksData($userTaskData) {
    return $this->webform->setThirdPartySetting('bc_webform', 'user_task_data', $userTaskData);
  }

  /**
   * Gets user task data.
   *
   * @return mixed
   */
  private function getUserTasksData() {
    return $this->webform->getThirdPartySetting('bc_webform', 'user_task_data', []);
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
    $oldUserTasksData = $this->getUserTasksData();
    $this->setUserTasksData($userTasksData + $oldUserTasksData);
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
    $userTasksData = $this->getUserTasksData();
    unset($userTasksData[$user_id]);
    $this->setUserTasksData($userTasksData);
    $this->webform->save();
  }

  /**
   * Resets submissions for user(s).
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
   * Adds task to user.
   *
   * @param $uid
   *   User id.
   *
   * @return bool|int|string|null
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addUserTask($uid) {
    $body = t('You have new webform to submit. <a href=":link">Submit response</a>', [
      ':link' => $this->webform->toUrl('canonical', [
        'query' => [
          'destination' => Url::fromRoute('bc_2movepeople_dashboard.main')->toString()
        ]
      ])->toString()
    ]);
    $task = Node::create([
        'type' => 'goal',
        'status' => 1,
        'title' => $this->webform->get('title'),
        'body' => ['value' => $body, 'format' => 'rich_text'],
        'field_activity_title' => t('Submit webform'),
        'field_due_date' => date('Y-m-d', strtotime('now + 1 week')),
      ]
    );

    if ($task->save() == SAVED_NEW) {
      $milestone_nids = MovepeopleDashboardController::getProgressionTargets($uid, 'target_milestone');
      if (empty($milestone_nids)) {
        $milestone = Node::create(array(
          'status' => 1,
          'type' => 'progression_target',
          'title' => t('Webforms milestone for :user', [':user' => $uid]),
          'field_purpose' => t('Gathering feedback'),
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
      _bc_2movepeople_dashboard_send_task_notification($task);
      return $task->id();
    }
    return FALSE;
  }

  /**
   * Completes user submit webform task.
   *
   * @param $uid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function completeUserTask(WebformSubmissionInterface $webformSubmission) {
    $userTasksData = $this->getUserTasksData();
    $uid = $webformSubmission->getOwnerId();
    if (empty($uid)
      && $userSubmissions = $this->webform->getThirdPartySetting('bc_webform', 'user_submissions', [])) {
      $uid = array_search($webformSubmission->getToken(), $userSubmissions);
    }

    if (!empty($userTasksData[$uid]) && $node = Node::load($userTasksData[$uid])) {
      $node->set('field_task_complete', 1);
      $node->save();
      $this->messenger()->addStatus($this->t('Task "@name" has been completed.', ['@name' => $node->label()]));
    }
  }

  /**
   * Removes weform related data from db.
   */
  public function cleanUp() {
    $userTasksData = $this->getUserTasksData();
    foreach ($userTasksData as $taskId) {
      Node::load($taskId)->delete();
      $milestone = Utils::getMilestoneByGoal($taskId);
      $goal_ids = $milestone->get('field_goal_ids')->referencedEntities();
      if (empty($goal_ids)) {
        $milestone->delete();
      }
      else {
        $new_goal_ids = [];
        foreach ($goal_ids as $goal) {
          $new_goal_ids[] = $goal->id();
        }
        $milestone->set('field_goal_ids', $new_goal_ids);
        $milestone->save();
      }
    }
  }

}
