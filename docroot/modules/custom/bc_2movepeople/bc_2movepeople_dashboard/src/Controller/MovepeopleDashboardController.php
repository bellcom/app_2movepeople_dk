<?php

namespace Drupal\bc_2movepeople_dashboard\Controller;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use \Drupal\taxonomy\Entity\Term;
use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\bc_2movepeople_dashboard\bc_2movepeople_dashboardStorage;
use Drupal\bc_2movepeople_dashboard\Form\MilestonePriorityEditForm;
use Drupal\bc_2movepeople_dashboard\Form\MilestoneEditForm;
use Drupal\bc_2movepeople_dashboard\Form\MilestoneStatusEditForm;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;
use \Drupal\views\Views;
use Drupal\user\UserInterface;
use Drupal\user\Entity\User;

/**
 * Controller for js_example pages.
 *
 * @ingroup js_example
 */
class MovepeopleDashboardController extends ControllerBase {

  protected $database;
  protected $f_str;
  protected $p_str;

  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database')
    );
  }

  public function __construct(Connection $database) {
    $this->database = $database;
    $this->f_str = 'feedback';
    $this->p_str = 'progress';
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
    $content = array();

    $content['message'] = array(
      '#markup' => $this->t('Generate a list of all entries in the database. There is no filter in the query.'),
    );

    $rows = array();
    $headers = array(t('Id'), t('uid'), t('Name'), t('Surname'), t('Age'));

    foreach ($entries = bc_2movepeople_dashboardStorage::load() as $entry) {
      // Sanitize each entry.
      $rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', (array) $entry);
    }
    $content['table'] = array(
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No entries available.'),
    );
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

  public function getJsWeightImplementation() {
    // Create an array of items with random-ish weight values.
    $weights = array(
      'red' => -4,
      'blue' => -2,
      'green' => -1,
      'brown' => -2,
      'black' => -1,
      'purple' => -5,
    );

    // Start building the content.
    $build = array();
    // Main container DIV. We give it a unique ID so that the JavaScript can
    // find it using jQuery.
    $build['content'] = array(
      '#markup' => '<div id="js-weights"></div>',
    );
    // Attach library containing css and js files.
    $build['#attached']['library'][] = 'js_example/js_example.weights';
    // Attach the weights array to our JavaScript settings. This allows the
    // color scripts we just attached to discover their weight values, by
    // accessing drupalSettings.js_example.js_weights.*color*. The color scripts
    // only use this information for display to the user.
    $build['#attached']['drupalSettings']['js_example']['js_weights'] = $weights;

    return $build;
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

    //Remove limit from progressions route if option is disabled
    $config = \Drupal::config('bc_2movepeople.settings');
    if (empty($config->get('rates_separately')) && !empty($limit)) {
      return $this->redirect('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id()]);
    }

    $title = t('Click on each section to expand or collapse the categories:');
    // Build using our theme. This gives us content, which is not a good
    // practice,.

    $progression_targets = array();
    $entity_ids = self::getProgressionTargets($user->id());
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
    foreach ($nodes as $progrdata) {
      if (!empty($config->get('rates_separately'))) {
        if ($progrdata->get('field_progression_type')->value == 'progression_feedback' && $this->p_str == $limit) {
          continue;
        }
        if ($progrdata->get('field_progression_type')->value <> 'progression_feedback' && $this->f_str == $limit) {
          continue;
        }
      }
      $mtid = $progrdata->get('field_goal_ids')->getValue();
      $progression_targets[$progrdata->id()]['title'] = $progrdata->get('title')->value;
      if ($progrdata->get('field_progression_type')->value == 'progression_feedback') {
        $progression_targets[$progrdata->id()]['feedback'] = TRUE;
      }
      $progression_targets[$progrdata->id()]['id'] = $progrdata->id();
      $progression_targets[$progrdata->id()]['goals'] = array();
      foreach ($mtid as $tid) {
        $gettid = $tid['target_id'];
        $progression_targets[$progrdata->id()]['goals'][$gettid] = self::getGoal($gettid, $progrdata);
      }
    }

    $build = array(
      '#theme' => 'bc_2movepeople_dashboard',
      "#title" => 'Dashboard',
      "#subtitle" => $title,
      "#user" => $user->id(),
      '#progression_targets' => $progression_targets,
      "#limit" => $limit,
    );
    return $build;
  }

  public function getMilestoneJsAccordionImplementation(AccountInterface $user) {
    $title = t('Click on each section to expand or collapse the progressions:');

    $progression_targets = array();
    $entity_ids = self::getProgressionTargets($user->id(), 'target_milestone');
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
    foreach ($nodes as $progrdata) {
      $progression_targets[$progrdata->id()]['id'] = $progrdata->id();
      $progression_targets[$progrdata->id()]['title'] = $progrdata->get('title')->value;

      $milestone_edit_form = new MilestoneEditForm($progrdata, $user);
      $progression_targets[$progrdata->id()]['form'] = \Drupal::formBuilder()->getForm($milestone_edit_form);

      //$progression_targets[$progrdata->id()]['form'] = \Drupal::formBuilder()->getForm(\Drupal\bc_2movepeople_dashboard\Form\MilestoneEditForm::class, $progrdata);
    }
    $build = array(
      '#theme' => 'bc_2movepeople_milestone_dashboard',
      "#title" => 'Dashboard Milestone',
      "#user" => $user->id(),
      "#subtitle" => $title,
      '#milestone_targets' => $progression_targets,
    );
    return $build;
  }

  /**
   * Callback function for Connected users list page.
   */
  public function getConnectedUsers() {
    $user = \Drupal::currentUser();
    $build['content'] = $this->renderConnectedUsers($user->getAccount());
    if (in_array('2mp_supervisor', $user->getRoles())) {
      $build['#title'] = $this->t('Managers');
    }
    return $build;
  }

  public function getUserOverviewImplementation(AccountInterface $user) {
    $build = [];
    $roles = $user->getRoles();
    if (in_array('2mp_user', $roles)) {
      $build = $this->getUserOverview($user);
    }

    if (in_array('2mp_manager', $roles)) {
      $build['#title'] = $this->t('Clients');
      $build['content'] = $this->renderConnectedUsers($user);
    }

    return $build;
  }

  /**
   * Render callback function for user overview page.
   */
  private function getUserOverview(AccountInterface $user) {
    $build = array(
      "#theme" => "bc_2movepeople_dashboard_user_overview",
      "#title" => $user->field_user_firstname->value . ' ' . $user->field_user_surname->value,
      "#user" => $user->id(),
    );

    $entity_progression_ids = array_keys($this->getProgressionTargets($user->id(), 'progressions'));
    $entity_milestone_ids = array_keys($this->getProgressionTargets($user->id(), 'target_milestone'));

    if (!empty($entity_progression_ids)) {

      //We need to know if there are any questions on categories to show 'Rate' buttons or not
      $categories = node_load_multiple($entity_progression_ids);
      $questions_found = FALSE;
      foreach ($categories as $category) {
        if (!empty($category->field_goal_ids->entity)) {
          $questions_found = TRUE;
          break;
        }
      }
      $config = \Drupal::config('bc_2movepeople.settings');
      if (!empty($config->get('rates_separately'))) {
        $controls['user_rate_category'] = [
          '#title' => $this->t('Doing well'),
          '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id(), 'limit' => $this->f_str]),
        ];
        $controls['category'] = [
          '#title' => $this->t('Show category'),
          '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id(), 'limit' => $this->p_str]),
        ];
      }
      else {
        $controls['category'] = [
          '#title' => $this->t('Show category'),
          '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id()]),
        ];
      }

      //Add 'Rate category' btn if there are any questions
      if ($questions_found) {
        $controls['rate_category'] = [
          '#title' => $this->t('Rate category'),
          '#url' => Url::fromRoute('bc_2movepeople_rate_progression.user_rates_add', ['user' => $user->id()]),
          '#attributes' => [
            'class' => ['btn-progress', 'use-ajax'],
            'data-dialog-type' => 'modal',
          ],
        ];
      }
      else {
        //No questions - do not show 'Rate category' btn, but add a message
        $build['#table_progression']['message'] = t('To rate users you have to add Categories and question inside categories');
      }

      $result_progression = $this->getProgressionsTable($entity_progression_ids);

      if (\Drupal::currentUser()->hasPermission('access category template')) {
        $controls['save_to_tpl'] = [
          '#title' => $this->t('Save to template'),
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
          "#table_data" => $result_progression['data']
        ];
      }
    }
    else {
      $controls = [[
      '#title' => $this->t('Create categories'),
      '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.progressions', ['user' => $user->id()]),
      ]];
    }
    $build['#table_progression']['controls'] = $this->getControlButtons($controls, ['class' => 'dashboard-overview__control-buttons']);

    $config = \Drupal::config('bc_2movepeople.settings');
    if (!empty($config->get('enable_milestones'))) {
      $conrtol_links['show_milestones'] = [
      '#title' => $this->t('Show Target Milestones'),
      '#url' => Url::fromRoute('bc_2movepeople_dashboard.user.milestones', ['user' => $user->id()]),
      ];
      $conrtol_links['rate_milestones'] = [
      '#title' => $this->t('Samlet evaluering'),
      '#url' => Url::fromRoute('<current>'), //TODO: Later this button will get all the "ratings from each "target milestone". But it does nothing for now.
      ];
      $build['#table_milestone']['controls'] = $this->getControlButtons($conrtol_links, ['class' => ['dashboard-overview__control-buttons']]);

      if (!empty($entity_milestone_ids)) {
        $result_milestone = $this->getMilestoneTable($entity_milestone_ids);
        $build['#table_milestone']['data'] = array(
          "#theme" => "bc_2movepeople_dashboard_progression_total_table",
          "#type" => 'milestone',
          "#table_header" => $result_milestone['header'],
          "#table_data" => $result_milestone['data'],
        );
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

  static function getGoal($nodeid, $progression_target = NULL) {
    $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($nodeid);
    $nodetitle = $nodedata->get('title')->value;
    $subnodes = $nodedata->get('field_subgoal')->getValue();
    $date = $nodedata->get('field_due_date')->value;
    $is_completed = $nodedata->get('field_task_complete')->value;
    $activity_title = $nodedata->get('field_activity_title')->value;
    $evaluation = $nodedata->get('field_evaluation')->value;
    $type = (isset($date)) ? 'task' : 'goal';
    $is_manager = $nodedata->get('field_is_manager_task')->value;

    $subgoals = array();
    foreach ($subnodes as $tid) {
      $goal_id = $tid['target_id'];
      $subgoals[$goal_id] = self::getGoal($goal_id, $progression_target);
    }
    $result = array(
      'id' => $nodeid,
      'title' => $nodetitle,
      'type' => $type,
      'completed' => $is_completed,
      'date' => $date,
      'rates' => null,
      'activity_title' => $activity_title,
      'evaluation' => $evaluation,
      'subgoals' => $subgoals,
      'is_manager' => $is_manager
    );
    if (is_object($progression_target)) {
      $result['rates'] = bc_2movepeople_rate_progression_get_rates($progression_target->id(), $nodeid);
    }

    return $result;
  }

  /*
   * return progression targets for user
   *
   * @params
   * $user_id - user uuid
   *
   * @return array
   *
   */

  public static function getProgressionTargets($user_id, $progression_type = 'progressions') {
//    $query = \Drupal::database()->select('node', 'n')
//      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
//      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    // select all progression targets

    $progression_types = [
      'progressions' => [
        'progression',
        'progression_feedback'
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
   * get average target rate
   *
   * @params
   * $target_id - progression target id
   *
   * @return float
   *
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

  /*
   * prepare progression data by entity id
   *
   * @params
   * $entity_ids - progresson ids
   *
   * @return array
   *
   */

  public static function getProgressionsTable($entity_ids) {
    $dates = array();
    $table = array();

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
    $header = array_merge(array(t('Categories')), $dates);

    if ($dates) {
      foreach ($entity_ids as $key => $target_id) {
        $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($target_id);
        $title = $nodedata->get('title')->value;
        $table[$key] = array_fill(1, count($dates), 0);
        $avg_rates = self::getTargetAveragePoints($target_id);
        foreach ($avg_rates as $row) {
          $table[$key][array_search($row->dates, $header)] = round((float) $row->avg_rates, 2);
        }
        $table[$key] = array_merge(array($title), $table[$key]);
      }
    }

    return array('header' => $header, 'data' => $table);
  }

  /**
   * return progression targets for user
   *
   * @params
   * $target_id - progression target nid
   *
   * @return array
   *
   */
  public static function getMilestoneTable($entity_ids) {
    $table = array();
    $header = array_merge(array(t('Target Milestones'), t('Priority'), t('Status')));

    foreach ($entity_ids as $key => $target_id) {
      $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($target_id);
      $title = $nodedata->get('title')->value;

      // $priority = $nodedata->get('field_priority')->value;

      $priority_edit_form = new MilestonePriorityEditForm($nodedata);
      $priority = \Drupal::formBuilder()->getForm($priority_edit_form);

      $status_edit_form = new MilestoneStatusEditForm($nodedata);
      $status = \Drupal::formBuilder()->getForm($status_edit_form);

//      $status_obj = $nodedata->get('field_progression_status');
//      $status = $status_obj->getSettings()['allowed_values'][$status_obj->value];

      $table[$key] = array($title, $priority, $status);
    }
    return array('header' => $header,
      'data' => $table);
  }

  /**
   * return progression targets for user
   *
   * @params
   * $target_id - progression target nid
   *
   * @return array
   *
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

      foreach ($goal_ids as $tid) {
        $goal_id = $tid['target_id'];
        $goal = self::getGoal($goal_id);
        if ($goal['completed'] || $goal['is_manager']) {
          continue;
        }

        list($year, $month, $day) = explode('-', $goal['date']);
        $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

        $is_remind = self::isRemindSession($goal['date']);
        $goal['is_remind'] = $is_remind;

        $tasks[$timestamp] = $goal;
        $second++;
      }
    }
    ksort($tasks, SORT_NUMERIC);

    if (empty($tasks)) {
      $title = t('Hi %name you have no scheduled tasks.', array('%name' => $user_name));
    }
    else {
      $title = t('Hi %name here are your tasks', array('%name' => $user_name));
    }

    $build = array(
      "#theme" => 'bc_2movepeople_dashboard_user_tasks_overview',
      "#title" => $title,
      "#tasks" => $tasks,
      "#user" => $user->id(),
    );

    return $build;
  }

  /**
   * get progression goals list (key - goal id, value - goal title)
   *
   * @params
   * $progression_node - progression node object
   *
   * @return array
   *
   */
  static function getProgressionGoalsList($progression_node) {

    $goal_ids = $progression_node->get('field_goal_ids')->getValue();

    $result = [];
    foreach ($goal_ids as $tid) {
      $goal_node = \Drupal::entityTypeManager()->getStorage('node')->load($tid['target_id']);
      $result[$goal_node->id()] = $goal_node->get('title')->value;
    }

    return $result;
  }

  /**
   * compare current date and task due date
   *
   * @params
   * $date - task due date
   *
   * @return integer flag (0 - false, 1 - is remind true, 2 - is expired true)
   *
   */
  public static function isRemindSession($date) {

    $is_remind = 0;
    $timezone = drupal_get_user_timezone();

    $config = \Drupal::config('bc_2movepeople_dashboard.AdminSettings');
    $reminder_days = $config->get('task_reminder_due_date');

    $current_date = new \DateTime('now', new \DateTimezone($timezone));
    $current_unixtimestamp = $current_date->getTimestamp();

    $due_date = new \DateTime($date, new \DateTimezone($timezone));
    $due_date_unixtimestamp = $due_date->getTimestamp();

    //$reminder_date_unixtimestamp = $reminder_days * 24 * 60 * 60;
    //$current_date->setTimestamp($current_unixtimestamp - $reminder_date_unixtimestamp);

    if ($due_date_unixtimestamp > $current_unixtimestamp) {
      $diff_date = $current_date->diff($due_date);
      $is_remind = $diff_date->d < $reminder_days ? 1 : 0;
    }
    else { // due date expire
      $is_remind = 2; // date expired
    }

    return $is_remind;
  }

  /**
   * check reminder time for current user
   *
   * @params
   *
   * @return boolean
   *
   */
  public static function checkUserSessionReminder() {
    //  $session = new \Symfony\Component\HttpFoundation\Session\Session();
    //  $session->start();

    $is_remind = FALSE;
    $current_date = new \DateTime('now', new \DateTimezone(drupal_get_user_timezone()));
    $current_unixtimestamp = $current_date->getTimestamp();


    $tempstore = \Drupal::service('user.private_tempstore')->get('bc_2movepeople_dashboard');

    $offset = 86400; // 86400 = 24h
    $offset = 30; // for test
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
   * set reminder message (drupal alert) for user tasks
   *
   * @params
   * $user_id - user id
   *
   * @return nothing
   *
   */
  public static function setUserTasksReminder($user_id) {

    $entity_ids = self::getProgressionTargets($user_id, 'target_milestone');
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);

    foreach ($nodes as $progrdata) {
      $goal_ids = $progrdata->get('field_goal_ids')->getValue();

      foreach ($goal_ids as $tid) {

        $goal = self::getGoal($tid['target_id'], $progrdata);
        $is_remind = self::isRemindSession($goal['date']);
        if (!$goal['is_manager'] && !$goal['completed'] && $is_remind) {
          drupal_set_message($goal['title'] . ' - ' . ($is_remind == 2 ? t('due date expired') : t('due to expire')) . ': ' . $goal['date']);
        }
      }
    }
  }

  /**
   * check access permission for current user
   * function implements in routing.yml
   *
   * @params
   * $account - current logged user object
   * $user - user object from routing
   *
   * @return access object - forbidden/allowed
   *
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
   * return connected users for admin/manager
   *
   * @params
   * $user_id - admin/manager user id
   *
   * @return array
   *
   */
  public static function getManagerIds($user_id) {
    $query = \Drupal::entityQuery('user');
    $query->condition('status', 1);
    $query->condition('field_connected_users', $user_id);
    return $query->execute();
  }

  /**
   * Simply send mail function
   *
   * @param array $message with keys
   * - to
   * - from
   * - body
   * - sender
   * - subject
   * @return BOOLEAN
   */
  public static function sendMail($message) {
    $send_mail = new \Drupal\Core\Mail\Plugin\Mail\PhpMail();
    $message['headers'] = array(
      'content-type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
      'MIME-Version' => '1.0',
      'reply-to' => $message['from'],
      'from' => $message['sender'] . ' <' . $message['from'] . '>'
    );
    return $send_mail->mail($message);
  }

  /**
   * Control buttons block definition.
   *
   * @param array $links with keys
   * - url
   * - title
   * - ajax
   * - sender
   * - subject
   *
   * @return array
   */
  public static function getControlButtons(array $links, array $attributes = []) {
    $build = [
      '#type' => 'container',
      '#attributes' => array_merge_recursive($attributes, ['class' => ['controll-buttons']])
    ];

    foreach ($links as $key => $link) {
      $build[$key] = array_merge_recursive($link, [
        '#type' => 'link',
        '#attributes' => [
          'class' => ['btn', 'btn-default'],
        ],
      ]);
    }

    return $build;
  }

}
