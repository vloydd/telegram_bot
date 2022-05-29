<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class for Random Commands.
 */
class GenericCommand extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'generic';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'Handles NOT Usable and Sets Default Value for Them';

  /**
   * Usage of This Command.
   *
   * @var string
   */
  protected $version = '1.0.0';

  /**
   * Command Execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    $message = $this->getMessage();
    $command = $message->getCommand();
    return $this->replyToChat("Command /
    {$command} NOT Found...\u{1F62D}");
  }

}
