<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * FAQ Get Questions Class.
 */
class GetFaqQuestionsCommand extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'getfaqquestions';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'FAQ Questions';


  /**
   * Version of Our Command.
   *
   * @var string
   * Version of Our Command.
   */
  protected $version = '1.0.0';

  /**
   * Only for Private Chats.
   *
   * @var bool
   */
  protected $private_only = TRUE;

  /**
   * Execute Command.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute() :ServerResponse {

    $callback_query = $this->getCallbackQuery();
    $callback_data = $callback_query->getData();
    $inline_keyboard = new InlineKeyboard([]);
    $data = [
      'chat_id' => $callback_query->getFrom()->getId(),
      'reply_markup' => $inline_keyboard,
      'parse_mode' => 'html',
    ];
    $faq_command = (new FaqCommand($this->telegram));
    $faq_questions = $faq_command->getFaqQuestions();
    $keyboard_buttons = [];
    for ($i = 0; $i < count($faq_questions); $i++) {
      switch ($callback_data) {
        case "faq_chapter_{$i}":
          for ($j = 0; $j < count($faq_questions[$i]); $j++) {
            $keyboard_buttons[$j] = [
              'text' => $faq_questions[$i][$j],
              'callback_data' => "faq_verse_{$i}_{$j}",
            ];
            $inline_keyboard->addRow($keyboard_buttons[$j]);
          }
          $data['text'] = "<b>Chapter {$i}</b>";
          return Request::sendMessage($data);

        default:
          $data['text'] = 'Sorry, there is chapter of questions just like that... :(';
      }
    }
    return Request::sendMessage($data);
  }

}
