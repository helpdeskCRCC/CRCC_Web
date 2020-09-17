<?php

namespace Drupal\crcc_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crcc_forms\CrccFormHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ComplaintForm.
 */
class ComplaintForm extends FormBase {

  /**
   * @var \Drupal\crcc_forms\CrccFormHelper
   */
  protected $helper;

  /**
   * ComplaintForm constructor.
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
    return 'crcc_complaint_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Attach complaint form shared fields.
    $form += $this->helper->getComplaintFormSharedFields();

    $form['contact_for_messages_questions_filenbr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('If you were given a file number by the RCMP with respect to the incident, please provide it (if known)'),
      '#size' => 20,
    ];

    $form['contact_for_messages_organization'] = [
      '#type' => 'textarea',
    ];

    $form['witness_added_note'] = [
      '#title' => $this->t('Is there anything else you would like to add?'),
      '#type' => 'textarea',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit complaint'),
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
   * Gets Date element attributes.
   *
   * @return array
   *   Date element attributes.
   */
  protected function getDateAttributes() {
    // TODO: replace with meaningful values.
    return [
      'type' => 'date',
      'min' => '1900-01-01',
      'max' => '2019-12-31',
    ];
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
      $form_state->setRedirect('crcc_forms.successful_submission_controller_thank_you', [], [
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
  function getUniqueLegacyHiddenFields() {
    return [
      'form_assign_to' => 'NIO-BNRP IO-ARP',
      'form_case_taken_by' => 'CRCC-CCETP',
      'form_summary' => 'Online complaint form.',
      'wdtEnvoy_RouteId' => 'complaint',
    ];
  }

}
