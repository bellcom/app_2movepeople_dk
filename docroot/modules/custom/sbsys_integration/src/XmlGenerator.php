<?php

namespace Drupal\sbsys_integration;

use XMLWriter;

/**
 * Class generates xml string from array.
 */
class XmlGenerator {
  /** @var XMLWriter $xml */
  protected $xml;
  
  /** @var string $ver */
  protected $ver;

  /** @var string $charset */
  protected $charset;

  /**
   * Class constructor.
   *
   * @param string $ver
   *  Xml version string.
   * @param string $charset
   *  Xml charset.
   */
  function __construct($ver = '1.0', $charset = 'UTF-8') {
    $this->ver     = $ver;
    $this->charset = $charset;
  }

  /**
   * Main process method.
   *
   * @param string $root
   *   Root tag name.
   * @param array|object $data
   *   Data to convert.
   * @return string xml
   *   Xml string.
   */
  function generate($root, $data = []) {
    $this->xml = new XmlWriter();
    $this->xml->openMemory();
    $this->xml->startDocument($this->ver, $this->charset);
    $this->xml->startElement($root);
    $this->xml->setIndent(TRUE);
    $this->xml->setIndentString('    ');
    $this->write($this->xml, $data);
    $this->xml->endElement();
    $this->xml->endDocument();
    $xml = $this->xml->outputMemory(TRUE);
    $this->xml->flush();
    return $xml;
  }

  /**
   * Write element method.
   *
   * @param XMLWriter $xml
   *   Xml writer instance.
   * @param array|object $data
   *   Data to append to current writer.
   */
  protected function write(XMLWriter $xml, $data) {
    foreach ($data as $key => $value) {
      if (is_integer($key) && (is_array($value) || is_object($value))) {
        $xml->startElement('item');
        $this->write($xml, $value);
        $xml->endElement();
      }
      else if (is_integer($key)) {
        $xml->writeElement("item$key", $value);
      }
      else if (is_array($value) || is_object($value)) {
        $xml->startElement($key);
        $this->write($xml, $value);
        $xml->endElement();
      }
      else {
        $xml->writeElement($key, $value);
      }
    }
  }

}
