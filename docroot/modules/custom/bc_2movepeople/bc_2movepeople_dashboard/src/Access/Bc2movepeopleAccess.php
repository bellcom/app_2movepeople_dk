<?php

namespace Drupal\bc_2movepeople_dashboard\Access;

use Drupal\bc_2movepeople_dashboard\Misc\Utils;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the custom access control handler for the user accounts.
 */
class Bc2movepeopleAccess {

  /**
   * Check whether the user has access to dashboard pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkAccess(AccountInterface $account, $checkedUser = NULL) {
    if ($account->isAnonymous()) {
      return AccessResult::neutral();
    }
    $result = AccessResult::allowedIfHasPermission($account, 'access user dashboard');
    $isConnected = self::isConnected($account, $checkedUser);
    $matchOrganization = self::matchOrganization($account, $checkedUser);
    return $result->andIf($isConnected)->andIf($matchOrganization);
  }

  /**
   * Check if current user has user as connected.
   *
   * @return AccessResult
   */
  public static function isConnected(AccountInterface $account, UserInterface $checkedUser = NULL) {
    if (
      $account->id() <= 1
      || in_array('2mp_admin', $account->getRoles())) {
      return AccessResult::allowed();
    }

    if (in_array('2mp_supervisor', $account->getRoles())) {
      return AccessResult::allowed();
    }

    $user = $checkedUser;
    if (empty($checkedUser)) {
      $user = self::getUserFromRoute();
      if (empty($user)) {
        return AccessResult::neutral();
      }
    }

    /** @var EntityReferenceFieldItemList $connected_users */
    $currentUser = User::load($account->id());
    $connectedUsers = $currentUser->get('field_connected_users');
    if ($connectedUsers instanceof EntityReferenceFieldItemList) {
      foreach ($connectedUsers->referencedEntities() as $connectedUser) {
        if ($user->id() == $connectedUser->id()) {
          return AccessResult::allowed();
        }
      }
    }

    return AccessResult::forbidden((string) t('User @user_uid is not connected to @current_user_uid and  does not match.', [
      '@current_user_uid' => $currentUser->id(),
      '@user_uid' => $user->id(),
    ]));
  }

  /**
   * Check if current user has any of organization from giver user.
   *
   * @return AccessResult
   */
  public static function matchOrganization(AccountInterface $account, UserInterface $checkedUser = NULL) {
    if (
      $account->id() == 1
      || in_array('2mp_admin', $account->getRoles())) {
      return AccessResult::allowed();
    }


    $user = $checkedUser;
    if (empty($checkedUser)) {
      $user = self::getUserFromRoute();
      if (empty($user)) {
        return AccessResult::neutral();
      }
    }

    $userOrganisationTids = Utils::getUserOrganizations($user);
    /** @var \Drupal\user\UserInterface $user */
    $currentUser = User::load($account->id());
    $currentUserOrganisationTids = Utils::getUserOrganizations($currentUser);

    return empty(array_intersect($currentUserOrganisationTids, $userOrganisationTids)) ? AccessResult::forbidden(
      (string) t('Organizations for users @current_user_uid and @user_uid does not match.', [
      '@current_user_uid' => $currentUser->id(),
      '@user_uid' => $user->id(),
    ])) : AccessResult::allowed();
  }

  public static function getUserFromRoute() {
    $user = \Drupal::routeMatch()->getParameter('user');
    /** @var NodeInterface $node */
    if (empty($user) && $node = \Drupal::routeMatch()->getParameter('node')) {
      $node = Node::load($node->id());
      if ($node->bundle() == 'progression_target' && !$node->get('field_progression_user')->isEmpty()) {
        $value = $node->get('field_progression_user')->getValue();
        $user = User::load($value[0]['target_id']);
      }

    }
    return $user;
  }
}
