<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * Class ExchangeCommand.
 *
 * Class creates functionality for currency envelopes.
 */
class ExchangeCommand extends UserCommand {
  /**
   * Name for command.
   *
   * @var string
   */
  protected $name = 'exchange';

  /**
   * Short command description.
   *
   * @var string
   */
  protected $description = 'Currency converter';

  /**
   * Text for using command in telegram.
   *
   * @var string
   */
  protected $usage = '/exchange';

  /**
   * Version of command.
   *
   * @var string
   */
  protected $version = '1.0.1';

  /**
   * We point do not use the databases.
   *
   * @var bool
   */
  protected $need_mysql = FALSE;

  /**
   * We point that the command is available for private dialogues.
   *
   * @var bool
   */
  protected $private_only = TRUE;

  /**
   * Conversation Object.
   *
   * @var \Longman\TelegramBot\Conversation
   */
  protected $conversation;

  /**
   * Main command execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Return Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    $message = $this->getMessage();
    $chat    = $message->getChat();
    $user    = $message->getFrom();
    $text    = trim($message->getText(TRUE));
    $chat_id = $chat->getId();
    $user_id = $user->getId();

    // Preparing response.
    $data = [
      'chat_id'      => $chat_id,
      // Remove any keyboard by default.
      'reply_markup' => Keyboard::remove(['selective' => TRUE]),
    ];

    if ($chat->isGroupChat() || $chat->isSuperGroup()) {
      // Force reply is applied by default , so it can work with privacy on.
      $data['reply_markup'] = Keyboard::forceReply(['selective' => TRUE]);
    }

    // Conversation start.
    $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

    // Load any existing notes from this conversation.
    $notes = &$this->conversation->notes;
    !is_array($notes) && $notes = [];

    // Load the current state of the conversation.
    $state = $notes['state'] ?? 0;

    $result = Request::emptyResponse();

    // State machine
    // Every time a step is achieved the state is updated.
    switch ($state) {
      // 1st Step.Choose the currency from which to convert.
      case 0:
        if ($text === '' || !in_array($text, ['UAH', 'EUR', 'USD', 'BHD'], TRUE)) {
          $notes['state'] = 0;
          $this->conversation->update();
          $this->buildCurrencyKeyboard('Choose the currency from which to convert:');
          break;
        }

        $notes['currency_from'] = $text;
        $text                   = '';

        // 2d Step.Select the currency to convert.
      case 1:
        if ($text === '' || !in_array($text, ['UAH', 'EUR', 'USD', 'BHD'], TRUE)) {
          $notes['state'] = 1;
          $this->conversation->update();
          $this->buildCurrencyKeyboard('Select the currency to convert:');
          break;
        }

        $notes['currency_to'] = $text;
        $text                 = '';

        // 3d Step.Type sum for convert.
      case 2:
        if ($text === '' || !is_numeric($text)) {
          $notes['state'] = 2;
          $this->conversation->update();

          $data['text'] = 'Type sum for convert';
          if ($text !== '') {
            $data['text'] = 'Sum must be a number';
          }

          $result = Request::sendMessage($data);
          break;
        }

        $notes['sum'] = $text;
        $text         = '';

        // 4th Step.Create request for currency api and calculate result.
      case 3:
        $this->conversation->update();
        $currency_to = $notes['currency_to'];
        $api_key = \Drupal::config('telegram_bot.settings')->get('currency_api');
        $currency_request = file_get_contents("https://api.currencyapi.com/v3/latest?apikey={$api_key}&currencies={$notes['currency_to']}&base_currency={$notes['currency_from']}");
        $response_array = json_decode($currency_request);
        $currency_ratio = $response_array->data->$currency_to->value;
        $result_sum = $currency_ratio * $notes['sum'];
        $data['text'] = 'Its your converted money there : ' . $result_sum . ' ' . $currency_to;
        $this->conversation->stop();

        $result = Request::sendMessage($data);
        break;
    }

    return $result;
  }

  /**
   * Building keyboard, which will be used in steps.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function buildCurrencyKeyboard($text) {
    $keyboard_obj = new Keyboard(
      ['UAH', 'USD'],
      ['EUR', 'BHD'],
    );
    $keyboard = $keyboard_obj
      ->setResizeKeyboard(TRUE)
      ->setOneTimeKeyboard(TRUE)
      ->setSelective(FALSE);

    $this->replyToChat($text, [
      'reply_markup' => $keyboard,
    ]);
  }

}
