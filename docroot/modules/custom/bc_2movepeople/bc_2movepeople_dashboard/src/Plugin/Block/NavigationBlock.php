<?php

namespace Drupal\bc_2movepeople_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;

/**
 * Provides a "Navigation block".
 *
 * @Block(
 *   id = "navigation_block",
 *   admin_label = @Translation("Navigation block")
 * )
 */
class NavigationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = \Drupal::currentUser();

    $feedback_progression = MovepeopleDashboardController::getProgressionTargets($user->id(), 'feedback');
    $build = [
      '#cache' => [
        'tags' => ['feedback:' . implode(':', $feedback_progression)],
      ],
    ];

    if ($user->hasPermission('access user dashboard')) {
      $build['connected_users'] = MovepeopleDashboardController::renderConnectedUsers($user->getAccount(), 'block_links_simple');
    }

    $buttons = [];

    $user_roles = $user->getRoles();

    if (in_array("2mp_user", $user_roles)) {
      $buttons['Navigation.self_rate'] = [
        '#url' => Url::fromRoute('bc_2movepeople_rate_progression.self_rates_add', [
          'navigation' => TRUE,
        ]),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
        ],
      ];
    }

    if (MovepeopleDashboardController::getProgressionTargets($user->id(), 'feedback')
    && $user->hasPermission('access provide feedback')) {
      $buttons['Navigation.feedback'] = [
        '#url' => Url::fromRoute('bc_2movepeople_rate_progression.feedback_add', [
          'navigation' => TRUE,
        ]),
        '#title' => t('Dialogue rating'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal'
        ],
      ];
    }

    if ($user->hasPermission('create connected users')) {

      // Alter text of button.
      $add_button_key = 'Navigation.create_user';
      if (in_array('2mp_supervisor', $user_roles)) {
        $add_button_key = 'Navigation.supervisor_create_user';
      }
      else if (in_array('2mp_manager', $user_roles)) {
        $add_button_key = 'Navigation.manager_create_user';
      }

      $buttons[$add_button_key] = [
        '#url' => Url::fromRoute('bc_2movepeople_dashboard.users.create'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal'
        ],
      ];
    }

    if (!empty($buttons)) {
      $build['buttons'] = MovepeopleDashboardController::getControlButtons($buttons, ['class' => ['text-center']]);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $user = \Drupal::currentUser();
    return Cache::mergeTags(parent::getCacheTags(), array('feedback:' . $user->id()));
  }

}
