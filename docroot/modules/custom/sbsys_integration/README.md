SBSYS Integration module
-------------------------

Basic implementation of service that handling xml generation for SBSYS system.

Service configuration is configurable and extendable with `XmlDataProvider` plugins. See `src/Plugin/XmlDataProvider`.

### XML generate plugin system usage examle

```
  /**
   * Renders SBSYS XML file.
   */
  public function sbsys_xml($context) {
    $response = new Response();
    $xml = \Drupal::service('sbsys_integration.xml_handler')->generate(['key' => $context]);
    $response->setContent($xml);
    $response->headers->set('Content-Type', 'text/xml');
    return $response;
  }

```
