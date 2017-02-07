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
use Drupal\bc_2movepeople_dashboard\bc_2movepeople_dashboardStorage;

/**
 * Controller for js_example pages.
 *
 * @ingroup js_example
 */
class MovepeopleDashboardController extends ControllerBase {

  protected $database;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  public function __construct(Connection $database) {
    $this->database = $database;
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
  public function getJsAccordionImplementation() {
    $title = t('Klik on each section to expand or collapse the progressions:');
    // Build using our theme. This gives us content, which is not a good
    // practice,.

    $progression_targets = array();

    $query = $this->database->select('node', 'n')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    // select all progression targets
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('type', 'progression_target');
    $entity_ids = $query->execute();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
    foreach ($nodes as $progrdata) {
      $mtid = $progrdata->get('field_progression_target')->getValue();
      $progression_targets[$progrdata->id()]['title'] = $progrdata->get('title')->value;
      $progression_targets[$progrdata->id()]['id'] = $progrdata->id();
      $progression_targets[$progrdata->id()]['goals'] = array();
      foreach ($mtid as $tid) {
        $gettid = $tid['target_id'];
        $nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($gettid);
        $nodetitle = $nodedata->get('title')->value;
        $progression_targets[$progrdata->id()]['goals'][$gettid]['id'] = $gettid;
        $progression_targets[$progrdata->id()]['goals'][$gettid]['title'] = $nodetitle ;
        $progression_targets[$progrdata->id()]['goals'][$gettid]['rates'] = bc_2movepeople_rate_progression_get_rates($progrdata->id(), $gettid);
        //$progression .= '<p data-toggle="collapse" href="#collapse' . $tid['target_id'] .'">' . $nodetitle . ' ' . bc_2movepeople_rate_progression_get_rates($progrdata->id(), $gettid) . '<p id="collapse' . $tid['target_id'] .'">&nbsp;&nbsp;&nbsp;subtask </p></p>';
      }
    }

    $build = array (
      '#theme' => 'bc_2movepeople_dashboard',
       "#title" => 'Dashboard',
       "#subtitle" => $title,
       '#progression_targets' => $progression_targets
    );
    return $build;
  }
}
