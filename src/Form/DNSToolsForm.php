<?php

namespace Drupal\dns_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    $instance = new static(
      $container->get('current_user')
    );
    return $instance;
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
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    $form['dns_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('DNS Field'),
      '#ajax' => [
        'callback' => '::runCommandAjax',
        'event' => 'change',
        'wrapper' => 'dns-result',
      ],
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
   * AJAX callback handler.
   */
  public function runCommandAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get the domain name entered by the user
    $domain = $form_state->getValue('dns_field');

    // Run the dig command and process the output
    $output = $this->runDigCommand($domain);

    // Update the result field with dynamic content
    $response->addCommand(new HtmlCommand('#dns-result', $output));

    return $response;
  }

  /**
   * Executes the dig command and returns the result as HTML.
   */
  private function runDigCommand($domain) {
    // Escape the input domain to prevent command injection
    $escaped_domain = escapeshellarg($domain);

    // Extract the TLD from the domain
    $tld = substr(strrchr($domain, '.'), 1);

    // Get the nameserver for the TLD
    $tld_nameserver = shell_exec("dig .$tld NS +short | head -1");
    $tld_nameserver = $tld_nameserver !== null ? trim($tld_nameserver) : '';

    $parent_server = '';
    if (!empty($tld_nameserver)) {
      // Get the parent server (authoritative nameservers) for the domain
      $parent_server = shell_exec("dig @$tld_nameserver $escaped_domain NS +noall +auth");
      $parent_server = $parent_server !== null ? trim($parent_server) : '';
    }

    // Run the dig command for each record type
    $records = [
      'A' => shell_exec("dig +short A $escaped_domain"),
      'AAAA' => shell_exec("dig +short AAAA $escaped_domain"),
      'MX' => shell_exec("dig +short MX $escaped_domain"),
      'NS' => shell_exec("dig +short NS $escaped_domain"),
      'SOA' => shell_exec("dig +short SOA $escaped_domain"),
      'CNAME' => shell_exec("dig +short CNAME $escaped_domain"),
      'TXT' => shell_exec("dig +short TXT $escaped_domain"),
      'DS' => shell_exec("dig +short DS $escaped_domain"),
      'DNSKEY' => shell_exec("dig +short DNSKEY $escaped_domain"),
    ];

    // Get current date and time in UTC
    $currentDateTime = gmdate('Y-m-d H:i:s');

    // Get current user's login
    $currentUserLogin = $this->currentUser->getAccountName();

    // Format the results into a table
    $table = '<table>';
    $table .= '<thead><tr><th>Category</th><th>Status</th><th>Test name</th><th>Information</th></tr></thead>';
    $table .= '<tbody>';

    // Adding rows for the parent server and its records
    $table .= $this->addRecordRow('Parent', 'Domain name servers', $parent_server, true);
    $table .= $this->addRecordRow('Parent', 'Name servers A records', $records['A']);
    $table .= $this->addRecordRow('Parent', 'Name servers AAAA records', $records['AAAA']);

    // Adding rows for additional DNS record types
    $table .= $this->addRecordRow('Name servers', 'NS records from your nameservers', $records['NS'], true);
    $table .= $this->addRecordRow('Name servers', 'DNS servers responded', "All nameservers, listed at the parent server, responded.", false, true);
    $table .= $this->addRecordRow('Name servers', 'Mismatched NS records', "All nameservers returned by the parent server are the same as the ones reported by your nameservers.", false, true);
    $table .= $this->addRecordRow('Name servers', 'Recursive Queries', "The nameservers reported by the parent server do not allow recursive queries.", false, true);
    $table .= $this->addRecordRow('Name servers', 'Multiple name servers', "You have at least two unique name servers.", false, true);
    $table .= $this->addRecordRow('Name servers', 'Multiple subnets', "Your name servers are hosted on different subnets.", false, true);
    $table .= $this->addRecordRow('Name servers', 'Public IPs for name servers', "IP addresses of your name servers are public.", false, true);
    $table .= $this->addRecordRow('Name servers', 'Name servers respond by TCP', "All your name servers respond to DNS queries over TCP.", false, true);

    // Adding rows for SOA records
    $table .= $this->addSoaRecordRows($records['SOA']);

    $table .= '</tbody></table>';

    return $table;
  }

  /**
   * Adds a row to the table with the given category, test name, and record.
   */
  private function addRecordRow($category, $testName, $record, $infoIcon = false, $success = false) {
    $statusIcon = $infoIcon ? '<span></span>' : ($success ? '<span></span>' : (!empty($record) ? '✔' : '✘'));
    $recordFormatted = $this->formatRecord($record);

    return "<tr>
              <td>{$category}</td>
              <td>{$statusIcon}</td>
              <td>{$testName}</td>
              <td><div><span>{$recordFormatted}</span></div></td>
            </tr>";
  }

  /**
   * Adds rows for the SOA records.
   */
  private function addSoaRecordRows($soaRecord) {
    if (empty($soaRecord)) {
      return $this->addRecordRow('SOA', 'SOA records', 'No SOA records found.');
    }

    // Split the SOA record into its components
    $soaParts = preg_split('/\s+/', trim($soaRecord));
    if (count($soaParts) < 7) {
      return $this->addRecordRow('SOA', 'SOA records', 'Invalid SOA record format.');
    }

    $primaryNs = $soaParts[0];
    $adminEmail = $soaParts[1];
    $serial = $soaParts[2];
    $refresh = $soaParts[3];
    $retry = $soaParts[4];
    $expire = $soaParts[5];
    $defaultTtl = $soaParts[6];

    $soaRows = '';
    $soaRows .= $this->addRecordRow('SOA', 'SOA record', "Primary NS: <strong>{$primaryNs}</strong><br>DNS admin e-mail: <strong>{$adminEmail}</strong><br>Serial: <strong>{$serial}</strong><br>Refresh rate: <strong>{$refresh}</strong> (1 hours)<br>Retry rate: <strong>{$retry}</strong> (30 minutes)<br>Expire time: <strong>{$expire}</strong> (2 weeks)<br>Default TTL: <strong>{$defaultTtl}</strong> (1 days)", true);
    $soaRows .= $this->addRecordRow('SOA', 'Primary server', $primaryNs, false, true);
    $soaRows .= $this->addRecordRow('SOA', 'Refresh time', "SOA refresh interval {$refresh} (1 hours) is okay.", false, true);
    $soaRows .= $this->addRecordRow('SOA', 'Retry time', "SOA retry time {$retry} (30 minutes) is okay.", false, true);
    $soaRows .= $this->addRecordRow('SOA', 'Expire time', "SOA expire time {$expire} (2 weeks) is okay.", false, true);
    $soaRows .= $this->addRecordRow('SOA', 'Default TTL', "SOA Default TTL is used to inform resolvers for the minimum time to keep their cache, but this value is overwritten by the TTL of each record.<br><br>Default TTL {$defaultTtl} (1 days) is okay.", false, true);

    return $soaRows;
  }

  /**
   * Formats the record output.
   */
  private function formatRecord($record) {
    return !empty($record) ? nl2br($record) : 'No records found.';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Form submission logic here.
  }
}
