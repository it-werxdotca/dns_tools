<?php

namespace Drupal\dns_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class DnsToolsController.
 */
class DnsToolsController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a DnsToolsController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(FormBuilderInterface $form_builder, LoggerChannelFactoryInterface $logger_factory) {
    $this->formBuilder = $form_builder;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('logger.factory')
    );
  }

  /**
   * Implements hook_preprocess_HOOK() for user templates.
   */
  public function dns_tools_preprocess_user_profile(&$variables) {
    DNSToolsUserProfilePreprocess::preprocessUserProfile($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  public function dns_tools_preprocess_block(&$variables) {
    $logger = $this->loggerFactory->get('dns_tools');
    $logger->info('dns_tools_preprocess_block called for plugin_id: @plugin_id', ['@plugin_id' => $variables['plugin_id']]);

    if ($variables['plugin_id'] == 'local_tasks_block:primary') {
      $logger->info('Preprocessing primary local tasks block.');
      DNSToolsUserProfilePreprocess::addDnsToolsLink($variables);
    }
  }

  /**
   * Provides autocomplete suggestions.
   */
  public function autocomplete() {
    $matches = [
      '-4' => 'IPv4',
      '-6' => 'IPv6',
      '-a' => 'All',
      '-t' => 'Type',
    ];

    return new JsonResponse($matches);
  }

  /**
   * Implements hook_theme().
   */
  public function dns_tools_theme() {
    return [
      'dns_tools_results' => [
        'variables' => [
          'records' => [],
          'validation_results' => [],
          'parent_server' => '',
        ],
        'template' => 'dns_tools_results',
      ],
    ];
  }

  /**
   * Run form with ajax.
   */
  public function runCommandAjax($uid) {
    $form = $this->formBuilder->getForm('Drupal\dns_tools\Form\DNSToolsForm');
    $response = new AjaxResponse();
    // Update the response with the necessary data.
    return $response;
  }

  /**
   * Displays the DNS Tools page for a user.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return array
   *   A render array representing the DNS Tools form.
   */
  public function userDnsTools(User $user) {
    // Return the DNS Tools form.
    return $this->formBuilder->getForm('Drupal\dns_tools\Form\DNSToolsForm', $user);
  }

}
