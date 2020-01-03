<?php

namespace Drupal\bc_webform;
use Drupal\user\Entity\User;
use Drupal\webform\WebformInterface;

/**
 * Handle bc webform functionality.
 */
class BcWebformHandler  {

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
    $userOrganisationTid = empty($values[0]['target_id']) ? NULL : $values[0]['target_id'];
    $organisationTid = $this->webform->getThirdPartySetting('bc_webform', 'organisation_tid');
    if (empty($organisationTid)) {
      $webformOwnerOrganisationTid = $this->webform->getOwner()->get('field_organisation')->getValue();
      $organisationTid = empty($webformOwnerOrganisationTid[0]['target_id']) ? NULL : $webformOwnerOrganisationTid[0]['target_id'];
    }

    return $userOrganisationTid == $organisationTid;
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
    return !isset($userSubmissions[$user_id]);
  }

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

}
