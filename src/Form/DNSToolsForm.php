<?php

namespace Drupal\dns_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DNSToolsForm.
 */
class DNSToolsForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Constructs a new DNSToolsForm object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dns_tools_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?User $user = NULL) {
    $form['#attached']['library'][] = 'dns_tools/dns_tools';

    $form['resolver'] = [
      '#type' => 'select',
      '#title' => $this->t('DNS Resolver'),
      '#options' => [
        '1.1.1.1' => $this->t('Cloudflare'),
        '8.8.8.8' => $this->t('Google'),
        '9.9.9.9' => $this->t('Quad9'),
        '208.67.222.222' => $this->t('Cisco OpenDNS'),
      ],
      '#default_value' => '9.9.9.9',
    ];

    $form['dns_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DNS Field'),
      '#placeholder' => 'example.com',
      '#ajax' => [
        'callback' => '::runCommandAjax',
        'event' => 'change',
        'wrapper' => 'dns-result',
      ],
      '#element_validate' => ['::validateDomain'],
    ];

    $form['result'] = [
      '#type' => 'markup',
      '#markup' => '',
      '#prefix' => '<div id="dns-result">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * Validate the domain input.
   */
  public function validateDomain(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $domain = $form_state->getValue($element['#parents']);
    if (!preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{0,62}[a-zA-Z0-9]\.)+[a-zA-Z]{2,6}$/', $domain)) {
      $form_state->setError($element, $this->t('Please enter a valid domain name.'));
    }
  }

  /**
   * AJAX callback handler.
   */
  public function runCommandAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get the domain name entered by the user.
    $domain = $form_state->getValue('dns_field');

    // Get the selected DNS resolver.
    $resolver = $form_state->getValue('resolver');

    // Run the dig command and process the output.
    try {
      $output = $this->runDigCommand($domain, $resolver);
    } catch (\Exception $e) {
      $output = $this->t('An error occurred while processing your request. Please try again.');
    }

    // Update the result field with dynamic content.
    $response->addCommand(new HtmlCommand('#dns-result', $output));

    return $response;
  }

  /**
   * Executes the dig command and returns the result as HTML.
   */
  private function runDigCommand($domain, $resolver) {
    // Escape the input domain to prevent command injection.
    $escaped_domain = escapeshellarg($domain);

    // Extract the TLD from the domain.
    $tld = substr(strrchr($domain, '.'), 1);

    // Get the parent server for the TLD.
    $parent_server = shell_exec("dig +short $tld NS");
    $parent_server = $parent_server !== NULL ? trim($parent_server) : '';

    // Format the parent server result.
    $parent_server_formatted = !empty($parent_server) ? "<strong>$parent_server</strong>" : 'No parent server found.';

    // Run the dig command for each record type.
    $records = [
      'A' => $this->runShellCommand("dig @$resolver +short A $escaped_domain"),
      'AAAA' => $this->runShellCommand("dig @$resolver +short AAAA $escaped_domain"),
      'MX' => $this->runShellCommand("dig @$resolver +short MX $escaped_domain"),
      'NS' => $this->runShellCommand("dig @$resolver +short NS $escaped_domain"),
      'SOA' => $this->runShellCommand("dig @$resolver +short SOA $escaped_domain"),
      'CNAME' => $this->runShellCommand("dig @$resolver +short CNAME $escaped_domain"),
      'TXT' => $this->runShellCommand("dig @$resolver +short TXT $escaped_domain"),
      'DS' => $this->runShellCommand("dig @$resolver +short DS $escaped_domain"),
      'DNSKEY' => $this->runShellCommand("dig @$resolver +short DNSKEY $escaped_domain"),
      'CAA' => $this->runShellCommand("dig @$resolver +short CAA $escaped_domain"),
      'NSEC3PARAM' => $this->runShellCommand("dig @$resolver +short NSEC3PARAM $escaped_domain"),
      '_DMARC' => $this->runShellCommand("dig @$resolver +short TXT _dmarc.$escaped_domain"),
      'SPF' => $this->runShellCommand("dig @$resolver +short TXT $escaped_domain | grep 'v=spf1'"),
    ];

    // Process TXT records to handle multiple entries.
    $records['TXT'] = explode("\n", trim($records['TXT']));

    // Validate DNS records.
    $validationResults = $this->validateDnsRecords($records);

    // Render the output using a Twig template.
    return [
      '#theme' => 'dns_tools_results',
      '#records' => $records,
      '#validation_results' => $validationResults,
      '#parent_server' => $parent_server_formatted,
    ];
  }

  /**
   * Executes a shell command securely.
   */
  private function runShellCommand($command) {
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    if ($return_var !== 0) {
      throw new \RuntimeException('Shell command failed: ' . implode("\n", $output));
    }
    return implode("\n", $output);
  }

  /**
   * Validates the DNS records.
   */
  private function validateDnsRecords($records) {
    $validationResults = [];

    // Validate SPF record.
    $spfValid = !empty($records['SPF']) && strpos($records['SPF'], 'v=spf1') === 0;
    $validationResults[] = ['SPF record', $spfValid ? 'Valid' : 'Invalid', $spfValid, "Ensure it starts with 'v=spf1' and includes mechanisms to specify allowed mail servers."];

    // Validate DS record.
    $dsValid = !empty($records['DS']);
    $validationResults[] = ['DS record', $dsValid ? 'Exists' : 'Missing', $dsValid, "Ensure DS records are correctly generated and match the DNSKEY records using tools like 'dnssec-dsfromkey'."];

    // Validate DNSKEY record.
    $dnskeyValid = !empty($records['DNSKEY']);
    $validationResults[] = ['DNSKEY record', $dnskeyValid ? 'Exists' : 'Missing', $dnskeyValid, "Ensure DNSKEY records are correctly generated and published. Regularly rotate DNSSEC keys."];

    // Validate NSEC3PARAM record.
    $nsec3paramValid = !empty($records['NSEC3PARAM']);
    $validationResults[] = ['NSEC3PARAM record', $nsec3paramValid ? 'Exists' : 'Missing', $nsec3paramValid, "Ensure NSEC3PARAM records are correctly configured following best practices."];

    return $validationResults;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form submission logic here.
  }

}
