<?php

namespace Drupal\bc_2movepeople_dashboard\Controller;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\user\Entity\User;
use Mpdf\Mpdf;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Controller\ControllerBase;
use Mpdf\Output\Destination;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\bc_2movepeople_dashboard\bc_2movepeople_dashboardStorage;
use Drupal\bc_2movepeople_dashboard\Form\MilestonePriorityEditForm;
use Drupal\bc_2movepeople_dashboard\Form\MilestoneEditForm;
use Drupal\bc_2movepeople_dashboard\Form\MilestoneStatusEditForm;
use Drupal\Core\Access\AccessResult;
use Drupal\Component\Utility\Html;
use Drupal\views\Views;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contains MovepeopleDashboardController.
 */
class MovepeopleDashboardController extends ControllerBase {

  protected $database;
  protected $mailManager;
  protected $fStr;
  protected $pStr;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database'),
        $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, MailManagerInterface $mail_manager) {
    $this->database = $database;
    $this->fStr = 'feedback';
    $this->pStr = 'progress';
    $this->mailManager = $mail_manager;
  }

  /**
   * Example info page.
   *
   * @return array
   *   A renderable array.
   */
  public function info() {
    $build['content'] = [
      'first_line' => [
        '#prefix' => '<p>',
        '#markup' => 'Drupal includes jQuery and jQuery UI.',
        '#suffix' => '</p>',
      ],
      'second_line' => [
        '#prefix' => '<p>',
        '#markup' => 'We have two examples of using these:',
        '#suffix' => '</p>',
      ],
      'examples_list' => [
        '#theme' => 'item_list',
        '#items' => [
          'An accordion-style section reveal effect. This demonstrates calling a jQuery UI function using Drupal&#39;s rendering system.',
          'Sorting according to numeric &#39;weight.&#39; This demonstrates attaching your own JavaScript code to individual page elements using Drupal&#39;s rendering system.',
        ],
        '#type' => 'ol',
      ],
    ];

    return $build;
  }

  /**
   * Render a list of entries in the database.
   */
  public function entryList() {
    $content = [];

    $content['message'] = [
      '#markup' => $this->t('Generate a list of all entries in the database. There is no filter in the query.'),
    ];

    $rows = [];
    $headers = [t('Id'), t('uid'), t('Name'), t('Surname'), t('Age')];

    foreach ($entries = bc_2movepeople_dashboardStorage::load() as $entry) {
      // Sanitize each entry.
      $rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);
    }
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No entries available.'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

  /**
   * Accordion page implementation.
   *
   * We're allowing a twig template to define our content in this case,
   * which isn't normally how things work, but it's easier to demonstrate
   * the JavaScript this way.
   *
   * @return array
   *   A renderable array.
   */
  public function getJsAccordionImplementation(AccountInterface $user, $limit = NULL) {

    // Remove limit from progressions route if option is disabled.
    $config = $this->config('bc_2movepeople.settings');
    if (empty($config->get('rates_separately')) && !empty($limit)) {
      return $this->redirect('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id()]);
    }

    $title = t('Click on each section to expand or collapse the categories:');
    // Build using our theme. This gives us content, which is not a good
    // practice,.
    $progression_targets = [];
    $entity_ids = self::getProgressionTargets($user->id());
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
    foreach ($nodes as $progrdata) {
      if (!empty($config->get('rates_separately'))) {
        if ($progrdata->get('field_progression_type')->value == 'progression_feedback' && $this->pStr == $limit) {
          continue;
        }
        if ($progrdata->get('field_progression_type')->value <> 'progression_feedback' && $this->fStr == $limit) {
          continue;
        }
      }
      $mtid = $progrdata->get('field_goal_ids')->getValue();
      $progression_targets[$progrdata->id()]['title'] = $progrdata->get('title')->value;
      if ($progrdata->get('field_progression_type')->value == 'progression_feedback') {
        $progression_targets[$progrdata->id()]['feedback'] = TRUE;
      }
      $progression_targets[$progrdata->id()]['id'] = $progrdata->id();
      $progression_targets[$progrdata->id()]['goals'] = [];
      foreach ($mtid as $tid) {
        $gettid = $tid['target_id'];
        $progression_targets[$progrdata->id()]['goals'][$gettid] = self::getGoal($gettid, $progrdata);
      }
    }

    $build = [
      '#theme' => 'bc_2movepeople_dashboard',
      "#title" => 'Dashboard',
      "#subtitle" => $title,
      "#user" => $user->id(),
      '#progression_targets' => $progression_targets,
      "#limit" => $limit,
    ];
    return $build;
  }

  /**
   * User milestones accordion callback.
   */
  public function getMilestoneJsAccordionImplementation(AccountInterface $user) {
    $title = t('Click on each section to expand or collapse the milestone:');

    $progression_targets = [];
    $entity_ids = self::getProgressionTargets($user->id(), 'target_milestone');
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
    foreach ($nodes as $progrdata) {
      $progression_targets[$progrdata->id()]['id'] = $progrdata->id();
      $progression_targets[$progrdata->id()]['title'] = $progrdata->get('title')->value;

      $milestone_edit_form = new MilestoneEditForm($progrdata, $user);
      $progression_targets[$progrdata->id()]['form'] = \Drupal::formBuilder()->getForm($milestone_edit_form);
    }
    $build = [
      '#theme' => 'bc_2movepeople_milestone_dashboard',
      "#title" => t('Dashboard Milestone'),
      "#user" => $user->id(),
      "#subtitle" => $title,
      '#milestone_targets' => $progression_targets,
    ];
    return $build;
  }

  /**
   * Callback function for Connected users list page.
   */
  public function getConnectedUsers() {
    $user = \Drupal::currentUser();
    // Users has no connected users. We redirect them to own tasks.
    if (!$user->hasPermission('access user dashboard')) {
      return $this->redirect('bc_2movepeople_dashboard.user.tasks', ['user' => $user->id()]);
    }
    $build['content'] = $this->renderMyConnectedUsers();

    if (in_array('2mp_supervisor', $user->getRoles())) {
      $build['#title'] = $this->t('Managers');
    }
    else {
      $build['#title'] = $this->t('Clients');
    }

    return $build;
  }

  /**
   * User overview page callback implementation.
   */
  public function getUserOverviewImplementation(AccountInterface $user) {
    $build = [];
    $roles = $user->getRoles();

    if (in_array('2mp_user', $roles)) {
      $build = $this->getUserOverview($user);
    }
    else {
      $build['content'] = $this->renderConnectedUsers($user);
    }

    if (in_array('2mp_supervisor', $roles)) {
      $build['#title'] = $this->t('Managers');
    }
    else {
      $build['#title'] = $this->t('Clients');
    }

    return $build;
  }

  /**
   * Render callback function for user overview page.
   */
  private function getUserOverview(AccountInterface $user) {
    $build = [
      "#theme" => "bc_2movepeople_dashboard_user_overview",
      "#title" => $user->field_user_firstname->value . ' ' . $user->field_user_surname->value,
      "#user" => $user->id(),
    ];

    $entity_progression_ids = $this->getProgressionTargets($user->id(), 'progression');
    $entity_milestone_ids = $this->getProgressionTargets($user->id(), 'target_milestone');
    if (!empty($entity_progression_ids)) {

      // We need to know if there are any questions on categories to show
      // 'Rate' buttons or not.
      $categories = node_load_multiple($entity_progression_ids);
      $questions_found = FALSE;
      foreach ($categories as $category) {
        if (!empty($category->field_goal_ids->entity)) {
          $questions_found = TRUE;
          break;
        }
      }
      $config = $this->config('bc_2movepeople.settings');
      if (!empty($config->get('rates_separately'))) {
        $controls['Progression.feedback'] = [
          '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id(), 'limit' => $this->fStr]),
        ];
        $controls['Progression.show_category'] = [
          '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id(), 'limit' => $this->pStr]),
        ];
      }
      else {
        $controls['Progression.show_category'] = [
          '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id()]),
        ];
      }

      // Add 'Rate category' btn if there are any questions.
      if (!$questions_found) {
        $controls['Progression.rate_category'] = [
          '#attributes' => [
            'disabled' => 'disabled',
          ],
        ];
      }
      else {
        $controls['Progression.rate_category'] = [
          '#url' => Url::fromRoute('bc_2movepeople_rate_progression.user_rates_add', ['user' => $user->id()]),
          '#attributes' => [
            'class' => ['btn-progress', 'use-ajax'],
            'data-dialog-type' => 'modal',
          ],
        ];
      }

      $result_progression = $this->getProgressionsTable($entity_progression_ids);

      if (\Drupal::currentUser()->hasPermission('access category template')) {
        $controls['Progression.save_to_template'] = [
          '#url' => Url::fromRoute('bc_2movepeople_dashboard.save_to_tpl', ['user' => $user->id()]),
          '#attributes' => [
            'class' => ['btn-progress', 'use-ajax'],
            'data-dialog-type' => 'modal',
          ],
        ];
      }

      if (!empty($result_progression['data'])) {
        $build['#table_progression']['data'] = [
          "#theme" => "bc_2movepeople_dashboard_progression_total_table",
          "#type" => 'progression',
          "#table_header" => $result_progression['header'],
          "#table_data" => $result_progression['data'],
        ];
      }
    }
    else {
      $controls['Progression.create_categories'] = [
        '#title' => $this->t('Create categories'),
        '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id()]),
      ];

      $build['#table_progression']['empty'] = $this->t('Currently no measurements found. You need to create a category to create measurements.');
    }
    $build['#table_progression']['controls'] = $this->getControlButtons($controls, ['class' => 'dashboard-overview__control-buttons']);

    $config = $this->config('bc_2movepeople.settings');
    $control_links['Milestone.overall_evaluation'] = [
      '#url' => Url::fromRoute('bc_2movepeople_dashboard.milestone.evaluations', ['user' => $user->id()]),
      '#attributes' => [
        'class' => ['btn', 'use-ajax', 'ui-dialog-buttonpane'],
        'data-dialog-type' => 'modal',
      ],
    ];

    if (!empty($config->get('enable_milestones'))) {
      $control_links['Milestone.show_target_milestones'] = [
        '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.milestones', ['user' => $user->id()]),
      ];
      $build['#table_milestone']['controls'] = $this->getControlButtons($control_links, ['class' => ['dashboard-overview__control-buttons']]);

      if (!empty($entity_milestone_ids)) {
        $result_milestone = $this->getMilestoneTable($entity_milestone_ids);
        $build['#table_milestone']['data'] = [
          "#theme" => "bc_2movepeople_dashboard_progression_total_table",
          "#type" => 'milestone',
          "#table_header" => $result_milestone['header'],
          "#table_data" => $result_milestone['data'],
        ];
      }
    }

    return $build;
  }

  /**
   * Render callback function for Connected users list.
   */
  public static function renderConnectedUsers(AccountInterface $user, $display = 'block_link_boxes') {
    $args = [$user->id()];
    $view = Views::getView('2mp_connected_users');
    if (!is_object($view)) {
      return '';
    }
    $view->setArguments($args);
    $view->setDisplay($display);
    $view->preExecute();
    $view->execute();
    return $view->render();
  }

  /**
   * Render callback function for My connected users list.
   */
  public static function renderMyConnectedUsers() {
    $user = \Drupal::currentUser();
    $user->getAccount();
    $args = [$user->id()];
    $view = Views::getView('2mp_connected_users');

    if (!is_object($view)) {
      return '';
    }

    $view->setArguments($args);
    $view->setDisplay('my_users_list');
    $view->preExecute();
    $view->execute();

    return $view->render();
  }

  /**
   * Get function for goal CT.
   */
  public static function getGoal($nodeid, $progression_target = NULL) {
    $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($nodeid);
    $nodetitle = $nodedata->get('title')->value;
    $subnodes = $nodedata->get('field_subgoal')->getValue();
    $date = $nodedata->get('field_due_date')->value;
    $is_completed = $nodedata->get('field_task_complete')->value;
    $activity_title = $nodedata->get('field_activity_title')->value;
    $evaluation = $nodedata->get('field_evaluation')->value;
    $type = (isset($date)) ? 'task' : 'goal';
    if (!empty($nodedata->field_responsible_manager->entity)) {
      $responsible_manager = $nodedata->field_responsible_manager->entity->id();
    }
    else {
      $responsible_manager = NULL;
    }

    $subgoals = [];
    foreach ($subnodes as $tid) {
      $goal_id = $tid['target_id'];
      $subgoals[$goal_id] = self::getGoal($goal_id, $progression_target);
    }
    $result = [
      'id' => $nodeid,
      'title' => $nodetitle,
      'type' => $type,
      'completed' => $is_completed,
      'date' => $date,
      'rates' => NULL,
      'activity_title' => $activity_title,
      'evaluation' => $evaluation,
      'subgoals' => $subgoals,
      'responsible_manager' => $responsible_manager,
    ];
    if (is_object($progression_target)) {
      $result['rates'] = bc_2movepeople_rate_progression_get_rates($progression_target->id(), $nodeid);
    }

    return $result;
  }

  /**
   * Return progression targets for user.
   *
   * @params
   * $user_id - user uuid
   *
   * @return array
   *   Array with progressions.
   */
  public static function getProgressionTargets($user_id, $progression_type = 'progressions') {
    $progression_types = [
      'progressions' => [
        'progression',
        'progression_feedback',
      ],
      'progression' => ['progression'],
      'feedback' => ['progression_feedback'],
      'target_milestone' => ['target_milestone'],
    ];

    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'progression_target');
    $query->condition('field_progression_user', $user_id);
    $query->condition('field_progression_type', $progression_types[$progression_type], 'IN');
    $entity_ids = $query->execute();

    return $entity_ids;
  }

  /**
   * Get average target rate.
   *
   * @params
   * $target_id - progression target id
   *
   * @return float
   *   Average value of rate.
   */
  public static function getTargetAveragePoints($target_id) {
    $query = \Drupal::database()->select('bc_2movepeople_rate_progression', 'rates');
    $query->condition('progression_target_id', $target_id, '=');
    $query->addExpression("FROM_UNIXTIME(created,  '%d.%m')", 'dates');
    $query->addExpression("AVG(rate)", 'avg_rates');
    $query->addExpression("MAX(created)", 'created');
    $query->GroupBy('dates');
    $query->orderBy('created', 'ASC');
    $result = $query->execute()->fetchAll();
    return $result;
  }

  /**
   * Prepare progression data by entity id.
   *
   * @params
   * $entity_ids - progresson ids
   *
   * @return array
   *   Progressions table data.
   */
  public static function getProgressionsTable($entity_ids) {
    $dates = [];
    $table = [];

    $query = \Drupal::database()->select('bc_2movepeople_rate_progression', 'rates');
    $query->condition('progression_target_id', $entity_ids, 'IN');
    $query->addExpression("FROM_UNIXTIME(created,  '%d.%m')", 'dates');
    $query->addExpression("MAX(created)", 'created');
    $query->GroupBy('dates');
    $query->orderBy('created', 'ASC');

    $result = $query->execute()->fetchAll();
    foreach ($result as $row) {
      $dates[] = $row->dates;
    }
    $header = array_merge([t('Categories')], $dates);

    if ($dates) {
      foreach ($entity_ids as $key => $target_id) {
        $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($target_id);
        if ($nodedata) {
          $title = $nodedata->get('title')->value;
          $table[$key] = array_fill(1, count($dates), 0);
          $avg_rates = self::getTargetAveragePoints($target_id);
          foreach ($avg_rates as $row) {
            $table[$key][array_search($row->dates, $header)] = round((float) $row->avg_rates, 2);
          }
          $table[$key] = array_merge([$title], $table[$key]);
        }
      }
    }

    return ['header' => $header, 'data' => $table];
  }

  /**
   * Return progression targets for user.
   *
   * @params
   * $target_id - progression target nid
   *
   * @return array
   *   Milestones table data.
   */
  public static function getMilestoneTable($entity_ids) {
    $table = [];
    $header = array_merge([t('Target Milestones'), t('Priority'), t('Status')]);

    foreach ($entity_ids as $key => $target_id) {
      $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($target_id);
      if (is_object($nodedata) && $nodedata->getType() === 'progression_target') {
        $title = $nodedata->get('title')->value;
        $priority_edit_form = new MilestonePriorityEditForm($nodedata);
        $priority = \Drupal::formBuilder()->getForm($priority_edit_form);

        $status_edit_form = new MilestoneStatusEditForm($nodedata);
        $status = \Drupal::formBuilder()->getForm($status_edit_form);
        $table[$key] = [$title, $priority, $status];
      }

    }
    return [
      'header' => $header,
      'data' => $table,
    ];
  }

  /**
   * Return progression targets for user.
   *
   * @params
   * $target_id - progression target nid
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *
   * @return array
   *   User tasks render array.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserTasks(AccountInterface $user) {
    $user_name = $user->getDisplayName();

    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'progression_target');
    $query->condition('field_progression_type', 'target_milestone');
    $query->condition('field_progression_user', $user->id());
    $entity_ids = $query->execute();
    $progression_targets = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);

    $hour = 0;
    $minute = 0;
    $second = 0;

    $tasks = [];
    foreach ($progression_targets as $progrdata) {
      $goal_ids = $progrdata->get('field_goal_ids')->getValue();

      foreach ($goal_ids as $goal_id) {
        $tid = $goal_id['target_id'];
        $goal = self::getGoal($tid);

        $date = ($goal['date'] ? $goal['date'] : date('Y-m-d'));
        if ($goal['completed'] || !empty($goal['responsible_manager'])) {
          continue;
        }

        list($year, $month, $day) = explode('-', $date);

        $timestamp = mktime((int) $hour, (int) $minute, (int) $second, (int) $month, (int) $day, (int) $year);
        $is_remind = self::isRemindSession($date);

        $goal['is_remind'] = $is_remind;

        $tasks[$timestamp] = $goal;
        $second++;
      }
    }
    ksort($tasks, SORT_NUMERIC);

    $build = [
      "#theme" => 'bc_2movepeople_dashboard_user_tasks_overview',
      "#title" => t('Scheduled tasks for %name.', ['%name' => $user_name]),
      "#tasks" => $tasks,
      "#user" => $user->id(),
    ];

    return $build;
  }

  /**
   * Get progression goals list (key - goal id, value - goal title).
   *
   * @params
   * $progression_node - progression node object
   *
   * @return array
   *   The list of goals.
   */
  public static function getProgressionGoalsList($progression_node) {

    $goal_ids = $progression_node->get('field_goal_ids')->getValue();

    $result = [];
    foreach ($goal_ids as $tid) {
      $goal_node = \Drupal::entityTypeManager()->getStorage('node')->load($tid['target_id']);
      $result[$goal_node->id()] = $goal_node->get('title')->value;
    }

    return $result;
  }

  /**
   * Compare current date and task due date.
   *
   * @params
   * $date - task due date
   *
   * @return int
   *   Flag (0 - false, 1 - is remind true, 2 - is expired true).
   */
  public static function isRemindSession($date) {
    $timezone = drupal_get_user_timezone();

    $config = \Drupal::config('bc_2movepeople_dashboard.AdminSettings');
    $reminder_days = $config->get('task_reminder_due_date');

    $current_date = new \DateTime('now', new \DateTimezone($timezone));
    $current_unixtimestamp = $current_date->getTimestamp();

    $due_date = new \DateTime($date, new \DateTimezone($timezone));
    $due_date_unixtimestamp = $due_date->getTimestamp();

    if ($due_date_unixtimestamp > $current_unixtimestamp) {
      $diff_date = $current_date->diff($due_date);
      $is_remind = $diff_date->d < $reminder_days ? 1 : 0;
    }
    // Due date expire.
    else {
      // Date expired.
      $is_remind = 2;
    }

    return $is_remind;
  }

  /**
   * Check reminder time for current user.
   */
  public static function checkUserSessionReminder() {
    $is_remind = FALSE;
    $current_date = new \DateTime('now', new \DateTimezone(drupal_get_user_timezone()));
    $current_unixtimestamp = $current_date->getTimestamp();

    $tempstore = \Drupal::service('user.private_tempstore')->get('bc_2movepeople_dashboard');

    // The redimder original value is 86400 = 24h.
    // For test.
    $offset = 30;
    if ($tempstore->get('is_reminded') && $tempstore->get('is_reminded')['expire'] < $current_unixtimestamp) {

      $tempstore->delete('is_reminded');
      $expire_unixtimestamp = $current_unixtimestamp + $offset;
      $tempstore->set('is_reminded', ['expire' => $expire_unixtimestamp]);

      $is_remind = TRUE;
    }
    elseif (!$tempstore->get('is_reminded')) {
      $expire_unixtimestamp = $current_unixtimestamp + $offset;
      $tempstore->set('is_reminded', ['expire' => $expire_unixtimestamp]);
      $is_remind = TRUE;
    }

    return $is_remind;
  }

  /**
   * Set reminder message (drupal alert) for user tasks.
   */
  public static function setUserTasksReminder($user_id) {

    $entity_ids = self::getProgressionTargets($user_id, 'target_milestone');
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);

    foreach ($nodes as $progrdata) {
      $goal_ids = $progrdata->get('field_goal_ids')->getValue();

      foreach ($goal_ids as $tid) {

        $goal = self::getGoal($tid['target_id'], $progrdata);
        $is_remind = self::isRemindSession($goal['date']);
        if (empty($goal['responsible_manager']) && !$goal['completed'] && $is_remind) {
          drupal_set_message($goal['title'] . ' - ' . ($is_remind == 2 ? t('due date expired') : t('due to expire')) . ': ' . $goal['date']);
        }
      }
    }
  }

  /**
   * Check access permission for current user.
   *
   * Function implements in routing.yml.
   *
   * @params
   * $account - current logged user object
   * $user - user object from routing
   *
   * @return object
   *   forbidden/allowed
   */
  public function access(AccountInterface $account, UserInterface $user = NULL) {

    $account_roles = $account->getRoles();

    if (in_array("administrator", $account_roles) ||
        in_array("2mp_manager", $account_roles)) {
      return AccessResult::allowed();
    }
    $route_name = \Drupal::routeMatch()->getRouteName();

    $access = AccessResult::forbidden();

    switch ($route_name) {

      case "bc_2movepeople_dashboard.user.tasks":
        $user_roles = $user->getRoles();
        if (in_array("2mp_manager", $account_roles)) {
          $manager_ids = self::getManagerIds($user->id());
          if (in_array($account->id(), $manager_ids)) {
            $access = AccessResult::allowed();
          }
        }
        elseif (in_array("2mp_user", $user_roles)) {
          if ($account->id() == $user->id()) {
            $access = AccessResult::allowed();
          }
        }
        break;
    }

    return $access;
  }

  /**
   * Return connected users for admin/manager.
   *
   * @params
   * $user_id - admin/manager user id
   *
   * @return array
   *   The array with ids.
   */
  public static function getManagerIds($user_id) {
    $query = \Drupal::entityQuery('user');
    $query->condition('status', 1);
    $query->condition('field_connected_users', $user_id);
    return $query->execute();
  }

  /**
   * Simply send mail function.
   *
   * @param array $message
   *   Email message array.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted for delivery, FALSE otherwise.
   */
  public static function sendMail(array $message) {
    $send_mail = new PhpMail();
    $message['headers'] = [
      'content-type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
      'MIME-Version' => '1.0',
      'reply-to' => $message['from'],
      'from' => $message['sender'] . ' <' . $message['from'] . '>',
    ];
    return $send_mail->mail($message);
  }

  /**
   * Control buttons block definition.
   */
  public static function getControlButtons(array $links, array $attributes = []) {
    $build = [
      '#type' => 'container',
      '#attributes' => array_merge_recursive($attributes, ['class' => ['control-buttons']]),
    ];

    foreach ($links as $key => $link) {
      $button = _bc_2movepeople_dashboard_button($key);
      $value = !empty($button['title']) ? $button['title'] : $button['name'];

      // Button (only buttons can be disabled).
      if (isset($link['#attributes']['disabled'])) {
        $type = 'button';
        $link['#value'] = $value;
      }

      // Link.
      else {
        $type = 'link';
        $link['#title'] = $value;
      }

      $build[$key] = array_merge_recursive($link, [
        '#type' => $type,
        '#attributes' => [
          'class' => ['btn', 'btn-default'],
        ],
      ]);
    }

    return $build;
  }

  /**
   * Prepare a data array with milestones target evaluations.
   *
   * @return array
   *   Data with Milestone evaluations.
   */
  private static function getMilestoneEvaluationsData(AccountInterface $user) {
    $milestones_ids = self::getProgressionTargets($user->id(), 'target_milestone');
    $milestones_data = [];
    $milestone_nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($milestones_ids);

    foreach ($milestone_nodes as $milestone_node) {
      $milestones_data[$milestone_node->id()]['milestone'] = [
        'name' => $milestone_node->getTitle(),
        'purpose' => $milestone_node->get('field_purpose')->getValue()[0]['value'],
        'id' => $milestone_node->id(),
      ];
      $target_nodes = $milestone_node->get('field_goal_ids')->referencedEntities();
      if (!empty($target_nodes)) {
        foreach ($target_nodes as $target_node) {
          $evaluation = $target_node->get('field_evaluation')->getValue();
          $milestones_data[$milestone_node->id()]['targets'][$target_node->id()] = [
            'name' => $target_node->getTitle(),
            'evaluation' => empty($evaluation[0]['value']) ? NULL : $evaluation[0]['value'],
          ];
        }
      }
      else {
        unset($milestones_data[$milestone_node->id()]);
      }
    }

    return $milestones_data;
  }

  /**
   * Milestone Evaluations page.
   *
   * Show user Milestone targets and its
   * evaluations.
   *
   * @return array
   *   A renderable array.
   */
  public static function getMilestoneEvaluations(AccountInterface $user) {
    $build = [
      '#theme' => 'bc_2movepeople_milestone_evaluations',
      '#title' => t('Milestone evaluations'),
      '#user' => $user->id(),
      '#milestones_data' => self::getMilestoneEvaluationsData($user),
    ];

    return $build;
  }

  /**
   * Milestone Evaluations PDF page.
   *
   * Output a PDF of user evaluations.
   */
  public function getMilestoneEvaluationsPdf(AccountInterface $user) {
    $html = $this->getMilestoneEvaluationsContent($user);
    $mpdf = new Mpdf(['tempDir' => 'sites/default/files/tmp']);
    $mpdf->WriteHTML($html);
    $mpdf->Output('user_' . $user->id() . '_evaluations.pdf', 'D');
    exit;
  }

  /**
   * Milestone Evaluations content generate mathod.
   */
  private function getMilestoneEvaluationsContent(AccountInterface $user) {
    $config = $this->config('bc_2movepeople_dashboard.AdminSettings');

    // Load Milestone evaluation header.
    $header_node = NULL;
    if (!empty($config->get('milestone_evaluation_header_nid'))) {
      $header_node = \Drupal::entityTypeManager()->getStorage('node')
        ->load($config->get('milestone_evaluation_header_nid'));
    }

    $name = null;
    $field_firstname = $user->get('field_user_firstname')->getValue();
    $field_lastname = $user->get('field_user_surname')->getValue();

    if (!empty($field_firstname[0]['value']) && !empty($field_lastname[0]['value'])) {
      $name = $field_firstname[0]['value'] . ' ' . $field_lastname[0]['value'];
    }

    $social_security_number = '';
    if ($field_social_security_number = $user->get('field_social_security_number')->getValue()) {
      $social_security_number = $field_social_security_number[0]['value'];
    }

    $build = [
      '#theme' => 'bc_2movepeople_milestone_evaluations_pdf',
      '#header' => empty($header_node) ? NULL : $header_node->body->view(['label' => 'hidden']),
      '#user' => $user->id(),
      '#name' => $name,
      '#social_security_number' => $social_security_number,
      '#milestones_data' => $this->getMilestoneEvaluationsData($user),
    ];

    $html = \Drupal::service('renderer')->renderRoot($build);
    $html = Html::transformRootRelativeUrlsToAbsolute($html, \Drupal::request()->getSchemeAndHttpHost());
    return $html;
  }

  /**
   * Milestone Evaluations PDF page.
   *
   * Output a PDF of user evaluations.
   */
  public function sendMilestoneEvaluationsToSbsys(AccountInterface $user) {
    $config = $this->config('bc_2movepeople_dashboard.AdminSettings');
    $attachments = [];

    // Getting PDF file.
    $html = $this->getMilestoneEvaluationsContent($user);
    $mpdf = new Mpdf(['tempDir' => 'sites/default/files/tmp']);
    $mpdf->WriteHTML($html);
    $pdf_content = $mpdf->Output('user_' . $user->id() . '_evaluations.pdf', Destination::STRING_RETURN);
    $attachments[] = [
      'filecontent' => $pdf_content,
      'filename' => 'evaluation.pdf',
      'filemime' => 'application/pdf',
    ];
    // Gettings xml file.
    $xml_content = \Drupal::service('sbsys_integration.xml_handler')->generate(['user' => User::load($user->id())]);
    $attachments[] = [
      'filecontent' => $xml_content,
      'filename' => 'sbsys.xml',
      'filemime' => 'application/xml',
    ];

    $to = $config->get('sbsys_email.to');
    $subject = $config->get('sbsys_email.subject');
    $message = $config->get('sbsys_email.message');

    $mail = $this->mailManager->mail(
      'bc_2movepeople_dashboard',
      'sbsys',
      $to,
      \Drupal::languageManager()->getDefaultLanguage()->getId(), [
        'subject' => $subject,
        'body' => $message,
        'attachments' => $attachments,
      ]
    );

    if ($mail['result']) {
      drupal_set_message($this->t('Email has been sent to sbsys'));
    }
    else {
      drupal_set_message($this->t('Email sending to SBSYS failed. See error log for more details.'));
    }

    $url = Url::fromRoute('bc_2movepeople_dashboard.user.overview', ['user' => $user->id()]);
    $response = new RedirectResponse($url->toString());
    $response->send();
  }

}
