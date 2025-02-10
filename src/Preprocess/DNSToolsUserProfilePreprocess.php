<?php

namespace Drupal\dns_tools\Preprocess;

use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Preprocesses the user profile page to add the DNS Tools link.
 */
class DNSToolsUserProfilePreprocess {

  /**
   * Preprocess function to add the DNS Tools link to the user profile page.
   */
  public function preprocess(array &$variables) {
    // Check if we are on the user profile page.
    if ($variables['theme_hook_original'] == 'user_profile') {
      // Retrieve the link that was set in the install process.
      $link = \Drupal::state()->get('dns_tools.user_profile_link');

      if ($link) {
        // Add the link to the profile page content.
        $variables['content']['#markup'] .= '<div class="dns-tools-link">' . $link . '</div>';
      }
    }
  }
}
