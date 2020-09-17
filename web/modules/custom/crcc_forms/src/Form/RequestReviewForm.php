<?php

namespace Drupal\crcc_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crcc_forms\CrccFormHelper;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RequestReviewForm.
 */
class RequestReviewForm extends FormBase {

  /**
   * @var \Drupal\crcc_forms\CrccFormHelper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crcc_request_review_form';
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
    $form['complainant_name_given'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Given Name'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['complainant_name_family'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Family Name'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['complainant_address_mailing_street_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street Address'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['complainant_address_mailing_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['complainant_address_mailing_province'] = [
      '#type' => 'select',
      '#title' => $this->t('Province (Canada)'),
      '#required' => TRUE,
      '#options' => $this->helper->getProvinceOptions(),
    ];

    $form['complainant_address_mailing_postalcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code / ZIP Code'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['complainant_address_mailing_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['complainant_phone_home'] = [
      '#type' => 'tel',
      '#title' => $this->t('Telephone number where you can be reached'),
      '#required' => TRUE,
      '#size' => 20,
      '#attributes' => [
        'data-rule-phoneus' => 'true',
        'placeholder' => '111-222-3333',
      ],
      '#element_validate_settings' => [
        'format' => PhoneNumberFormat::NATIONAL,
        'country' => ['CA'],
      ],
    ];

    $form['complainant_email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail Address'),
      '#size' => 20,
      '#attributes' => [
        'placeholder' => 'email@email.com',
      ],
    ];

    $form['incident_filenbr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('RCMP File Number (If known)'),
      '#size' => 20,
    ];

    $form['incident_cpcnbr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Commission File Number (If known)'),
      '#size' => 20,
    ];

    $form['circumstances'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Please list the allegation(s) you would like to have reviewed and the reason(s)'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit request'),
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
      'review_entered_by' => 'ccmEnvoy',
      'review_entered_date' => '17-May-2014',
      'review_entered_userid' => 'ccmEnvoy',
      'review_entered_type' => 'REVIEW NOTE-NOTE EXAMEN',

      'form_case_taken_by' => 'CRCC-CCETP',
      'form_assign_to' => 'RI-EE PO-AP',
      'form_summary' => 'Online Request for Review form.',
      'wdtEnvoy_RouteId' => 'review',
    ];
  }

}
