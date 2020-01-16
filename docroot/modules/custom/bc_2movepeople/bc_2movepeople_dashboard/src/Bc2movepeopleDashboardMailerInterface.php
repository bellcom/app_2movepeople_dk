<?php

namespace Drupal\bc_2movepeople_dashboard;

/**
 * Provides an interface for assembly and dispatch of mail messages.
 */
interface Bc2movepeopleDashboardMailerInterface {

  /**
   * Sends mail messages as appropriate for a given Message form submission.
   *
   * Can potentially send up to three messages as follows:
   * - To the configured recipient;
   * - Auto-reply to the sender; and
   * - Carbon copy to the sender.
   *
   * @param array $message
   *   Mail message array.
   */
  public function sendMail(array $message);

}
