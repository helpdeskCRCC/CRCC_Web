<?php

namespace Drupal\crcc_forms;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\language\ConfigurableLanguageManagerInterface;
use GuzzleHttp\ClientInterface;
use libphonenumber\PhoneNumberFormat;

/**
 * Class FormHelper.
 */
class CrccFormHelper {

  use StringTranslationTrait;

  /**
   * Drupal\language\ConfigurableLanguageManagerInterface definition.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new FormHelper object.
   */
  public function __construct(ConfigurableLanguageManagerInterface $language_manager, ClientInterface $http_client) {
    $this->languageManager = $language_manager;
    $this->httpClient = $http_client;
  }

  /**
   * Adds "(Required)" suffix to all required fields.
   *
   * @param array $form
   *   Form array.
   */
  public function addRequired(&$form) {
    foreach ($form as &$el) {
      if (!empty($el['#required']) && !empty($el['#title'])) {
        $el['#title'] = $this->t($el['#title']->getUntranslatedString() . ' (Required)');
      }
    }
  }

  /**
   * Adds "autocomplete=off" attribute to all select elements.
   *
   * @param array $form
   *   Form array.
   */
  public function addSelectAutocompleteOff(&$form) {
    foreach ($form as &$el) {
      if (isset($el['#type']) && $el['#type'] == 'select') {
        $el['#attributes']['autocomplete'] = 'off';
      }
    }
  }

  /**
   * Gets province options.
   *
   * @return array
   *   Province options.
   */
  public function getProvinceOptions() {
    return [
      'AB' => 'AB',
      'BC' => 'BC',
      'YT' => 'YK',
      'NT' => 'NT',
      'NU' => 'NU',
      'MB' => 'MB',
      'ON' => 'ON',
      'SK' => 'SK',
      'NB' => 'NB',
      'QC' => 'QC',
      'PE' => 'PE',
      'NL' => 'NL',
      'NS' => 'NS',
      'NA-SO' => 'N/A'
    ];
  }

  /**
   * Gets time select 15 mins interval options.
   *
   * @return array
   *   15 mins interval options.
   *
   * @throws \Exception
   */
  public function getTimeIntervalOptions() {
    $start = new \DateTime('today');
    $end = new \DateTime('tomorrow');

    $interval = new \DateInterval('PT15M');
    $period = new \DatePeriod($start, $interval, $end);

    $options = [];
    foreach ($period as $dt) {
      $options[$dt->format("H:i") . ' '] = $dt->format("H:i");
    }

    return $options;
  }

  /**
   * Gets Yes/No options.
   *
   * @return array
   *   Yes/No options.
   */
  public function getYesNoOptions() {
    return [
      'YES-OUI' => $this->t('Yes'),
      'NO-NON' => $this->t('No'),
    ];
  }

  /**
   * Gets shared fields for complaint (make complaint & public complaint) forms.
   *
   * @return array
   *   Array of fields.
   *
   * @throws \Exception
   */
  public function getComplaintFormSharedFields() {
    $form = [];

    $form['complainant_name_family'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Family Name'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['complainant_name_given'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Given Name'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['complainant_date_birth'] = [
      '#type' => 'date',
      '#title' => $this->t('Date of Birth'),
      // TODO: getDOBAttributes()
      '#attributes' => $this->getDateOfBirthAttributes(),
    ];

    $form['complainant_address_mailing_street_nbr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street Address or Post Office Box'),
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
      '#options' => $this->getProvinceOptions(),
      '#empty_option' => $this->t('Please Select'),
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
      '#title' => $this->t('Telephone number where you can be reached (999-999-9999)'),
      '#required' => TRUE,
      '#attributes' => [
        'data-rule-phoneus' => 'true',
      ],
      '#element_validate' => [['Drupal\telephone_validation\Render\Element\TelephoneValidation', 'validateTel']],
      '#element_validate_settings' => [
        'format' => PhoneNumberFormat::NATIONAL,
        'country' => ['CA'],
      ],
      '#size' => 20,
    ];

    $form['complainant_email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail Address (yourname@domain.com)'),
      '#size' => 20,
    ];

    $form['contact_for_messages_questions_form'] = [
      '#type' => 'select',
      '#title' => $this->t('Have you previously filed a public complaint about this incident with the CRCC or the RCMP?'),
      '#required' => TRUE,
      '#options' => $this->getYesNoOptions(),
      '#empty_option' => $this->t('Please Select'),
    ];

    $form['contact_for_messages_questions_informal'] = [
      '#type' => 'select',
      '#title' => $this->t('If yes, did you sign an agreement with the RCMP to resolve this complaint informally?'),
      '#required' => TRUE,
      '#options' => $this->getYesNoOptions(),
      '#empty_option' => $this->t('Please Select'),
    ];

    $form['complainant_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Preferred Language of Correspondence'),
      '#required' => TRUE,
      '#options' => [
        'ENGLISH-ANGLAIS' => $this->t('English'),
        'FRENCH-FRAN&#199;AIS' => $this->t('French'),
      ],
      '#empty_option' => $this->t('Please Select'),
    ];

    $form['contact_tool'] = [
      '#type' => 'select',
      '#title' => $this->t('Preferred method of communication'),
      '#required' => TRUE,
      '#options' => [
        'EMAIL-COURRIEL' => $this->t('E-Mail'),
        'TELEPHONE-TÉLÉPHONE' => $this->t('Phone'),
        'LETTER-LETTRE' => $this->t('Mail'),
      ],
      '#empty_option' => $this->t('Please Select'),
    ];

    $form['contact_for_messages_questions_involved'] = [
      '#type' => 'select',
      '#title' => $this->t('Were you the person involved in the incident being complained of?'),
      '#required' => TRUE,
      '#options' => $this->getYesNoOptions(),
      '#empty_option' => $this->t('Please Select'),
    ];

    $form['incident_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Date of Incident'),
      '#attributes' => $this->getDateAttributes(),
    ];

    $form['incident_time'] = [
      '#type' => 'select',
      '#title' => $this->t('Time of Incident (approx.)'),
      '#options' => $this->getTimeIntervalOptions(),
      '#default value' => '00:00'
    ];

    $form['incident_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location (city, town)'),
      '#size' => 20,
    ];

    $form['incident_province'] = [
      '#type' => 'select',
      '#title' => $this->t('Province'),
      '#options' => $this->getProvinceOptions(),
      '#required' => TRUE,
      '#empty_option' => $this->t('Please Select'),
    ];

    $form['circumstances'] = [
      '#title' => $this->t('Please describe the circumstances that led to your complaint as completely as possible'),
      '#type' => 'textarea',
      '#required' => TRUE,
    ];

    $form['officer_entered_note'] = [
      '#title' => $this->t('Please provide, if possible, the name , rank and detachment of the RCMP member(s) whose behavior you are complaining about.'),
      '#type' => 'textarea',
    ];

    $form['witness_entered_note'] = [
      '#title' => $this->t('Please provide the name(s) of any witness(es), if applicable. Witnesses may include RCMP members you are not complaining about.'),
      '#type' => 'textarea',
    ];

    $form['note_acknowledge'] = [
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => $this->t('By clicking this box, you are acknowledging that the information provided is complete and accurate to the best of your knowledge.'),
      '#return_value' => 'on',
    ];

    $form['contact_for_messages_organization'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Please provide the representive(s) contact information.'),
    ];

    return $form;
  }

  /**
   * Gets Date of Birth element attributes.
   *
   * @return array
   *   Date element attributes.
   */
  protected function getDateOfBirthAttributes() {
    // TODO: replace with meaningful values.
    return [
      'type' => 'date',
      'min' => '1900-01-01',
      'max' => '2019-12-31',
    ];
  }

  /**
   * Gets date attributes.
   *
   * @return array
   *   Date element attributes.
   */
  protected function getDateAttributes() {
    // TODO: insert meaningful min-max attributes.
    return $this->getDateOfBirthAttributes();
  }

  /**
   * Gets legacy hidden fields values for complaint forms.
   *
   * @return array
   *   Hidden fields values to be added to the $form_state values.
   */
  public function getComplaintLegacyHiddenFieldsValues() {
    return [
      'witness_entered_by' => 'ccmEnvoy',
      'witness_entered_date' => '17-May-2014',
      'witness_entered_userid' => 'ccmEnvoy',
      'witness_entered_type' => 'WITNESS-TÉMOIN',
      'officer_entered_by' => 'ccmEnvoy',
      'officer_entered_date' => '17-May-2014',
      'officer_entered_userid' => 'ccmEnvoy',
      'officer_entered_type' => 'RCMP-GRC',
      'witness_added_type' => 'GENERAL-GÉNÉRAL',
      'advocate_entered_type' => 'ADVOCATE-DÉFENSEUR',
    ];
  }

  /**
   * Gets legacy hidden fields values for all forms.
   *
   * @return array
   *   Hidden fields values to be added to the $form_state values.
   */
  public function getCommonLegacyHiddenFieldsValues() {
    return [
      'intake_log' => '',
      'form_date' => '17-May-2014',
      'form_time' => '09:22:03',
      'form_added_by' => 'ccmEnvoy',
      'form_caller_type' => '',
      'form_intake_method' => 'ONLINE-EN LIGNE',
      'form_status' => 'PENDING-EN ATTENTE',
      'form_type' => 'INT-RÉC',
      'form_crcc_office' => 'NIO-BNRP',
      'form_activity' => 'Review new Intake - Nouvelle réception à examiner',
      'form_activity_type' => 'INT-RÉC',
      'form_assign_date' => '17-May-2014',
      'form_deadline' => '21-May-2014',
      'form_assigned_by' => 'ccmEnvoy',
      'form_modified_by' => 'ccmEnvoy',
      'form_modified_date' => '17-May-2014',
    ];
  }

  /**
   * Initiates curl resource.
   *
   * It is the way how old D7 site is sending form submissions.
   *
   * @param string $url
   *   URL where to send the request.
   * @param $form_state_values
   *   Form values.
   *
   * @return false|resource
   *   Curl resource.
   */
  public function curlInit($url, $form_state_values) {
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $form_state_values);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    return $ch;
  }

  /**
   * Sends POST request in a Drupal way.
   *
   * Not tested! Not used.
   *
   * @param string $url
   *   URL where to send the request.
   * @param $form_state_values
   *   Form values.
   *
   * @return array
   *   Status code and response.
   */
  public function sendRequest($url, $form_state_values) {
    $response = $this->httpClient->post($url, [
      'form_params' => $form_state_values,
    ]);

    return [
      'status_code' => $response->getStatusCode(),
      'response'
    ];
  }

}
