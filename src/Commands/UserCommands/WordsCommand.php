<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\ChatAction;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * Class that Works With WordsAPI.
 */
class WordsCommand extends UserCommand {

  /**
   * Command Name.
   *
   * @var string
   */
  protected $name = 'words';

  /**
   * Command Description.
   *
   * @var string
   */
  protected $description = 'Works with WordsApi';

  /**
   * Usage of the Command.
   *
   * @var string
   */
  protected $usage = '/words';

  /**
   * Command Version.
   *
   * @var string
   */
  protected $version = '1.0.0';

  /**
   * Sets/Unsets Privacy.
   *
   * @var bool
   */
  protected $private_only = FALSE;

  /**
   * Command Execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Telegram Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    // Basic message data.
    $message      = $this->getMessage();
    $chat_id      = $message->getChat()->getId();
    $message_id   = $message->getMessageId();
    $message_text = $message->getText(TRUE);
    // Data to be sent.
    $data = [
      'chat_id' => $chat_id,
      'reply_to_message_id' => $message_id,
    ];
    // Getting word operation by WordsAPI.
    $words = \Drupal::service('telegram_bot.words')
      ->getWordsInfo($message_text);
    if ($words) {
      $response_message = $this->buildMessage($words);
    }
    else {
      $response_message = "Sorry, we don't find info about this word. Please, check that the word is correct";
    }
    // Chat action "typing...".
    Request::sendChatAction([
      'chat_id' => $chat_id,
      'action'  => ChatAction::TYPING,
    ]);
    $keyboard_obj[] = new Keyboard(
      ['Weather', 'Forecast'],
      ['Course', 'Exchange'],
      ['FAQ', 'Enterprises'],
      ['Help', 'Flight'],
      ['Main Page', 'Visit a Website']
    );
    $keyboard = end($keyboard_obj)
      ->setResizeKeyboard(TRUE)
      ->setOneTimeKeyboard(TRUE)
      ->setSelective(FALSE);
    // Sends Message.
    $data['text'] = $response_message;
    $data['parse_mode'] = 'HTML';
    $data['reply_markup'] = $keyboard;
    return Request::sendMessage($data);
  }

  /**
   * Builds Message from Response .
   *
   * @param array $words
   *   Response Array.
   *
   * @return string
   *   Message Result.
   */
  public function buildMessage(array $words):string {
    $keys = array_keys($words);
    $message = "⚡⚡ We've got {$keys[1]}, based on Your Request: <b><i>{$words['word']}</i></b>\n";
    $message .= "    🗿Pronunciation: {$words['pronunciation']['all']}
    📝Word Frequency: {$words['frequency']}
    ✏Counts-{$words['syllables']['count']}: ";
    for ($i = 0; $i < $words['syllables']['count']; $i++) {
      $message .= $words['syllables']['list'][$i];
      if ($i != $words['syllables']['count'] - 1) {
        $message .= '-';
      }
    }
    for ($j = 0; $j < count($words['results']); $j++) {
      $message .= PHP_EOL . PHP_EOL . '💬' . $this->getAdditionalWordInfo($words['results'][$i]);
    }
    return $message;
  }

  /**
   * Gets Additional Info(Definition, Parts of Speech, Synonyms, Etc).
   *
   * @param array $word
   *   One Word - One Result.
   *
   * @return string
   *   Returns Additional Message.
   */
  public function getAdditionalWordInfo(array $word):string {
    foreach ($word as $key => $value) {
      $key_upper = ucwords($key);
      if (!is_array($value)) {
        $message .= "📌 {$key_upper}: {$value}\n";
      }
      else {
        $message .= "📍 {$key_upper}: ";
        for ($i = 0; $i < count($value); $i++) {
          $message .= "{$value[$i]}; ";
        }
        $message .= "\n";
      }
    }
    return $message;
  }

}
