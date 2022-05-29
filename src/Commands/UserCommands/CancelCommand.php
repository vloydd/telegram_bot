<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class, which provide cancelling conversation.
 */
class CancelCommand extends UserCommand {
  /**
   * Name for command.
   *
   * @var string
   */
  protected $name = 'cancel';

  /**
   * Short command description.
   *
   * @var string
   */
  protected $description = 'Cancel the currently active conversation';

  /**
   * Text for using command in telegram.
   *
   * @var string
   */
  protected $usage = '/cancel';

  /**
   * Version of command.
   *
   * @var string
   */
  protected $version = '0.3.0';

  /**
   * We point to the use of databases.
   *
   * @var bool
   */
  protected $need_mysql = TRUE;

  /**
   * Main command execution if no DB connection is available.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function executeNoDb(): ServerResponse {
    return $this->removeKeyboard('Nothing to cancel.');
  }

  /**
   * Main command execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Return Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    $text = 'No active conversation!';

    // Cancel current conversation if any.
    $conversation = new Conversation(
          $this->getMessage()->getFrom()->getId(),
          $this->getMessage()->getChat()->getId()
      );

    if ($conversation_command = $conversation->getCommand()) {
      $conversation->cancel();
      $text = 'Conversation "' . $conversation_command . '" cancelled!';
    }

    return $this->removeKeyboard($text);
  }

  /**
   * Remove the keyboard and output a text.
   *
   * @param string $text
   *   Short text message for users.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Return Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  private function removeKeyboard(string $text): ServerResponse {
    return $this->replyToChat($text, [
      'reply_markup' => Keyboard::remove(['selective' => TRUE]),
    ]);
  }

}
