<?php

namespace Drupal\sbsys_integration\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the SBSYS XML Data Provider plugin manager.
 */
class XmlDataProviderManager extends DefaultPluginManager {

  /**
   * Constructs a new XmlDataProviderManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/XmlDataProvider', $namespaces, $module_handler, 'Drupal\sbsys_integration\Plugin\XmlDataProviderInterface', 'Drupal\sbsys_integration\Annotation\XmlDataProvider');

    $this->alterInfo('sbsys_integration_xml_data_provider_info');
    $this->setCacheBackend($cache_backend, 'sbsys_integration_xml_data_provider_plugins');
  }

}
