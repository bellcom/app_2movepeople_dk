<?php

namespace Drupal\bc_2movepeople_dashboard;

use Drupal\bc_2movepeople_dashboard\Form\AdminSettingsForm;
use Drupal\bc_2movepeople_dashboard\Misc\Utils;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\web_push_notification\Entity\SubscriptionInterface;
use Drupal\web_push_notification\NotificationItem;
use Drupal\web_push_notification\WebPushSenderInterface;

/**
 * Class Bc2movePeopleDashboardMailer.
 */
class Bc2MovepeopleDashboardMailer implements Bc2movepeopleDashboardMailerInterface {

  use StringTranslationTrait;
  use LoggerChannelTrait;

  /**
   * Config factory object.
   *
   * @var ConfigFactoryInterface $configFactory
   */
  private $configFactory;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Web push notifications sender.
   *
   * @var \Drupal\web_push_notification\WebPushSenderInterface
   */
  protected $wpnSender;

  /**
   * Constructs a new Bc2movePeopleDashboardMailer object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, TranslationInterface $string_translation, WebPushSenderInterface $wpn_sender) {
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->stringTranslation = $string_translation;
    $this->wpnSender = $wpn_sender;
  }

  /**
   * Returns default tokens.
   *
   * @return array
   */
  public function getDefaultTokens() {
    return [
      '@recipient_full_name' => $this->t('Recipient full name'),
      '@recipient_user_name' => $this->t('Recipient user name'),
      '@recipient_password_reset_link' => $this->t('Recipient password reset link'),
    ];
  }

  /**
   * Replace token function.
   *
   * @param $string
   * @param $tokensData
   */
  public function replaceDefaultTokens(&$string, $tokensData) {
    if (!empty($tokensData['recipient'])
      && $tokensData['recipient'] instanceof UserInterface) {
      /** @var UserInterface $user */
      $user = $tokensData['recipient'];
      $reset_link = \Drupal::l($this->t('Reset password'),  Url::fromRoute('user.pass'));
      $string = str_replace('@recipient_full_name', Utils::getUserName($user), $string);
      $string = str_replace('@recipient_user_name', $user->getAccountName(), $string);
      $string = str_replace('@recipient_password_reset_link', $reset_link, $string);
    }
  }

  /**
   * Simply send mail function.
   *
   * @param array $message
   *   Email message array.
   *
   * @return array
   */
  public function sendMail(array $message) {
    $message['headers'] = [
      'content-type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
      'MIME-Version' => '1.0',
      'reply-to' => $message['from'],
      'from' => $message['sender'] . ' <' . $message['from'] . '>',
    ];

    if (!empty($message['wpn_to']) && $message['wpn_to'] instanceof UserInterface) {
      $this->sendWpn($message['wpn_to'], $message);
    }

    return $this->mailManager->mail(
      'bc_2movepeople_dashboard',
      'default',
      $message['to'],
      \Drupal::languageManager()->getDefaultLanguage()->getId(), [
      'subject' => $message['subject'],
      'body' => Markup::create($message['body']),
      'headers' => $message['headers']
    ],
      $message['from']
    );
  }

  /**
   * Send wpn notification.
   *
   * @param UserInterface $user
   *   User who get notifications.
   *
   * @param array $params
   *   Notification params array.
   */
  public function sendWpn(UserInterface $user, $params) {
    if (!function_exists('gmp_init')) {
      $this->getLogger('bc_2movepeople_dashboard')->warning('Can not send web push notification, gmp extension is not enabled.');
      return;
    }

    $notification = new NotificationItem();
    /** @var SubscriptionInterface $subscription */
    foreach ($user->get('field_wpn')->referencedEntities() as $subscription) {
      $notification->ids[] = $subscription->id();
    }
    if (empty($notification->ids)) {
      return;
    }
    $config = $this->configFactory->get('web_push_notification.settings');
    $notification->title = $params['subject'];
    $body = FieldPluginBase::trimText([
      'max_length' => $config->get('body_length') ?: 100,
      'word_boundary' => TRUE,
      'ellipsis' => TRUE,
      'html' => FALSE,
    ], strip_tags($params['body'])
    );
    $notification->body = $body;
    $notification->url = Url::fromRoute('<front>')->setAbsolute()->toString();
    if (!empty($params['wpn_url']) && $params['wpn_url'] instanceof Url) {
      $notification->url = $params['wpn_url']->setAbsolute()->toString();
    }

    $this->wpnSender->send($notification);
  }

  /**
   * Milestone Evaluations PDF page.
   *
   * Output a PDF of user evaluations.
   */
  public function sendSbsysMail(array $attachments) {
    $config = $this->configFactory->get(AdminSettingsForm::getConfigName());
    $to = $config->get('sbsys_email.to');
    $subject = $config->get('sbsys_email.subject');
    $body = $config->get('sbsys_email.message');
    return $this->mailManager->mail(
      'bc_2movepeople_dashboard',
      'sbsys',
      $to,
      $this->languageManager->getDefaultLanguage()->getId(), [
        'subject' => $subject,
        'body' => $body,
        'attachments' => $attachments,
      ]
    );
  }

  /**
   * Sends notification to user about assigned task.
   *
   * @param EntityInterface $entity
   *   Task entity.
   *
   * @param string $user_id
   *   User id who will get notification.
   *
   * @param Url $access_url
   *   Notification access URL.
   */
  function sendTaskNotification(EntityInterface $entity, $user_id = '', Url $access_url = NULL) {
    $milestone = Utils::getMilestoneByGoal($entity->id());

    // Get milestone user.
    $milestone_user = $milestone->get('field_progression_user')->referencedEntities()[0];

    $config = \Drupal::config(AdminSettingsForm::getConfigName());

    if (empty($user_id)) {
      // Empty value for responsible manager user means that task have been
      // assigned to milestone owner.
      // @see \Drupal\bc_2movepeople_dashboard\Form\MilestoneTaskAddForm::buildForm()
      // @see \Drupal\bc_2movepeople_dashboard\Form\CommonFormUtils::getGoalRow()
      // for more info.
      $user = $milestone_user;
      $subject = $config->get('task_notification_email_subject');
      $task_body_value = $entity->get('body')->getValue();
      $task_body = '';
      if (!empty($task_body_value)) {
        $task_body = [
          '#type'=> 'processed_text',
          '#text' => $task_body_value[0]['value'],
          '#format' => $task_body_value[0]['format'],
        ];
        $task_body = \Drupal::service('renderer')->render($task_body);
      }
      $body = $config->get('task_notification_email_body');
      $this->replaceDefaultTokens($body, ['recipient' => $user]);
      $body = str_replace("@task_title", $entity->get('title')->value, $body);
      $body = str_replace("@task_body", $task_body, $body);
      $body = str_replace("@dashboard_url", Url::fromRoute('bc_2movepeople_dashboard.main')->toString(), $body);
    }
    else {
      // Send notification to responsible manager.
      $user = User::load($user_id);
      $subject = $config->get('responsible_manager_email_subject');
      $body = $config->get('responsible_manager_email_body');
      $body = str_replace("@manager", $user->getDisplayName(), $body);
      $body = str_replace("@user", $milestone_user->getDisplayName(), $body);
      $body = str_replace("@task_title", $entity->get('title')->value, $body);
    }

    if ($to = $user->get('mail')->value) {
      $this->sendMail([
        'to' => $to,
        'from' => \Drupal::config('system.site')->get('mail'),
        'subject' => $subject,
        'body' => $body,
        'sender' => t('System notify'),
        'wpn_to' => $user,
        'wpn_url' => $access_url
      ]);
    }
  }

}
