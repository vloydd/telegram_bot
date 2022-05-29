<?php

namespace Drupal\telegram_bot\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\telegram_bot\Service\TelegramBotManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Webhook Endpoint.
 */
class TelegramController extends ControllerBase {

  /**
   * The Telegram Bot Manager.
   *
   * @var \Drupal\telegram_bot\Service\TelegramBotManagerInterface
   */
  protected $telegramBotManager;

  /**
   * Constructs a New Telegram Object.
   *
   * @param \Drupal\telegram_bot\Service\TelegramBotManagerInterface $telegram_bot_manager
   *   The Telegram Bot Manager Service.
   */
  public function __construct(TelegramBotManagerInterface $telegram_bot_manager) {
    $this->telegramBotManager = $telegram_bot_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('telegram_bot.manager'));
  }

  /**
   * Handles Telegram Webhooks.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns Response.
   */
  public function handle(): Response {
    $this->telegramBotManager->handle();
    return new Response();
  }

}
