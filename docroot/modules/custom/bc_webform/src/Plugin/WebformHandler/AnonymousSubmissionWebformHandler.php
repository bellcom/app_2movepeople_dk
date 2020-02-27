<?php

namespace Drupal\bc_webform\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission action handler.
 *
 * @WebformHandler(
 *   id = "anonymous_submission",
 *   label = @Translation("Anonymous submission"),
 *   category = @Translation("Action"),
 *   description = @Translation("Anonymizes submission data. Remove IP adress and user data."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class AnonymousSubmissionWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $webform_submission->setOwnerId(0);
    $webform_submission->setRemoteAddr('');
  }

}
