<?php

namespace Drupal\krisecenter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the custom access control handler.
 */
class KrisecenterAccess {

  /**
   * Check whether the user has access to KvindeBasicInfoForm.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkKvindeBasicInfoFormAccess(AccountInterface $account) {
    // @TODO Implement krisecenter enabled setting check from 2move people.
    return AccessResult::allowed();
  }

}
