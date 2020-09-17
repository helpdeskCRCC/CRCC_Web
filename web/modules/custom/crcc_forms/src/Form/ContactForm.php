<?php

namespace Drupal\crcc_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crcc_forms\CrccFormHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContactForm.
 */
class ContactForm extends FormBase {

  /**
   * @var \Drupal\crcc_forms\CrccFormHelper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crcc_contact_form';
  }

  /**
   * ContactForm constructor.
   *
   * @param \Drupal\crcc_forms\CrccFormHelper $helper
   *   crcc_forms.crcc_form_helper service.
   */
  public function __construct(
    CrccFormHelper $helper
  ) {
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('crcc_forms.crcc_form_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['contact_name_first'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['contact_name_last'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['contact_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address (email@email.com)'),
      '#size' => 20,
      '#required' => TRUE,
    ];

    $form['contact_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['contact_comments'] = [
      '#title' => $this->t('Comments'),
      '#type' => 'textarea',
      '#required' => TRUE,
    ];

    $form['note_acknowledge'] = [
      '#type' => 'select',
      '#title' => $this->t('I consent to the use of my personal information by the Civilian Review and Complaints Commission for the purpose of responding to my inquiry.'),
      '#required' => TRUE,
      '#options' => [
        'Y' => $this->t('Yes'),
        'N' => $this->t('No'),
      ],
    ];


    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit & Print'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary'],
      ]
    ];

    $form['clear'] = [
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => [
        'class' => ['btn', 'btn-default'],
        'type' => 'reset',
        'value' => $this->t('Clear form'),
      ],
    ];

    // Add "(Required)" suffix to all required fields.
    $this->helper->addRequired($form);

    // Add "autocomplete=off" attribute to all select elements.
    $this->helper->addSelectAutocompleteOff($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = 'http://cms-ncr-001:51510/submit/add';

    $form_state_values = $form_state->cleanValues()->getValues();

    // Attach legacy hidden fields values.
    $form_state_values += $this->getLegacyHiddenFieldsValues();

    ksort($form_state_values);

    // Run curl how it was done in D7.
    $ch = $this->helper->curlInit($url, $form_state_values);
    $result = curl_exec($ch);
    curl_close($ch);

    if ($result == 'A server exception has been caught: One or more errors occurred.' or $result == NULL) {
      $this->logger('crcc_forms')->error($result);

      // TODO: replace with the meaningful error message.
      $message = $this->t('Spam detected!');

      $this->messenger()->addError($message);
    }
    else {
      $message = $this->t('Thank you for contacting the Commission. Your reference number is <strong>@result</strong>', [
        '@result' => $result,
      ]);
      $this->messenger()->addMessage($message);
    }

  }

  /**
   * Gets legacy hidden fields values.
   *
   * @return array
   *   Hidden fields values to be added to the $form_state values.
   */
  function getLegacyHiddenFieldsValues() {
    $legacy_hidden_values = $this->helper->getCommonLegacyHiddenFieldsValues();
    return array_merge($legacy_hidden_values, $this->getUniqueLegacyHiddenFields());
  }

  /**
   * Gets legacy hidden fields values for this particular form.
   *
   * @return array
   *   Hidden fields values to be added to the $form_state values.
   */
  protected function getUniqueLegacyHiddenFields() {
    return [
      'form_case_taken_by' => 'CRCC-CCETP',
      'form_assign_to' => 'CCS-CSI AA-AA',
      'name_here_goes' => '',
      'wdtEnvoy_RouteId' => 'contact',
    ];
  }

}
