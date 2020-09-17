<?php

namespace Drupal\crcc_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crcc_forms\CrccFormHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PublicComplaintForm.
 */
class PublicComplaintForm extends FormBase {

  /**
   * @var \Drupal\crcc_forms\CrccFormHelper
   */
  protected $helper;

  /**
   * PublicComplaintForm constructor.
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
  public function getFormId() {
    return 'crcc_public_complaint_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Attach complaint form shared fields.
    $form += $this->helper->getComplaintFormSharedFields();

    // Make alterations for this particular form.
    $form['complainant_email']['#title'] = $this->t('E-mail Address');
    $form['complainant_phone_home']['#title'] = $this->t('Telephone number (999-999-9999)');
    $form['complainant_language']['#title'] = $this->t('What is the complainant’s preferred language of correspondence');
    $form['contact_tool']['#title'] = $this->t('How does the complainant want to be contacted?');
    $form['contact_for_messages_questions_involved']['#title'] = $this->t('Is the complainant directly involved in the incident?');
    $form['officer_entered_note']['#title'] = $this->t('List the member(s) whose conduct the individual is complaining about. If the complainant is unsure, please write UNKNOWN for each member involved and ask the individual to provide a brief physical description.');
    $form['contact_for_messages_organization']['#title'] = $this->t('Please provide the representive(s) contact information.');
    $form['witness_entered_note']['#title'] = $this->t('Witness(es) if applicable. Witnesses may include RCMP members who are NOT the subject of the complaint. If the witness(es) and/or member(s) are unknown to the individual, ask the individual to provide a brief physical description.');
    $form['note_acknowledge']['#title'] = $this->t('By clicking this box, you the member are acknowledging that you have reviewed the information contained on this form with the complainant and that the information is true and accurate to the best of their knowledge.');
    $form['circumstances']['#title'] = $this->t('Please describe the circumstances of the complaint');

    $form['incident_country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#required' => TRUE,
      '#size' => 20,
    ];

    $form['witness_added_note'] = [
      '#title' => $this->t('RCMP Member\'s Name and HRMIS #'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 20,
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

      // Redirect to the failure page.
      $form_state->setRedirect('crcc_forms.failed_submission_controller_failed_submission');

      return TRUE;
    }
    else {
      $this->getRequest()->getSession()->set('form_values', $form_state_values);

      // Redirect to the preview and submit page.
      $form_state->setRedirect('crcc_forms.successful_submission_controller_preview_and_submit', [],
        [
        'query' => [
          'result' => $result,
        ]
      ]);
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
    $legacy_hidden_values = array_merge($legacy_hidden_values, $this->helper->getComplaintLegacyHiddenFieldsValues());
    return array_merge($legacy_hidden_values, $this->getUniqueLegacyHiddenFields());
  }

  /**
   * Gets legacy hidden fields values for this particular form.
   *
   * @return array
   *   Hidden fields values to be added to the $form_state values.
   */
  protected function getUniqueLegacyHiddenFields(){
    return [
      'form_assign_to' => 'NIO-BNRP IO-ARP',
      'form_case_taken_by' => 'RCMP-GRC',
      'form_summary' => 'Online complaint form for RCMP use only.',
      'wdtEnvoy_RouteId' => 'officer',
    ];
  }

}
