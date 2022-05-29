<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class that Provides Help Command.
 */
class StartCommand extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'start';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'Start Command';

  /**
   * Usage of This Command.
   *
   * @var string
   */
  protected $usage = '/start';

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
    // For Case in which We Have: "/start bot".
    // $deep_linking_parameter = $this->getMessage()->getText(TRUE);

    /**
     * @var \Longman\TelegramBot\Entities\Keyboard[] $keyboards
     */
    $keyboards[] = new Keyboard(
      ['FAQ', 'Enterprises'],
      ['Help', 'Exchange'],
      ['Visit a Website', 'Flight'],
      ['Main Page'],
    );

    $keyboard = end($keyboards)
      ->setResizeKeyboard(TRUE)
      ->setOneTimeKeyboard(TRUE)
      ->setSelective(FALSE);
    return $this->replyToChat(
          "Hello, My Friend! Welcome to Bahrain International Airport \u{1F44B}. It's Great to See You in Our Telegram Bot. What Can I Offer You?
            \nIf You Need Any Help, Please Use Command /help to Check All Commands or Take a Look at Menu on the Left Side\u{1F44C}", [
              'reply_markup' => $keyboard,
            ]);
  }

}
