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

    if (MovepeopleDashboardController::getProgressionTargets($user->id(), 'feedback')
    && $user->hasPermission('access provide feedback')) {
      $buttons['feedback'] = [
        '#url' => Url::fromRoute('bc_2movepeople_rate_progression.feedback_add'),
        '#title' => t('Dialogue rating'),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal'
        ],
      ];
    }

    if ($user->hasPermission('create connected users')) {
      $buttons['Navigation.create_user'] = [
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
