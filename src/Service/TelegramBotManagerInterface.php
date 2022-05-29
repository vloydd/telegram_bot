<?php

namespace Drupal\telegram_bot\Service;

/**
 * This is Interface of TelegramBotManagerInterface.
 */
interface TelegramBotManagerInterface {

  /**
   * Connects to Telegram Bot.
   *
   * @param string $bot_token
   *   Telegram Bot Token.
   */
  public function connect(string $bot_token = NULL);

  /**
   * Sends Message to Dedicated User.
   *
   * @param string $message
   *   Message to Send.
   * @param string $chat_id
   *   User to Receive.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   *
   * @return bool
   *   Sent/Unsent Message.
   */
  public function sendMessage(string $message, string $chat_id): bool;

  /**
   * Sets Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function setWebhook();

  /**
   * Deletes Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function deleteWebhook();

}
