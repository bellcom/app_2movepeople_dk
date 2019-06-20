<?php

namespace Drupal\sbsys_integration\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SBSYS XML Data Provider plugin annotation object.
 *
 * @see \Drupal\sbsys_integration\Plugin\XmlDataProviderManager
 * @see plugin_api
 *
 * @Annotation
 */
class XmlDataProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
