<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultContact;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultLocation;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Class to Handle Inline Query.
 */
class InlineQueryCommand extends SystemCommand {
  /**
   * Command Name.
   *
   * @var string
   */
  protected $name = 'inlinequery';

  /**
   * Command Description.
   *
   * @var string
   */
  protected $description = 'Handle Inline Query';

  /**
   * Command Version.
   *
   * @var string
   */
  protected $version = '1.0.0';

  /**
   * Main command execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Telegram Response.
   */
  public function execute(): ServerResponse {
    $inline_query = $this->getInlineQuery();
    $query        = $inline_query->getQuery();
    $loc          = $inline_query->getLocation();
    $message      = $this->getMessage();
    $user_id      = $inline_query->getFrom()->getId();
    $usermane     = $inline_query->getFrom()->getUsername();
    $name         = $inline_query->getFrom()->getFirstName();
    // Response data.
    $results = [];
    // Stores coordinates.
    $location = [];
    if ($loc) {
      $location['lat'] = $loc->getLatitude();
      $location['lon'] = $loc->getLongitude();
    }

    if ($query !== '') {
      $query = strtolower($query);
      switch ($query) {
        case 'weather':
          $your_city = \Drupal::config('telegram_bot.settings')->get('openweather_city');
          $weather = \Drupal::service('telegram_bot.openweather')->getWeatherByName($your_city);
          $results[] = new InlineQueryResultArticle([
            'id'                    => '001',
            'title'                 => 'Weather in ' . $your_city . ' right now',
            'description'           => $this->formShortWeatherMessage($weather),
            // Here you can put any other Input...MessageContent you like.
            // It will keep the style of an article
            // but post the specific message type back to the user.
            'input_message_content' => new InputTextMessageContent([
              'message_text' => 'Weather in ' . $your_city . ' right now' . PHP_EOL . $this->formShortWeatherMessage($weather),
            ]),
          ]);
          break;

        case 'local weather':
          if ($loc) {
            $weather = \Drupal::service('telegram_bot.openweather')->getWeatherByCoord($location['lat'], $location['lon']);
            $results[] = new InlineQueryResultArticle([
              'id'                    => '001',
              'title'                 => 'Weather in ' . $weather['name'] . ' right now',
              'description'           => $this->formShortWeatherMessage($weather),
              'input_message_content' => new InputTextMessageContent([
                'message_text' => 'Weather in ' . $weather['name'] . ' right now' . PHP_EOL . $this->formShortWeatherMessage($weather),
              ]),
            ]);
          }
          else {
            $results[] = new InlineQueryResultArticle([
              'id'                    => '001',
              'title'                 => "404 Error",
              'description'           => "Sorry, we can't get Your location",
              'input_message_content' => new InputTextMessageContent([
                'message_text' => "Sorry, we can't get Your location. Please, repeat later",
              ]),
            ]);
          }
          break;

        case 'location':
          if ($loc) {
            $results[] = new InlineQueryResultLocation([
              'id'        => '003',
              'title'     => 'Your location right here!',
              'latitude'  => $location['lat'],
              'longitude' => $location['lon'],
            ]);
          }
          else {
            $results[] = new InlineQueryResultLocation([
              'id'        => '003',
              'title'     => 'The center of the world!',
              'latitude'  => 40.866667,
              'longitude' => 34.566667,
            ]);
          }
          break;

        case 'me':
          $results[] = new InlineQueryResultContact([
            'id'           => '002',
            'phone_number' => '12345678',
            'first_name'   => '@' . $usermane,
            'last_name'    => $name,
          ]);
          break;

      }
    }

    return $inline_query->answer($results);
  }

  /**
   * Forms Short Weather Message.
   *
   * @param array $weather
   *   Weather Request.
   *
   * @return string
   *   Returns Message.
   */
  public function formShortWeatherMessage(array $weather) {
    $message = "{$weather['weather'][0]['main']} ({$weather['weather'][0]['description']})\n🌡Temperature: {$weather['main']['temp']}°C\n🌪Wind: {$weather['wind']['speed']}M/S {$weather['wind']['deg']}°";
    return $message;
  }

}
