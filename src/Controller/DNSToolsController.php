<?php

namespace Drupal\dns_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class DNSToolsController extends ControllerBase {

  public function autocomplete() {
	$matches = [
	  '-4' => 'IPv4',
	  '-6' => 'IPv6',
	  '-a' => 'All',
	  '-t' => 'Type',
	];

	return new JsonResponse($matches);
  }
}