<?php

namespace Drupal\bc_2movepeople\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\ProfileForm;

/**
 * Controller for js_example pages.
 *
 * @ingroup js_example
 */
class MovepeopleController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function user_edit(AccountInterface $user, Request $request) {

//$user_form  = $this->formBuilder->getForm($user, 'default');
    $user_form = \Drupal::service('entity.form_builder')->getForm($user, 'default');

    $build[] = $user_form;

    return $build;
  }

}
