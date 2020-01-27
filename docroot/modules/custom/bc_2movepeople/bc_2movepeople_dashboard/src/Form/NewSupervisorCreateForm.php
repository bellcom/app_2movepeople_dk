<?php

namespace Drupal\bc_2movepeople_dashboard\Form;

/**
 * Form to add new supervisor user.
 */
class NewSupervisorCreateForm extends NewUserCreateFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-supervisor-create-form';
  }

}
