<?php

namespace Drupal\dupman_agent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\dupman_agent\Dupman;

/**
 * Main Controller.
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
 * @package Drupal\dupman_agent\Controller
 */
class DupmanController extends ControllerBase {

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Dupman service.
   *
   * @var \Drupal\dupman_agent\Dupman
   */
  protected $dupman;

  /**
   * Construct a new DupmanController object.
   *
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\dupman_agent\Dupman $dupman
   *   The Dupman service.
   */
  public function __construct(
    FloodInterface $flood,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    Dupman $dupman
  ) {
    $this->flood = $flood;
    $this->logger = $logger_factory->get('dupman');
    $this->requestStack = $request_stack;
    $this->dupman = $dupman;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood'),
      $container->get('logger.factory'),
      $container->get('request_stack'),
      $container->get('dupman')
    );
  }

  /**
   * Get website status.
   */
  public function status(): JsonResponse {
    $request = $this->requestStack->getCurrentRequest();
    $token = $request->headers->get('X-Dupman-Token', "");

    if (!$this->flood->isAllowed('dupman_agent_check_attempt', 10)) {
      $this->logger->warning('Flood prevention has been triggered for IP @ip.', ['@ip' => $request->getClientIp()]);

      throw new AccessDeniedHttpException("Access Denied");
    }

    if (!$this->dupman->checkToken($token)) {
      $this->logger->warning('Invalid access token.');
      $this->flood->register('dupman_agent_check_attempt');

      throw new AccessDeniedHttpException("Access Denied");
    }

    $this->flood->clear('dupman_agent_check_attempt');

    return new JsonResponse($this->dupman->getStatus());
  }

}
