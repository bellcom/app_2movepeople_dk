<?php

/**
 * @file
 * Contains \Drupal\bc_2movepeople_meeting\Form\MeetingAddForm.
 */

namespace Drupal\bc_2movepeople_meeting\Form;

use Drupal\bc_2movepeople_dashboard\Bc2movepeopleDashboardMailerInterface;
use Drupal\bc_2movepeople_dashboard\Controller\MovepeopleDashboardController;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MeetingAddForm extends FormBase {

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
   * Dashboard mailer service.
   *
   * @var Bc2movepeopleDashboardMailerInterface
   */
  protected $dashboardMailer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('2movepeople_dashboard.mailer')
    );
  }

  /**
   * MeetingAddForm constructor object.
   */
  public function __construct(Bc2movepeopleDashboardMailerInterface $dashboard_mailer) {
    $this->dashboardMailer = $dashboard_mailer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $this->user = $user;

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Meeting title'),
      '#required' => TRUE,
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

    // Meeting video link.
    $form['video_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video link'),
      '#description' => $this->t('URL to video link, e.g. https://youtu.be/...'),
      '#pattern' => 'https?:\/\/.*',
    ];

    $form['send_user_invite'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send invite to user'),
      '#default_value' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_2movepeople-meeting-meeting-add-form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Getting the form values.
    $title = $form_state->getValue('title');
    $place = $form_state->getValue('place');
    $start_date = $form_state->getValue('start_date');
    $end_date = $form_state->getValue('end_date');
    $video_link = $form_state->getValue('video_link');
    $send_user_invite = $form_state->getValue('send_user_invite');

    // Creating meeting node.
    $this->meeting = Node::create(array(
      'status' => 1,
      'type' => 'meeting',
      'title' => $title,
      'field_meeting_place' => $place,
      'field_meeting_start_date' => ($start_date) ? $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => 'UTC']) : NULL,
      'field_meeting_end_date' => ($end_date) ? $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, ['timezone' => 'UTC']) : NULL,
      'field_meeting_user' => ['target_id' => $this->user->id()],
      'field_meeting_video_link' => $video_link,
    ));
    $this->meeting->save();

    // Sending the invitation email.
    if ($send_user_invite) {
      $config = $this->config('bc_2movepeople_meeting.admin_settings');

      // Making replacements.
      $to_replace['subject'] = $config->get('meeting_invite_email_subject');
      $to_replace['body'] = $config->get('meeting_invite_email_body');
      foreach ($to_replace as &$text) {
        $this->dashboardMailer->replaceDefaultTokens($text, ['recipient' => $this->user]);
        $text = str_replace("@author", \Drupal::currentUser()->getDisplayName(), $text);
        $text = str_replace("@meeting_title", $this->meeting->getTitle(), $text);
        $text = str_replace("@meeting_place", $place, $text);
        $text = str_replace("@start_date", \Drupal::service('date.formatter')->format($start_date->getTimestamp(), 'short'), $text);
        $text = str_replace("@end_date", \Drupal::service('date.formatter')->format($end_date->getTimestamp(), 'short'), $text);
        $text = str_replace("@meeting_link", $video_link, $text);
      }

      $this->dashboardMailer->sendMail([
        'to' => $this->user->get('mail')->value,
        'from' => \Drupal::config('system.site')->get('mail'),
        'subject' => $to_replace['subject'],
        'body' => $to_replace['body'],
        'sender' => $this->t('System notify'),
        'wpn_to' => $this->user,
      ]);
    }

    $form_state->setRedirectUrl(Url::fromRoute('bc_2movepeople_meeting.user.meetings', ['user' => $this->user->id()]));
  }

}
