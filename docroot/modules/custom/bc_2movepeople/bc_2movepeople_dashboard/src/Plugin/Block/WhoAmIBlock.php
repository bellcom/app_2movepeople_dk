<?php

namespace Drupal\bc_2movepeople_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\user\Entity\User;

/**
 * Provides a '2move: who am i' block.
 *
 * @Block(
 *   id = "whoami_block",
 *   admin_label = @Translation("Who am i"),
 *   category = @Translation("2move"),
 * )
 */
class WhoAmIBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Grab the logged in user.
    $current_user = \Drupal::currentUser();

    // User is not logged in. Don't show anything.
    if (!$current_user) {
      return $build;
    }
    $user = User::load($current_user->id());

    $build['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['who-am-i']],
    ];

    // User image.
    if (!$user->user_picture->isEmpty()) {
      $image_uri = $user->user_picture->entity->getFileUri();
      $image_style = ImageStyle::load('who_am_i');
      $image_url = $image_style->buildUrl($image_uri);
    }

    $build['wrapper']['user-image'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['who-am-i__image']],
    ];

    if (isset($image_url)) {
      $build['wrapper']['user-image']['image'] = [
        '#markup' => '<img src="' . $image_url . '" />',
      ];
    }

    // Personal data.
    $roles = $user->getRoles();

    $firstname = isset($user->field_user_firstname->value) ? $user->field_user_firstname->value : '';
    $lastname = isset($user->field_user_surname->value) ? $user->field_user_surname->value : '';
    if ($firstname && $lastname) {
      $name = $firstname . ' ' . $lastname;
    } else {
      $name = $user->getUsername();
    }

    $account_type = '';
    if (in_array('2mp_supervisor', $roles)) {
      $account_type = $this->t('Supervisor');
    } else if (in_array('2mp_manager', $roles)) {
      $account_type = $this->t('Manager');
    } else {
      // $account_type = $this->t('Citizen');
    }

    $build['wrapper']['personal-data'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['who-am-i__data']],
    ];
    $build['wrapper']['personal-data']['full-name'] = [
      '#markup' => '<div class="who-am-i__data__name">' . $name . '</div>',
    ];
    $build['wrapper']['personal-data']['account-type'] = [
      '#markup' => '<div class="who-am-i__data__account-type">' . $account_type . '</div>',
    ];

    return $build;
  }

}
