<?php

namespace Drupal\krisecenter\Form;

use Drupal\bc_2movepeople_dashboard\Misc\Utils;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\krisecenter\Entity\KvindeInfo;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class KvindeBasicInfoForm.
 */
class KvindeBasicInfoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kvinde_basic_info_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    if (empty($user)) {
      throw new AccessDeniedHttpException();
    }
    $entity = KvindeInfo::loadByUser($user);
    $form_state->set('entity', $entity);
    $basicInfo = $entity->getBasicInfo();
    $form['#tree'] = TRUE;
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('Stamoplysninger'),
      '#collapsible' => FALSE,
      '#open' => TRUE,
    ];

    $form['general']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Navn'),
      '#weight' => '0',
      '#default_value' => Utils::getUserName($user),
    ];

    $form['general']['cpr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cpr.nr.'),
      '#default_value' => $user->get('field_social_security_number')->value,
    ];

    $form['general']['phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Telefonnummer'),
      '#default_value' => $basicInfo['general']['phone_number'],
    ];

    $form['general']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailadresse'),
      '#default_value' => $user->getEmail(),
    ];

    $form['general']['municipality'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bopælskommune'),
      '#default_value' => $basicInfo['general']['municipality'],
    ];

    $form['general']['marital_status'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Civilstand'),
      '#default_value' => $basicInfo['general']['marital_status'],
    ];

    $form['general']['country_of_origin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Oprindelsesland'),
      '#default_value' => $basicInfo['general']['country_of_origin'],
    ];

    $form['general']['need_assistance'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Behov for tolkebistand/sprog'),
      '#default_value' => $basicInfo['general']['need_assistance'],
    ];

    $form['general']['support_basis'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Forsørgelsesgrundlag'),
      '#default_value' => $basicInfo['general']['support_basis'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $form_state->get('entity');
    $values = $form_state->cleanValues()->getValues();
    $entity->setBasicInfo($values);
    $entity->save();
    \Drupal::messenger()->addMessage($this->t('Basis information gemt'));
  }

}
