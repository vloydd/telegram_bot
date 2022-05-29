<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class , which provide keyboard buttons for searches.
 */
class FlightMenuCommand extends UserCommand {

  /**
   * Name for command.
   *
   * @var string
   */
  protected $name = 'flight';

  /**
   * Short command description.
   *
   * @var string
   */
  protected $description = 'Show text';

  /**
   * Text for using command in telegram.
   *
   * @var string
   */
  protected $usage = '/flight';

  /**
   * Version of command.
   *
   * @var string
   */
  protected $version = '1.2.0';

  /**
   * Building keyboard on call the command.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute() :ServerResponse {
    $text = 'Select the search method :';
    $keyboard_obj = new Keyboard(
          ['Number', 'City'],
        );
    $keyboard = $keyboard_obj
      ->setResizeKeyboard(TRUE)
      ->setOneTimeKeyboard(TRUE)
      ->setSelective(FALSE);

    return $this->replyToChat($text, [
      'reply_markup' => $keyboard,
    ]);
  }

}
