<?php

namespace Drupal\bc_2movepeople\Permissions;

class MovepeoplePermissions {

  public function permissions() {

    $all_roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    unset($all_roles['administrator']);
    unset($all_roles['authenticated']);

    $permissions = [];

    foreach ($all_roles as $role_name => $role_title) {
      $permissions[$role_name] = [
        'title' => t('Allow to edit/delete accounts with %role role', array('%role' => $role_title)),
        'description' => t('Warning: Give to trusted roles only; this permission has security implications.'),
      ];
    }
    return $permissions;
  }
}
