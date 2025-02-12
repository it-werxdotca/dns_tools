<?php

namespace Drupal\dns_tools\Install;

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class DNSToolsInstall.
 *
 * This class handles placing the DNS tools link on the user profile page.
 */
class DNSToolsInstall {

  /**
   * DNSToolsInstall constructor.
   */
  public function __construct() {
    // No dependencies are needed anymore for blocks.
  }

  /**
   * Adds the DNS tools link to the user profile menu.
   */
  public function addLinkToUserProfile() {
    // URL for the user profile DNS tools page.
    $url = Url::fromRoute('dns_tools.user_profile', ['uid' => \Drupal::currentUser()->id()]);

    // Create the link.
    $link = Link::fromTextAndUrl('DNS Tools', $url);

    // Add the link to the user profile page (we will do this programmatically using preprocess).
    \Drupal::state()->set('dns_tools.user_profile_link', $link->toString());
  }
}
