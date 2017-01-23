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
    $tab1 = $this->entryList();
    #error_log('Tab ' . print_r($tab1,true));
    $tab1 = t('hest');
      # '#theme' => 'bc_2movepeople_dashboard_accordion',
    $build['myelement'] = array(
      '#title' => $title,
      '#tab1' => $tab1,
    );
    // Add our script. It is tiny, but this demonstrates how to add it. We pass
    // our module name followed by the internal library name declared in
    // libraries yml file.
    // $build['myelement']['#attached']['library'][] = 'js_example/js_example.accordion';

    $build['myelement']['#attached']['library'][] = 'bc_2movepeople_dashboard/bc_2movepeople_dashboard.accordion';
    // Return the renderable array.

        $rows = array();

        $query = $this->database->select('node', 'n')
                 ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
                 ->extend('\Drupal\Core\Database\Query\TableSortExtender');

        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'progression_target');
        $entity_ids = $query->execute();
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
    	$progression = '<div class="demo"><h2>' . $title . '</h2>
    	<div id="accordion">';
	foreach ($nodes as $progrdata) {
		$mtid = $progrdata->get('field_progression_target')->getValue();
		$progression .= '<h3><a href="#">' . $progrdata->get('title')->value . '</a></h3>
		      <div>
		';
		foreach ($mtid as $tid) {
			$gettid = $tid['target_id'];
			$nodedata = \Drupal::entityTypeManager()->getStorage('node')->load($gettid);
			$nodetitle = $nodedata->get('title')->value;
        		$progression .= '<p>' . $nodetitle . '</p>';
        	}
		$progression .= '</div>';
    	}

    	$pgrogression .= '</div>
    		</div><!-- End demo -->';
    	$build['content'] = array(
      	'#markup' => $progression, 
    	);

    return $build;
  }

}
