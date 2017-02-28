<?php

namespace Drupal\bc_2movepeople_clients\Controller;

use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for js_example pages.
 *
 * @ingroup js_example
 */
class MovepeopleClientsController extends ControllerBase {

  protected $database;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  
  public function getClientsImplementation() {
   $current_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
   $connected_users_ids = $current_user->get('field_connected_users')->getValue();
   $connected_users = array();
   foreach ($connected_users_ids as $key => $user_id) {     
     $uid = $user_id['target_id'];
     $user = \Drupal\user\Entity\User::load($uid);
     $connected_users[$uid] = $user->field_user_firstname->value . ' ' . $user->field_user_surname->value;
   }
   
    $build = array (
      "#theme" => "bc_2movepeople_clients_list",
      "#title" => 'Clients',
      "#users" => $connected_users
    );
  ;
    return $build;
  }
  
}
