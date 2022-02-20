/*
 * This file is part of the dupman/agent project.
 *
 * (c) 2022. dupman <info@dupman.cloud>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Written by Temuri Takalandze <me@abgeo.dev>, February 2022
 */

(function () {
  'use strict';

  Drupal.behaviors.dupman_agent_admin = {
    attach: function (context, settings) {
      const generate_button = document.getElementById('js-generate-button');

      generate_button.onclick = function () {
        return confirm(Drupal.t('Generating a new token will invalidate the current one!'));
      };
    }
  };
}());
