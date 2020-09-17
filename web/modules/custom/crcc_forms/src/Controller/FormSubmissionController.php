<?php

namespace Drupal\crcc_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SuccessfulSubmissionController.
 */
class FormSubmissionController extends ControllerBase {

  /**
   * Thank You page callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return array
   *   Render array.
   */
  public function thankYou(Request $request) {
    return [
      '#theme' => 'crcc_successful_submission',
      '#reference_number' => $request->query->get('result'),
    ];
  }

  /**
   * Preview and submit form page callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return array
   *   Render array.
   */
  public function previewAndSubmit(Request $request) {

    $form_values = $request->getSession()->get('form_values');
    $request->getSession()->remove('form_values');

    return [
      '#theme' => 'crcc_preview_and_submit',
      '#reference_number' => $request->query->get('result'),
      '#vals' => $form_values,
    ];
  }

  /**
   * Failed Submission page callback.
   *
   * @return array
   *   Render array.
   */
  public function failedSubmission() {
    return [
      '#theme' => 'crcc_failed_submission',
    ];
  }

}
