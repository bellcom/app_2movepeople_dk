<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneStatusEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\field\Entity\FieldStorageConfig;

class MilestoneStatusEditForm extends FormBase {

  protected $node;
  private $updated_msg = 'Records successfully updated.';
  private $wrong_msg = 'Something wrong.';

  public function __construct($nodedata) {
    $this->node = $nodedata;
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $status = $this->node->get('field_progression_status')->value;
    $options = options_allowed_values(FieldStorageConfig::loadByName('node', 'field_progression_status'));
    //$status = $status_obj->getSettings()['allowed_values'][$status_obj->value];
 
    $form['status'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $status,
      '#empty_option' => $this->t('-Select status-'),
      '#ajax' => [
        'callback' => '::ajaxStatusChange'
      ]
    ];
    
    // Disable caching on this form.
    $form_state->setCached(FALSE);
    
    $form['actions'] = [
      '#type' => 'actions',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-dashboard-milestone-status-edit-form'. '_' . $this->node->id();
  }
  
  /**
   * {@inheritdoc}
   */
  public function ajaxStatusChange(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    
    $status = $form_state->getValue('status');
   
    $this->node->set("field_progression_status", $status);
    $this->node->save();
      
    return $ajax_response;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
       
}
