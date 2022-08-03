<?php

namespace Drupal\telegram_bot\Service;

use Drupal\user\UserInterface;
use Longman\TelegramBot\Telegram;

/**
 * This is Interface of TelegramBotManagerInterface.
 */
interface TelegramBotManagerInterface {

  /**
   * Connects to Telegram Bot.
   *
   * @param string|null $bot_token
   *   Telegram Bot Token.
   * @param string|null $bot_username
   *   Telegram Bot Username.
   */
  public function connect(string $bot_token = NULL, string $bot_username = NULL): Telegram;

  /**
   * Getter for Bot Api Key.
   *
   * @return array|mixed|null
   *   Returns Api Key.
   */
  public function getApiKey();

  /**
   * Collects Command List and Handles Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function handle();

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
  public function sendMessage(string $message, string $chat_id):bool;

  /**
   * Sets Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function setWebhook():void;

  /**
   * Deletes Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function deleteWebhook():void;

  /**
   * Generate telegram bot start command with login query params.
   *
   * @param \Drupal\user\UserInterface $account
   *   User Entity.
   *
   * @return string
   *   Returns URL With Start Command.
   */
  public function invitingUrlForUser(UserInterface $account):string;

}
