<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class to Handle Text Input.
 */
class GenericMessageCommand extends SystemCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'genericmessage';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'Handle generic message';

  /**
   * Version of the Command.
   *
   * @var string
   */
  protected $version = '1.0.0';

  /**
   * Main command execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    if ($active_conversation_response = $this->executeActiveConversation()) {
      return $active_conversation_response;
    }
    $message = $this->getMessage();
    // Handle any kind of message here.
    $type = $message->getType();
    $message_text = strtolower($message->getText(TRUE));

    switch ($message_text) {
      case 'visit a website':
        $inline_keyboard = new InlineKeyboard([
          [
            'text' => 'Main Page',
            'url' => 'https://bahrainair.dev.drudesk.com',
          ],
          [
            'text' => 'Corporate Page',
            'url' => 'https://bahrainair.dev.drudesk.com/corporate',
          ],
        ]);
        return $this->replyToChat("You're Always Welcome Here And on Our Website) With Love for Our Team\u{1F970}", [
          'reply_markup' => $inline_keyboard,
        ]);

      case 'rollback to main page':
      case 'main page':
        $message_text = 'start';
        break;

    }
    return $this->getTelegram()->executeCommand($message_text);
  }

}
