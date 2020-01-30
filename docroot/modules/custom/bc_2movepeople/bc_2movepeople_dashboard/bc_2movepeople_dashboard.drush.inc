<?php

use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\bc_2movepeople_dashboard\Misc\Utils;

/**
 * Implements hook_drush_command().
 */
function bc_2movepeople_dashboard_drush_command() {
  $items['2mp-check-users'] = [
    'description' => 'Check connected users organization.',
    'aliases' => ['2mp-cu'],
  ];
  return $items;
}

/**
 * Check connected users organizations.
 */
function drush_bc_2movepeople_dashboard_2mp_check_users() {
  $translation = Drupal::translation();
  $query = \Drupal::entityQuery('user');
  $query->condition('status', 1);
  $user_ids = $query->execute();
  $not_good_users = 0;
  foreach ($user_ids as $uid) {
    if ($uid == 1) {
      continue;
    }
    /** @var UserInterface $user */
    $user = User::load($uid);
    $user_organization_tids = Utils::getUserOrganizations($user);
    /** @var UserInterface $connected_user */
    foreach ($user->field_connected_users->referencedEntities() as $connected_user) {
      $connected_user_organization_tids = Utils::getUserOrganizations($connected_user);
      if (empty(array_intersect($connected_user_organization_tids, $user_organization_tids))) {
        $not_good_users++;
        \Drupal::messenger()->addWarning(
          $translation->translate('User @user_name(@user_id) has unreachable connected user @connected_user_name(@connected_user_id)', [
            '@user_name' => $user->label(),
            '@user_id' => $user->id(),
            '@connected_user_name' => $connected_user->label(),
            '@connected_user_id' => $connected_user->id(),
          ])
        );
      }
    }
  }

  if (empty($not_good_users)) {
    \Drupal::messenger()->addMessage($translation->translate('All users are ok!'));
  }
}
