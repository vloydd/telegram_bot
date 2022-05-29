<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Drupal\views\Views;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * Class for Enterprises Info.
 */
class EnterprisesInfo extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'enterprisesinfo';
  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'Enterprises Information with Links on Site';

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
   * View Id for Get Data from it.
   *
   * @var string
   */
  protected $view_id = '';

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
   * Gets View Header.
   */
  protected function getViewHeader($view_id, $display_id): string {
    $view = Views::getView($view_id);
    // Build View.
    $view_build = $view->build($display_id);
    // Get Header of the View if it Exists.
    if (!empty($view->header['area'])) {
      return $this->removeHtml($view->header['area']->options['content']['value']);
    }
    // Custom Text.
    else {
      $view_title = $view->getTitle();
      return "You Picked {$view_title}. And It's Time To Show You {$view_title}";
    }
  }

  /**
   * Gets View Entities.
   */
  protected function getViewEntities($view_id): InlineKeyboard {
    $view = Views::getView($view_id);
    $ds = $view->setDisplay('default');
    $ex = $view->execute();
    // Results of the View.
    $view_result = $view->result;
    // Inline Keyboard.
    $inline_keyboard = new InlineKeyboard([]);
    // Values (Path and Link to Content).
    $value = [];
    foreach ($view_result as $view_single) {
      $rand = $view_single->_entity->toArray();
      $value['title'][] = $rand['title'][0]['value'];
      $value['path'][] = $rand['path'][0]['alias'];
    }
    for ($i = 0; $i < count($value['title']); $i++) {
      // Create Buttons.
      $keyboard_buttons[$i] = [
        'text' => $value['title'][$i],
        'url' => "https://{$_SERVER['HTTP_HOST']}/{$value['path'][$i]}",
      ];
      // Add them as Row.
      $inline_keyboard->addRow($keyboard_buttons[$i]);
    }
    return $inline_keyboard;
  }

  /**
   * Execute Command.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute() :ServerResponse {
    $callback_query = $this->getCallbackQuery();
    $callback_data  = $callback_query->getData();
    $view_id = '';
    $display_id = '';
    switch ($callback_data) {
      case 'enterprises_0':
        $view_id = 'dining_restaurants';
        $display_id = 'page_1';
        break;

      case 'enterprises_1':
        $view_id = 'shopping';
        $display_id = 'page_1';
        break;

      case 'enterprises_2':
        $view_id = 'galleries';
        $display_id = 'galleries_page';
        break;

      case 'enterprises_3':
        $view_id = 'lounges_hotel';
        $display_id = 'loung_page';
        break;

      case 'enterprises_4':
        $view_id = 'services';
        $display_id = 'block_1';
        break;
    }
    $inline_keyboard = $this->getViewEntities($view_id);

    $view_header = $this->getViewHeader($view_id, $display_id);
    $data = [
      'chat_id' => $callback_query->getFrom()->getId(),
      'text' => $view_header,
      'reply_markup' => $inline_keyboard,
    ];

    return Request::sendMessage($data);
  }

}
