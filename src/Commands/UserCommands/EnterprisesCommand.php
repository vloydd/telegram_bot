<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class for Enterprises Command.
 */
class EnterprisesCommand extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'enterprises';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'Get and shows an info from FAQ section';

  /**
   * Usage of This Command.
   *
   * @var string
   */
  protected $usage = '/enterprises';

  /**
   * Version of the Command.
   *
   * @var string
   */
  protected $version = '0.3.0';

  /**
   * Enterprises Command Execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $menu_item = [];
    $main_menu = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->loadByProperties(['menu_name' => 'at-the-airport', 'enabled' => 1]);
    $inline_keyboard = new InlineKeyboard([]);
    $keyboard_buttons = [];
    foreach ($main_menu as $menu) {
      $menu_item[] = $menu->getTranslation($language)->title->getValue()[0];
    }
    for ($i = 0; $i < count($menu_item); $i++) {
      $keyboard_buttons[$i] = [
        'text' => $menu_item[$i]['value'],
        'callback_data' => "enterprises_{$i}",
      ];
      $inline_keyboard->addRow($keyboard_buttons[$i]);
    }
    return $this->replyToChat("You Picked Enterprises Command And it's  Time to Show You Things that We Have In Our Airport.", [
      'reply_markup' => $inline_keyboard,
    ]);
  }

}
