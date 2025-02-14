<?php

namespace Drupal\dns_tools\Preprocess;

use Drupal\Core\Url;

/**
 * Class DNSToolsUserProfilePreprocess.
 *
 * Preprocess class for adding a link to DNS tools on the user profile page.
 */
class DNSToolsUserProfilePreprocess {

  /**
   * Adds the DNS tools link to the user profile tabs.
   *
   * @param array $variables
   *   The variables to preprocess.
   */
  public static function addDnsToolsLink(&$variables) {
    $logger = \Drupal::logger('dns_tools');
    $logger->info('addDnsToolsLink called.');

    // Check that the current user is viewing their own profile.
    $user = \Drupal::routeMatch()->getParameter('user');
    if ($user) {
      $logger->info('User ID: @uid', ['@uid' => $user->id()]);
    }
    else {
      $logger->info('No user parameter found in route.');
    }

    if ($user && \Drupal::currentUser()->id() == $user->id()) {
      $logger->info('Current user is viewing their own profile.');
      // Add a link to the DNS tools form.
      $url = Url::fromRoute('dns_tools.user_dns_tools', ['user' => $user->id()]);
      $link = [
        '#type' => 'link',
        '#title' => t('DNS Tools'),
        '#url' => $url,
        '#attributes' => ['class' => ['dns-tools-link']],
      ];
      // Inject into primary local tasks.
      $variables['primary_local_tasks'][] = $link;
      $logger->info('DNS Tools link added to primary local tasks.');
    }
    else {
      $logger->info('Current user is not viewing their own profile.');
    }
  }

}
