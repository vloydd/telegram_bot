<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class that Provides Course Command.
 */
class CourseCommand extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'course';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'Course Command';

  /**
   * Usage of This Command.
   *
   * @var string
   */
  protected $usage = '/course';

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
   * Main command execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    /**
     * @var \Longman\TelegramBot\Entities\Keyboard[] $keyboards
     */
    $keyboards[] = new Keyboard(
      ['Weather', 'Forecast'],
      ['Course', 'Exchange'],
      ['FAQ', 'Enterprises'],
      ['Help', 'Flight'],
      ['Main Page', 'Visit a Website']
    );
    // HTTP request to PrivatBank API.
    $http_client = new Client();
    $response = $http_client->request('GET', 'https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=5');
    $code = $response->getStatusCode();
    if ($code == 200) {
      $result = JSON::decode($response->getBody());
      $message = $this->formMessagefromRequest($result);
    }
    // If it's not Ok.
    else {
      $message = 'Sorry, Something Went Wrong. Please, Repeat the Command or Wait Up a Bit';
    }
    // Forming up keyboard.
    $keyboard = end($keyboards)
      ->setResizeKeyboard(TRUE)
      ->setOneTimeKeyboard(TRUE)
      ->setSelective(FALSE);
    return $this->replyToChat($message, [
      'reply_markup' => $keyboard,
    ]);
  }

  /**
   * Forms Message from API Request.
   *
   * @param array $data
   *   API Data.
   *
   * @return string
   *   Message Itself.
   */
  public function formMessagefromRequest(array $data) {
    $message = "⚡We've got current course, as You wish:
      🇺🇲:
    	    Buy: {$data['0']['buy']}🇺🇦
    	    Sale: {$data['0']['sale']}🇺🇦
    	🇪🇺:
    	    Buy: {$data['1']['buy']}🇺🇦
    	    Sale: {$data['1']['sale']}🇺🇦
    	🪙:
    	    Buy: {$data['2']['buy']}🇺🇲
    	    Sale: {$data['2']['sale']}🇺🇲
      ";
    return $message;
  }

}
