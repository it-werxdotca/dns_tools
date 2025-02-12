<?php

namespace Drupal\dns_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Ajax\AjaxResponse;

class DnsToolsController extends ControllerBase {

  /**
   * Implements hook_preprocess_HOOK() for user templates.
   */
  function dns_tools_preprocess_user_profile(&$variables) {
    DNSToolsUserProfilePreprocess::preprocessUserProfile($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  function dns_tools_preprocess_block(&$variables) {
    $logger = \Drupal::logger('dns_tools');
    $logger->info('dns_tools_preprocess_block called for plugin_id: @plugin_id', ['@plugin_id' => $variables['plugin_id']]);

    if ($variables['plugin_id'] == 'local_tasks_block:primary') {
      $logger->info('Preprocessing primary local tasks block.');
      DNSToolsUserProfilePreprocess::addDnsToolsLink($variables);
    }
  }

  public function autocomplete() {
    $matches = [
      '-4' => 'IPv4',
      '-6' => 'IPv6',
      '-a' => 'All',
      '-t' => 'Type',
    ];

    return new JsonResponse($matches);
  }

  // Run form with ajax.
  public function runCommandAjax($uid) {
    $form = \Drupal::formBuilder()->getForm('Drupal\dns_tools\Form\DNSToolsForm');
    $response = new AjaxResponse();
    // Update the response with the necessary data
    return $response;
  }

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a DnsToolsController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
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
