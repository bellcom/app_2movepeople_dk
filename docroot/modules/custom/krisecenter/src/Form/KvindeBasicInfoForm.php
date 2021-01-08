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

    $general_children_counter = empty($form_state->get('general_children_counter')) ? count($basicInfo['general_children']) : $form_state->get('general_children_counter');
    if (empty($general_children_counter)) {
      $general_children_counter = 1;
    }

    if (empty($form_state->get('general_children_counter'))) {
      $form_state->set('general_children_counter', $general_children_counter);
    }

    $form['general_children'] = [
      '#type' => 'details',
      '#title' => $this->t('Stamoplysninger Børn'),
      '#open' => TRUE,
      '#prefix' => '<div id="general-children-wrapper">',
      '#suffix' => '</div>',
    ];
    for ($i = 0; $i < $general_children_counter; $i++) {
      $child = empty($basicInfo['general_children'][$i]) ? [
        'name' => '',
        'age' => '',
        'cpr' => '',
      ] : $basicInfo['general_children'][$i];
      $form['general_children'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Barn @i', ['@i' => $i + 1]),
        '#open' => TRUE,
        'name' => [
          '#type' => 'textfield',
          '#title' => $this->t('Navn'),
          '#default_value' => $child['name']
        ],
        'age' => [
          '#type' => 'textfield',
          '#title' => $this->t('Alder'),
          '#default_value' => $child['age']
        ],
        'cpr' => [
          '#type' => 'textfield',
          '#title' => $this->t('Cpr.nr.'),
          '#default_value' => $child['cpr']
        ],
        'remove' => [
          '#value' => t('Fjern linje'),
          '#name' => 'remove-' . $i,
          '#child_index' => $i,
          '#ajax' => [
            'wrapper' => 'general-children-wrapper',
            'callback' => '::ajaxGeneralChildrenCallback',
            'event' => 'click',
          ],
          '#submit' => ['::submitRemoveChild'],
          '#type' => 'submit',
          '#prefix' => '<div class="remove-element form-group">',
          '#suffix' => '</div>',
        ]
      ];
    }
    $form['general_children']['add-more'] = [
      '#value' => t('Tilføj et linje mere'),
      '#name' => 'add more',
      '#ajax' => [
        'wrapper' => 'general-children-wrapper',
        'callback' => '::ajaxGeneralChildrenCallback',
        'event' => 'click',
      ],
      '#submit' => ['::submitAddMoreChild'],
      '#type' => 'submit',
      '#prefix' => '<div class="add-more-elements form-group">',
      '#suffix' => '</div>',
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

  /**
   * Ajax bullet point update function.
   *
   * @param array $form
   *   Form API form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form API form.
   *
   * @return array
   *   Form array.
   */
  public function ajaxGeneralChildrenCallback(array $form, FormStateInterface $form_state) {
    return $form['general_children'];
  }

  public function submitAddMoreChild(array &$form, FormStateInterface $form_state) {
    $form_state->set('general_children_counter', $form_state->get('general_children_counter') + 1);
    $form_state->setRebuild();
  }

  public function submitRemoveChild(array &$form, FormStateInterface $form_state) {
    // @TODO Is not stable. Need to review/fix
    $triggering_element = $form_state->getTriggeringElement();
    $child_index = $triggering_element['#child_index'];
    $general_children = $form_state->getValue('general_children');
    unset($general_children[$child_index]);
    $form_state->setValue('general_children', $general_children);

    $form_state->set('general_children_counter', $form_state->get('general_children_counter') - 1);
    $form_state->setRebuild();

  }

}
