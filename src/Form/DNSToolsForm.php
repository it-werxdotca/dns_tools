<?php

namespace Drupal\dns_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\user\Entity\User;

class DNSToolsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dns_tools_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    $form['dns_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DNS Field'),
      '#ajax' => [
        'callback' => '::runCommandAjax',  // This is where the method is referenced
        'event' => 'change',               // The event that triggers the AJAX
        'wrapper' => 'dns-result',         // The element to update with the AJAX response
      ],
    ];

    $form['result'] = [
      '#type' => 'markup',
      '#markup' => '',
      '#prefix' => '<div id="dns-result">',  // The wrapper for AJAX
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * AJAX callback handler.
   */
  public function runCommandAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Example: Update the result field with something dynamic.
    $response->addCommand(new HtmlCommand('#dns-result', $this->t('Updated content!')));

    // You can replace 'Updated content!' with dynamic content based on form state.
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form submission logic here.
  }
}
