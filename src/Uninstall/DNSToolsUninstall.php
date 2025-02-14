<?php

namespace Drupal\dns_tools\Uninstall;

/**
 * Class DNSToolsUninstall.
 *
 * This class handles the uninstallation process, including the removal of any changes made during installation.
 */
class DNSToolsUninstall {

  /**
   * DNSToolsUninstall constructor.
   */
  public function __construct() {
    // No dependencies needed for cleanup as there are no blocks now.
  }

  /**
   * Removes the DNS Tools link stored in the state during installation.
   */
  public function removeLinkFromUserProfile() {
    // Delete the state variable that holds the DNS tools link.
    \Drupal::state()->delete('dns_tools.user_profile_link');
  }

}
