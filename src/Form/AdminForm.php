<?php

namespace Drupal\dupman_agent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dupman_agent\Dupman;

/**
 * Dupman Agent configuration form.
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
 * @package Drupal\dupman_agent\Form
 */
class AdminForm extends ConfigFormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Dupman Service.
   *
   * @var \Drupal\dupman_agent\Dupman
   */
  protected $dupman;

  /**
   * Construct a new AdminForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\dupman_agent\Dupman $dupman
   *   The Dupman Service.
   */
  public function __construct(
    MessengerInterface $messenger,
    Dupman $dupman
  ) {
    $this->messenger = $messenger;
    $this->dupman = $dupman;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('dupman')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dupman_agent.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dupman_agent_form';
  }

  /**
   * Ajax action for generating new Authentication token.
   */
  public function generateNewToken($form, FormStateInterface $form_state) {
    $token = $this->dupman->generateToken();
    $this->dupman->setToken($token);

    $message = $this->t(
      'A new token has been generated. Please copy this key and save it somewhere safe.<br><br>
      <strong>For security reasons, we cannot show it to you again!</strong><br><br>
      New token is: <strong>@token</strong>',
      ['@token' => $token]
    );

    $this->messenger->addMessage($message);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'dupman_agent/admin';

    $form['dupman_agent_generate_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate New Token'),
      '#attributes' => [
        'id' => 'js-generate-button',
      ],
      '#submit' => ['::generateNewToken'],
    ];

    return parent::buildForm($form, $form_state);
  }

}
