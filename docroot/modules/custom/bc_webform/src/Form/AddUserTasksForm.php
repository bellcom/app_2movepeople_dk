<?php

namespace Drupal\bc_webform\Form;

use Drupal\bc_webform\BcWebformHandler;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form to add user task with new webform.
 */
class AddUserTasksForm extends FormBase {
  /**
   * @var EntityTypeManagerInterface $entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bc_webform_add_tasks_batch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $webform_id = NULL) {
    $webform = $this->entityTypeManager->getStorage('webform')->load($webform_id);
    if (empty($webform)) {
      return $form;
    }
    $bcWebformHandler = new BcWebformHandler($webform);
    $form_state->set('webform', $webform);
    $users_list = [];
    foreach ($bcWebformHandler->userWithoutTask() as $uid) {
      $user = User::load($uid);
      $users_list[$uid] = $user->get('field_user_firstname')->value . ' ' . $user->get('field_user_surname')->value;
    }
    $form_state->set('users_ids', array_keys($users_list));

    $form['description'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('You are going to add new task about submit webform @title for @count users.',[
        '@title' => $webform->get('title'),
        '@count' => count($users_list),
      ]),
    ];
    $form['users_lists'] = [
      '#theme' => 'item_list',
      '#items' => $users_list,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#disabled' => empty($users_list),
      '#value' => $this->t('Assign task'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $webform = $form_state->get('webform');
    $usersIds = $form_state->get('users_ids');
    $operations = [];
    foreach($usersIds as  $uid) {
      $operations[] = [
        [self::class, 'addUserTask'],
        [
          $uid,
          $webform
        ],
      ];
    }
    $batch = [
      'title' => $this->t('Assigning tasks to submit webform @title', ['@title' => $webform->get('title')]),
      'operations' => $operations,
      'finished' => [self::class, 'batchComplete'],
    ];
    batch_set($batch);
  }

  /**
   * Add user task operation.
   *
   * @param int $uid
   *   User id.
   * @param WebformInterface $webform
   *   Webrofm object.
   * @param array $context
   *   Context array
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function addUserTask($uid, WebformInterface $webform, array &$context) {
    $bcWebofrmHandler = new BcWebformHandler($webform);
    if ($task_id = $bcWebofrmHandler->addUserTask($uid)) {
      $context['results'][$webform->id()][$uid] = $task_id;
    }
  }

  /**
   * Batch finish callback.
   *
   * @return RedirectResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function batchComplete($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success && !empty(key($results))) {
      $webform = \Drupal::service('entity_type.manager')->getStorage('webform')->load(key($results));
      $bcWebformHandler = new BcWebformHandler($webform);
      $newUserTaskData = $results[key($results)];
      $bcWebformHandler->updateTasksData($newUserTaskData);
      $messenger->addMessage(t('@count users got assigned new task', ['@count' => count($newUserTaskData)]));
    }
    elseif (!empty($operations)) {
      $error_operation = reset($operations);
      $messenger->addError(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
    elseif (empty($results)) {
      $messenger->addWarning(t('There is no users that got new task assigned. Are you sure you have any?'));
    }
    return new RedirectResponse(Url::fromRoute('bc_webform.collection')->toString());
  }

}
