<?php

namespace Drupal\dupman_agent;

use Drupal\Core\State\StateInterface;
use Drupal\update\UpdateManagerInterface;

/**
 * Dupman Integration Service.
 *
 * This file is part of the dupman/agent project.
 *
 * (c) 2022. dupman <info@dupman.cloud>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Written by Temuri Takalandze <me@abgeo.dev>, February 2022
 *
 * @package Drupal\dupman_agent
 */
class Dupman {

  const STATE_KEY = 'dupman_agent.token';

  /**
   * The state object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Construct a new Dupman object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Generate new authentication token.
   */
  public function generateToken(): string {
    return bin2hex(random_bytes(32));
  }

  /**
   * Set new authentication token.
   */
  public function setToken(string $token) {
    $this->state->set(self::STATE_KEY, $token);
  }

  /**
   * Check authentication token.
   */
  public function checkToken(string $token): bool {
    return hash_equals($this->state->get(self::STATE_KEY, ""), $token);
  }

  /**
   * Get site status.
   */
  public function getStatus(): array {
    return [
      'updates' => $this->getUpdates(),
    ];
  }

  /**
   * Get data for installed modules.
   */
  public function getExtensions(): array {
    $extensions = [];

    if ($available = update_get_available(TRUE)) {
      module_load_include('inc', 'update', 'update.compare');
      $extensions = update_calculate_project_data($available);
    }

    return $extensions;
  }

  /**
   * Get update data.
   */
  public function getUpdates(): array {
    $extensions = $this->getExtensions();

    $updates = [];
    foreach ($extensions as $extension) {
      if ($extension['status'] != UpdateManagerInterface::CURRENT) {
        $extension_data = [
          'name' => $extension['name'],
          'title' => $extension['title'],
          'link' => $extension['link'],
          'type' => $extension['project_type'],
          'current_version' => $extension['existing_version'],
          'latest_version' => $extension['latest_version'],
          'recommended_version' => $extension['recommended'],
          'releases' => [],
          'install_type' => $extension['install_type'],
          'status' => $extension['status'],
        ];

        foreach ($extension['releases'] as $release) {
          $extension_data['releases'][] = [
            'name' => $release['name'],
            'version' => $release['version'],
            'tag' => $release['tag'],
            'status' => $release['status'],
            'link' => $release['release_link'],
            'date' => $release['date'],
            'security' => $release['security'],
            'core_compatibility' => [
              'compatibility' => $release['core_compatibility'],
              'compatible' => $release['core_compatible'],
              'message' => $release['core_compatibility_message'],
            ],
          ];
        }

        $updates[] = $extension_data;
      }
    }

    return $updates;
  }

}
