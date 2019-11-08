<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_meeting\Form\MeetingEditForm.
 */

namespace Drupal\bc_2movepeople_meeting\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Url;

class MeetingEditForm extends FormBase {

  /**
   * User entity, which meeting is held with.
   *
   * @var Drupal\user\UserInterface
   */
  private $user;

  /**
   * Meeting node.
   *
   * @var Drupal\node\NodeInterface
   */
  private $meeting;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL, NodeInterface $meeting = NULL) {
    $this->user = $user;
    $this->meeting = $meeting;

    $form['#prefix'] = '<div class="dashboard-overview">';
    $form['#suffix'] = '</div>';

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Meeting title'),
      '#required' => TRUE,
      '#default_value' => $meeting->getTitle(),
    ];

    $form['place'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Place'),
      '#required' => TRUE,
    ];

    $form['start_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start date'),
      '#required' => TRUE,
    ];

    $form['end_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End date'),
      '#required' => TRUE,
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['#prefix'] = '<div class="row custom-form-fields edit-progression__control-buttons"><div class="col-xs-12 text-right">';
    $form['actions']['#suffix'] = '</div></div>';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Update'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['btn-default'],
      ],
    ];

    $form['actions']['back'] = [
      '#title' => $this->t('Back'),
      '#type' => 'link',
      '#url' => Url::fromRoute('bc_2movepeople_meeting.user.meetings', ['user' => $user->id()]),
      '#attributes' => array(
        'class' => ['btn', 'btn-default', 'link-btn'],
      ),
    ];

    $form = $this->populateFormData($form, $form_state, $meeting);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-meeting-meeting-edit-form';
  }

  /**
   * Populates meeting form with data from real meeting.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param \Drupal\node\NodeInterface $meeting
   *   Meeting node.
   *
   * @return array
   *   Form array with appended page.
   */
  public function populateFormData(array $form, FormStateInterface $form_state, NodeInterface $meeting) {
    $form['title']['#default_value'] = $meeting->getTitle();
    if ($place = $meeting->field_meeting_place->value) {
      $form['place']['#default_value'] = $place;
    }
    if ($start_date = $meeting->field_meeting_start_date->value) {
      $form['start_date']['#default_value'] = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $start_date, new \DateTimeZone('UTC'));
    }
    if ($end_date = $meeting->field_meeting_end_date->value) {
      $form['end_date']['#default_value'] = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_date, new \DateTimeZone('UTC'));
    }
    if ($notes = $meeting->field_meeting_notes->value) {
      $form['notes']['#default_value'] = $notes;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Getting form values.
    $title = $form_state->getValue('title');
    $place = $form_state->getValue('place');
    $start_date = $form_state->getValue('start_date');
    $end_date = $form_state->getValue('end_date');
    $notes = $form_state->getValue('notes');

    // Updating meeting node.
    $this->meeting->title = $title;
    $this->meeting->field_meeting_place = $place;
    $this->meeting->field_meeting_start_date = ($start_date) ? $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => DateTimeItemInterface::STORAGE_TIMEZONE]) : NULL;
    $this->meeting->field_meeting_end_date = ($end_date) ? $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => DateTimeItemInterface::STORAGE_TIMEZONE]) : NULL;
    $this->meeting->field_meeting_notes = $notes;
    $this->meeting->save();

    $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_meeting.user.meetings', ['user' => $this->user->id()]));
  }

}
