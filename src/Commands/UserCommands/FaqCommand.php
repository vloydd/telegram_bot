<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * FAQ Class Command.
 */
class FaqCommand extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'faq';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'FAQ Command';

  /**
   * Usage of This Command.
   *
   * @var string
   */
  protected $usage = '/faq';

  /**
   * Version of the Command.
   *
   * @var string
   */
  protected $version = '0.3.0';

  /**
   * FAQ Questions.
   *
   * @var string|null
   */
  protected $faq_question = [];

  /**
   * FAQ Answers.
   *
   * @var string|null
   */
  protected $faq_answer = [];

  /**
   * Executes FAQ Command.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    $main_menu = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->loadByProperties(['menu_name' => 'faq-links', 'enabled' => 1]);
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $inline_keyboard = new InlineKeyboard([]);
    $keyboard_buttons = [];

    foreach ($main_menu as $menu) {
      $menu_item[] = $menu->getTranslation($language)->title->getValue()[0];
    }

    for ($i = 0; $i < count($menu_item); $i++) {
      $keyboard_buttons[$i] = [
        'text' => $menu_item[$i]['value'],
        'callback_data' => "faq_chapter_{$i}",
      ];
      $inline_keyboard->addRow($keyboard_buttons[$i]);
    }
    return $this->replyToChat("You Picked FAQ Command And it's  Time to Talk About Question that You Might Have", [
      'reply_markup' => $inline_keyboard,
      'parse_mode' => 'html',
    ]);
  }

  /**
   * Gets FAQ Answers for a Questions.
   */
  public function getFaqAnswers():array {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load(184);
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $paragraph = $node->get('field_paragraph');
    $paragraph_items = $paragraph->referencedEntities();
    $faq_answers = [];
    $faq_single_answer = [];
    foreach ($paragraph_items as $para) {
      $question_list = $para->getTranslation($language)->field_faq_field->getValue();
      for ($i = 0; $i < count($question_list); $i++) {
        $faq_single_answer[] = $question_list[$i]['answer'];
      }
      $faq_answers[] = $faq_single_answer;
    }
    $this->faq_answer = $faq_answers;
    return $this->faq_answer;
  }

  /**
   * Gets FAQ Questions for a Questions.
   */
  public function getFaqQuestions():array {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load(184);
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $paragraph = $node->get('field_paragraph');
    $paragraph_items = $paragraph->referencedEntities();
    $faq_questions = [];
    $faq_single_question = [];
    foreach ($paragraph_items as $para) {
      $question_list = $para->getTranslation($language)->field_faq_field->getValue();
      for ($i = 0; $i < count($question_list); $i++) {
        $faq_single_question[] = $question_list[$i]['question'];
      }
      $faq_questions[] = $faq_single_question;
      $faq_single_question = [];
    }
    $this->faq_question = $faq_questions;
    return $this->faq_question;
  }

}
