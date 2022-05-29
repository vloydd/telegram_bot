<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * Class for Getting Answers from FAQ.
 */
class GetFaqAnswersCommand extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'getfaqanswers';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'FAQ Answers';

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
   * Removes HTML Tags and Other Trash from String.
   *
   * @param string $str
   *   Our String Value.
   *
   * @return string
   *   Returns String without
   */
  protected function removeHtml(string $str):string {
    return str_replace('&nbsp;', ' ', strip_tags($str));
  }

  /**
   * Execute Function.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute() :ServerResponse {
    $callback_query = $this->getCallbackQuery();
    $callback_data  = $callback_query->getData();
    $data = [
      'chat_id' => $callback_query->getFrom()->getId(),
    ];

    $faq_command = (new FaqCommand($this->telegram));
    $faq_answers = $faq_command->getFaqAnswers();
    $faq_questions = $faq_command->getFaqQuestions();
    for ($i = 0; $i < count($faq_questions); $i++) {
      for ($j = 0; $j < count($faq_questions[$i]); $j++) {
        switch ($callback_data) {
          case "faq_verse_{$i}_{$j}":
            $data['text'] = $this->removeHtml($faq_answers[$i][$j]);
            return Request::sendMessage($data);

          default:
            $data['text'] = 'Sorry, there is no answer for your question... :(';
        }
      }
    }
    return Request::sendMessage($data);
  }

}
