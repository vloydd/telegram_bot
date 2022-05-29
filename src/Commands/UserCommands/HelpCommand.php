<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class that Provides Help Command.
 */
class HelpCommand extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'help';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'Help Command To Check Available Commands';

  /**
   * Usage of This Command.
   *
   * @var string
   */
  protected $usage = '/help';

  /**
   * Version of the Command.
   *
   * @var string
   */
  protected $version = '1.0.0';

  /**
   * Only for Private Chats.
   *
   * @var bool
   */
  protected $private_only = TRUE;

  /**
   * Main command execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {

    /**
     * @var \Longman\TelegramBot\Entities\Keyboard[] $keyboards
     */
    $keyboards[] = new Keyboard(['Rollback To Main Page']);

    $keyboard = end($keyboards)
      ->setResizeKeyboard(TRUE)
      ->setOneTimeKeyboard(TRUE)
      ->setSelective(FALSE);
    return $this->replyToChat(
      "Dear Friend, You Picked Help and it's Time to Show List of Available Commands. I'm Here to Help You.
      \n/start - Start Command\n/help - Help Command\n/flights - Find Your Flight by City-name or Board Number\n/cancel - Leave a Conversation (only if You started)\n/faq - Checkout FAQ\n/exchange - Exchange\n/enterprises - Checkout Enterprises of Our Airport", [
        'reply_markup' => $keyboard,
      ]);
  }

}
