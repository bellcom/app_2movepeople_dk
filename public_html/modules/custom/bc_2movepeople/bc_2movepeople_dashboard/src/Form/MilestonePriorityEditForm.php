<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_dashboard\Form\MilestoneEditForm.
 */

namespace Drupal\bc_2movepeople_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\field\Entity\FieldStorageConfig;

class MilestonePriorityEditForm extends FormBase {

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

    $priority = $this->node->get('field_priority')->value;
    $options = options_allowed_values(FieldStorageConfig::loadByName('node', 'field_priority'));
 
    $form['priority'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $priority,
      '#empty_option' => $this->t('-Select priority-'),
      '#ajax' => [
        'callback' => '::ajaxPriorityChange'
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
    return 'bc_2movepeople-dashboard-milestone-priority-edit-form'. '_' . $this->node->id();
  }
  
  /**
   * {@inheritdoc}
   */
  public function ajaxPriorityChange(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    
    $priority = $form_state->getValue('priority');
   
    $this->node->set("field_priority", $priority);
    $this->node->save();
      
    return $ajax_response;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
       
}
