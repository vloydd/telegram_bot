<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * Class for Handling Callbacks.
 */
class CallbackqueryCommand extends SystemCommand {
  /**
   * Describes Command Name.
   *
   * @var string
   *   This is Command Name.
   */
  protected $name = 'callbackquery';

  /**
   * Describes Command.
   *
   * @var string
   *    Description of the Command.
   */
  protected $description = 'Handle the callback query';

  /**
   * Version of Command.
   *
   * @var string
   *   This is Version of Command.
   */
  protected $version = '1.0.1';

  /**
   * Main command execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {

    // Callback query data can be fetched and handled accordingly.
    $callback_query = $this->getCallbackQuery();
    $callback_data  = $callback_query->getData();
    if (strpos($callback_data, 'enterprises') === 0) {
      return $this->telegram->executeCommand('enterprisesinfo');
    }
    elseif (strpos($callback_data, 'faq_chapter') === 0) {
      return $this->telegram->executeCommand('getfaqquestions');
    }
    elseif (strpos($callback_data, 'faq_verse') === 0) {
      return $this->telegram->executeCommand('getfaqanswers');
    }
    return Request::emptyResponse();
  }

}
