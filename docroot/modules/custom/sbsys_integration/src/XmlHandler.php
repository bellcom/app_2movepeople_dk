<?php

namespace Drupal\sbsys_integration;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\sbsys_integration\Plugin\XmlDataProviderBase;
use Drupal\sbsys_integration\Plugin\XmlDataProviderInterface;

/**
 * Class XmlHandler.
 */
class XmlHandler  {

  /** @var XmlDataProviderInterface $dataProvider */
  private $dataProvider;

  /**
   * Constructs a new XmlDataProviderManager object.
   *
   * @param ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param PluginManagerInterface $plugin_manager
   *   Plugin manager object.
   *
   * @throws
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $plugin_manager) {
    $plugin_id = (string) $config_factory->get('sbsys_integration.settings')->get('xml_data_provider_plugin_id');
    if (!empty($plugin_id)) {
      $plugin_config = $config_factory->get(XmlDataProviderBase::getEditablePluginConfigName($plugin_id))->get();
      $this->dataProvider = $plugin_manager->createInstance($plugin_id, empty($plugin_config) ? [] : $plugin_config);
    }
  }

  public static function dataDefault() {
    return [
      'nemid_cpr' => '',
      'nemid_com_cvr' => '',
      'nemid_name' => '',
      'nemid_address' => '',
      'nemid_city' => '',
      'nemid_zipcode' => '',
      'os2formsId' => '',
      'kle' => '',
      'sagSkabelonId' => '',
      'bodyText' => '',
      'maa_sendes_til_dff' => 'ja',
      'titleText' => '',
    ];
  }

  /**
   * Generates the SBSYS XML, fills it with values and returns is as string.
   *
   * @param array $context
   *   An array of new context values.
   *
   * @return string
   */
  public function generate($context = []) {
    if (empty($this->dataProvider)) {
      return FALSE;
    }

    if (!empty($context)) {
      $this->dataProvider->setContextValues($context);
    }

    $data = self::dataDefault();
    foreach ($data as $key => $default_value) {
      $value = $this->dataProvider->getDataKey($key);
      if (!empty($value)) {
        $data[$key] = $value;
      }
    }

    $sbsysJournalisering = [
      'PrimaerPartCprNummer' => $data['nemid_cpr'],
      'PrimaerPartCvrNummer' => $data['nemid_com_cvr'],
      'KLe' => $data['kle'],
      'SagSkabelonId' => $data['sagSkabelonId'],
    ];
    $digitalForsendelse = [
      'Slutbruger' => [
        'CprNummer' => $data['nemid_cpr'],
        'CvrNummer' => $data['nemid_com_cvr'],
        'Navn' => $data['nemid_name'],
        'Adresse' => $data['nemid_address'],
        'Postnr' => $data['nemid_zipcode'],
        'Postdistrikt' => $data['nemid_city'],
      ],
      'Kvittering' => [
        'TitelTekst' => $data['titleText'],
        'BodyTekst' => $data['bodyText'],
      ],
      'MaaSendesTilDFF' => $data['maa_sendes_til_dff'],
    ];

    $xml_data = [
      'OS2FormsId' => $data['os2formsId'],
      'SBSYSJournalisering' => $sbsysJournalisering,
      'DigitalForsendelse' => $digitalForsendelse,
    ];

    $xml = new XmlGenerator();
    return $xml->generate('os2formsFormular', $xml_data);
  }

}
