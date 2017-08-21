<?php

namespace Drupal\bc_2movepeople\Controller;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An example controller.
 */
class QuestionMakerController extends ControllerBase {

  /**
   * {@inheritdoc}
   */

        protected $database;

        public static function create(ContainerInterface $container) {
                return new static(
                        $container->get('database')
                );
        }


        public function __construct(Connection $database) {
                $this->database = $database;
        }


        public function content() {

                $link = sprintf('<a class="%s" href="%s/node/add/goal?destination=%sbc_2movepeople/questionmaker">%s</a>'
                        , 'button button-action button--primary button--small'
                        , $GLOBALS['base_url']
                        , $GLOBALS['base_path']
                        , t('Create New Question')
                );

                $content = array();
                $content['link'] = array(
                        '#type' => 'markup',
                        '#markup' => '<p>' . $link . '</p>',
                );

                $content['qr_overview_table'] = $this->_overviewTable();
                $content['qr_overview_pager'] = array('#type' => 'pager');

                return $content;
        }

        private function _overviewTable() {

                // Table header
                $header = array(
                        array(
                                'data' => $this->t('Title'),
                                'field' => 'nfd.title',
                        ),
                        '', // "Edit" column
                        '', // "Delete" column
                );

                $rows = array();

                $query = $this->database->select('node', 'n')
                        ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
                        ->extend('\Drupal\Core\Database\Query\TableSortExtender');

                $query->join('node_field_data', 'nfd', 'nfd.nid = n.nid');
                $query->fields('nfd', array('nid', 'type', 'title'));

                $node_storage = $query->limit(20)->orderByHeader($header)->execute();
                foreach ($node_storage as $ns) {
                        $custom_url = $ns->field_custom_url_value;

                        if (UrlHelper::isValid($custom_url, true)) {
                                $custom_url = \Drupal::l($custom_url, Url::fromUri($custom_url));
                        }

                        $edit_link_url = sprintf('%s/node/%s/edit?destination=%sbc_2movepeople/questionmaker'
                                , $GLOBALS['base_url'], $ns->nid, $GLOBALS['base_path']
                        );

                        $delete_link_url = sprintf('%s/node/%s/delete?destination=%sbc_2movepeople/questionmaker'
                                , $GLOBALS['base_url'], $ns->nid, $GLOBALS['base_path']
                        );

                        $edit_link = \Drupal::l($this->t('Edit'), Url::fromUri($edit_link_url));
                        $delete_link = \Drupal::l($this->t('Delete'), Url::fromUri($delete_link_url));

                        $rows[] = array(
                                'data' => array(
                                        $ns->title,
                                        $edit_link,
                                        $delete_link,
                                )
                        );
                }


                return array(
                        '#type' => 'table',
                        '#header' => $header,
                        '#rows' => $rows,
                        '#empty' => $this->t('No data available.'),
                );

	}
}
