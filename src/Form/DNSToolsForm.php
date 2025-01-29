<?php

namespace Drupal\dns_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

class DNSToolsForm extends FormBase {

  public function getFormId() {
	return 'dns_tools_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
	$form['command'] = [
	  '#type' => 'select',
	  '#title' => $this->t('Command'),
	  '#options' => [
		'whois' => 'Whois',
		'nslookup' => 'NSLookup',
		'traceroute' => 'Traceroute',
		'dig' => 'Dig',
	  ],
	  '#required' => TRUE,
	];

	$form['flags'] = [
	  '#type' => 'textfield',
	  '#title' => $this->t('Flags'),
	  '#description' => $this->t('Enter command flags (e.g., -4 for IPv4).'),
	  '#autocomplete_route_name' => 'dns_tools.autocomplete',
	];

	$form['target'] = [
	  '#type' => 'textfield',
	  '#title' => $this->t('Target'),
	  '#description' => $this->t('Enter the target domain or IP address.'),
	  '#required' => TRUE,
	];

	$form['submit'] = [
	  '#type' => 'submit',
	  '#value' => $this->t('Run Command'),
	  '#attributes' => [
		'class' => ['dns-tools-submit'],
	  ],
	];

	$form['output'] = [
	  '#type' => 'container',
	  '#attributes' => ['id' => 'dns-tools-output'],
	];

	return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
	// No need for traditional submission handling since we're using AJAX.
  }

  public function runCommandAjax(array &$form, FormStateInterface $form_state) {
	$command = $form_state->getValue('command');
	$flags = $form_state->getValue('flags');
	$target = $form_state->getValue('target');

	$output = $this->runCommand($command, $flags, $target);

	$response = new JsonResponse([
	  'output' => render($output),
	]);

	return $response;
  }

  private function runCommand($command, $flags, $target) {
	$full_command = "$command $flags $target";
	$output = shell_exec($full_command);

	return [
	  '#theme' => 'dns_tools_output',
	  '#output' => $output,
	];
  }
}