<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 * Class for Getting Weather Results From OpenWeather.
 */
class WeatherCommand extends UserCommand {

  /**
   * Command Name.
   *
   * @var string
   */
  protected $name = 'weather';

  /**
   * Short Command Description.
   *
   * @var string
   */
  protected $description = 'Weather By Location and Other Features';

  /**
   * Text for Using Command in Telegram.
   *
   * @var string
   */
  protected $usage = '/weather';

  /**
   * Version of Command.
   *
   * @var string
   */
  protected $version = '1.0.1';

  /**
   * Usage of SQL Is that what We Need.
   *
   * @var bool
   */
  protected $need_mysql = TRUE;

  /**
   * This Command is Private Chats Only.
   *
   * @var bool
   */
  protected $private_only = FALSE;

  /**
   * Conversation Object.
   *
   * @var \Longman\TelegramBot\Conversation
   */
  protected $conversation;

  /**
   * Execution of the Command.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Return Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    // Getting basic data from message.
    $message = $this->getMessage();
    $chat = $message->getChat();
    $user = $message->getFrom();
    $text = trim($message->getText(TRUE));
    $chat_id = $chat->getId();
    $type = $message->getType();
    $user_id = $user->getId();
    // Preparing our response.
    $data = [
      'chat_id'      => $chat_id,
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
    $result = Request::emptyResponse();
    $state = $notes['state'] ?? 0;
    // For storing OpenWeather API response.
    $weather = [];
    $text_lowercase = strtolower($text);
    if ($text_lowercase == 'rollback to main page') {
      $this->conversation->stop();
      $this->buildCurrencyKeyboard('Conversation Finished', 3);
      return $result;
    }
    if ($text_lowercase == 'get back') {
      $state = intval($state) - 1;
      $this->conversation->update();
      if ($state == 0) {
        $this->conversation->stop();
        $this->buildCurrencyKeyboard('Conversation Finished', 3);
        return $result;
      }
    }
    switch ($state) {
      // 1st Step. Getting Basic Info.
      case 0:
        // For the cases in which we type '/weather city'.
        if ($text != '' && $text_lowercase != 'weather') {
          $weather = \Drupal::service('telegram_bot.openweather')->getWeatherByName($text);
          if ($weather) {
            $this->conversation->update();
            $this->buildCurrencyKeyboard($this->formMessagefromRequest($weather), 3);
            $this->conversation->stop();
            $this->sendLocation($chat_id, $weather);
          }
          else {
            $this->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check That Command is Correct(", 1);
          }
        }
        // Other cases - let user choose.
        elseif ($text === '' || in_array($text, ['weather', 'Weather'], TRUE)) {
          $notes['state'] = 1;
          $notes['type'] = $text;
          $this->conversation->update();
          $this->buildCurrencyKeyboard('Choose The Type of Weather', 1);
        }
        break;

      // 2nd Step - response by keyboard.
      case 1:
        if ($text === '' || in_array($text, ['Your', 'City', 'Coord', 'Location', 'Get Back'], TRUE)) {
          switch ($text) {
            case 'Location':
              $notes['state'] = 2.1;
              $notes['type'] = 'Location';
              $this->conversation->update();
              $this->buildCurrencyKeyboard('Please, send your Location', 6);
              break;

            case 'Your':
              $your_city = \Drupal::config('telegram_bot.settings')->get('openweather_city');
              $weather = \Drupal::service('telegram_bot.openweather')->getWeatherByName($your_city);
              if ($weather) {
                $this->conversation->update();
                $this->buildCurrencyKeyboard($this->formMessagefromRequest($weather), 3);
                $this->conversation->stop();
                $this->sendLocation($chat_id, $weather);
              }
              else {
                $this->conversation->stop();
                $this->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check if You Set Up the City in Your Form(", 1);
              }
              break;

            case 'City':
              $notes['state'] = 2.2;
              $notes['type'] = 'City';
              $this->conversation->update();
              $this->buildCurrencyKeyboard('Please, Enter City Name', 5);
              break;

            case 'Coord':
              $notes['state'] = 2.3;
              $notes['type'] = 'Coord';
              $this->conversation->update();
              $this->buildCurrencyKeyboard('Please, Your Coordinates Example in Format (Latitude, Longitude)', 5);
              break;

            default:
              $this->buildCurrencyKeyboard('Please, choose the type of weather', 1);
              $notes['state'] = 1;
              break;
          }
          $this->conversation->update();
          break;
        }
        $notes['type'] = $text;
        // 3rd step - by sending location.
      case 2.1:
        if ($type == 'location') {
          $loc = $message->getLocation();
          $location = [];
          $location['lat'] = $loc->getLatitude();
          $location['lon'] = $loc->getLongitude();
          $weather = \Drupal::service('telegram_bot.openweather')
            ->getWeatherByCoord($location['lat'], $location['lon']);
          if ($weather) {
            $this->conversation->stop();
            $this
              ->buildCurrencyKeyboard($this->formMessagefromRequest($weather), 3);
            $this->sendLocation($chat_id, $weather);
            break;
          }
          else {
            $this->conversation->stop();
            $this
              ->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check That Command is Correct(", 5);
            break;
          }
        }
        else {
          $notes['state'] = 2.1;
          $this->conversation->update();
          $this->buildCurrencyKeyboard('Please, send your Location as marker', 5);
          break;
        }
        // 3rd step - by entering dedicated city.
      case 2.2:
        $text = trim($message->getText(TRUE));
        $weather = \Drupal::service('telegram_bot.openweather')->getWeatherByName($text);
        if ($weather) {
          $this->conversation->stop();
          $this->buildCurrencyKeyboard($this->formMessagefromRequest($weather), 3);
          $this->sendLocation($chat_id, $weather);
          break;
        }
        else {
          $this->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check if You Set Up the Correct City-name(", 5);
          break;
        }
        // 3rd step - by entering coordinates.
      case 2.3:
        $text = trim($message->getText(TRUE));
        $coord = explode(", ", $text);
        try {
          $weather = \Drupal::service('telegram_bot.openweather')
            ->getWeatherByCoord($coord[0], $coord[1]);
        }
        catch (TelegramException $e) {
          $this->buildCurrencyKeyboard("Sorry, Something Wnt Wrong, Please, Check That Your Coordinates is Correct(", 4);
          \Drupal::logger('telegram_bot')
            ->error(
              "Telegram Bot: There's an Error for Telegram: @error.",
              [
                '@error' => $e->getMessage(),
              ]
            );
        }
        if ($weather) {
          $this->conversation->stop();
          $this->buildCurrencyKeyboard($this->formMessagefromRequest($weather), 4);
          $this->sendLocation($chat_id, $weather);
        }
        else {
          $this->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check That Your Coordinates is Correct(", 5);
        }
        break;

      default:
        $this->conversation->stop();
        $this->buildCurrencyKeyboard('Conversation Finished', 3);
        return $result;
    }
    return $result;
  }

  /**
   * Sends a Message with Location.
   *
   * @param string $chat_id
   *   Basically, Chat ID.
   * @param array $weather
   *   Weather Date to Get a Location.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Server Response Object.
   */
  public function sendLocation(string $chat_id, array $weather):ServerResponse {
    return Request::sendLocation(
      [
        'chat_id' => $chat_id,
        'latitude' => $weather['coord']['lat'],
        'longitude' => $weather['coord']['lon'],
      ]);
  }

  /**
   * Forms Message by Weather Response.
   *
   * @param array $weather
   *   Weather Response Array.
   *
   * @return string
   *   Message Itself.
   */
  public function formMessagefromRequest(array $weather):string {
    // Getting wind degree.
    $wind_degree = $this->getWindDirection($weather['wind']['deg']);
    // Getting weather emoji.
    $weather_emoji = $this->getEmoji($weather['weather'][0]['icon']);
    // Getting visibility.
    $weather_visibility = (float) $weather['visibility'] / 1000;
    // Convert timestamps into time.
    $weather_sunrise = date('H:i', $weather['sys']['sunrise']);
    $weather_sunset = date('H:i', $weather['sys']['sunset']);
    // Forming up message.
    $message = "⚡We've got weather by request: {$weather['name']}
    Main:
      {$weather_emoji} {$weather['weather'][0]['main']}({$weather['weather'][0]['description']})
      🌡Current Temperature: {$weather['main']['temp']}°C
      🤒Feels Like: {$weather['main']['temp']}°C
      🌪Wind: {$weather['wind']['speed']}M/S {$wind_degree}

    Other Features:
        🌠Visibility: {$weather_visibility}km
        ✨Pressure: {$weather['main']['pressure']}PA
        ☁Clouds: {$weather['clouds']['all']}%
        💦Humidity: {$weather['main']['humidity']}%
        🗺You Are In: {$weather['sys']['country']}({$weather['name']})
        🧭Coordinates: ({$weather['coord']['lat']}, {$weather['coord']['lon']})

    Daytime:
        🌄Sunrise(Local time): {$weather_sunrise}
        🌇Sunset(Local time): {$weather_sunset}

    ";
    if ($weather['rain']) {
      $rain_message = "Rain:
      ☔Rain Volume(Last 1h): {$weather['rain']['1h']}mm
      ";
      $message = $message . $rain_message;
    }
    if ($weather['snow']) {
      $snow_message = "Snow:
      ❄Snow Volume(Last 1h): {$weather['snow']['1h']}mm
      ";
      $message = $message . $snow_message;
    }
    return $message;
  }

  /**
   * Building Keyboard, and Send Message with it.
   *
   * @param string $text
   *   Message Text.
   * @param int $status
   *   Keyboard Status.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function buildCurrencyKeyboard(string $text, int $status) {
    switch ($status) {
      case 1:
        $keyboard_obj = new Keyboard(
          ['Your', 'City', 'Location', 'Coord'],
          ['Get Back']
        );
        break;

      case 2:
        $keyboard_obj = new Keyboard(
          ['Hourly', 'Daily', 'City', 'Location'],
        );
        break;

      case 3:
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
      case 5:
        $keyboard_obj = new Keyboard(
          ['Get Back'],
          ['Rollback To Main Page']
        );
        break;

      case 6:
        $keyboard_obj = new Keyboard(
          (new KeyboardButton('Share Location'))
            ->setRequestLocation(TRUE),
          ['Get Back'],
          ['Rollback To Main Page']
        );
        break;

      case 4:
      default:
        $keyboard_obj = Keyboard::remove();
    }
    if ($status != 3 && $status != 4) {
      $keyboard = $keyboard_obj
        ->setResizeKeyboard(TRUE)
        ->setOneTimeKeyboard(TRUE)
        ->setSelective(FALSE);
    }

    $this->replyToChat($text, [
      'reply_markup' => $keyboard,
    ]);
  }

  /**
   * Gets Emoji by Picture ID.
   *
   * @param string $picture_id
   *   Picture ID.
   *
   * @return string
   *   Emoji Itself.
   */
  public function getEmoji(string $picture_id):string {
    // Collecting emoji values.
    $weather_emoji = '';
    switch ($picture_id) {
      case '01d':
        $weather_emoji = '🌞';
        break;

      case '01n':
        $weather_emoji = '🌚';
        break;

      case '02d':
        $weather_emoji = '⛅';
        break;

      case '02n':
        $weather_emoji = '🌌';
        break;

      case '03d':
        $weather_emoji = '☁';
        break;

      case '03n':
        $weather_emoji = '☁🌚';
        break;

      case '04d':
        $weather_emoji = '🌫';
        break;

      case '04n':
        $weather_emoji = '🌫🌚';
        break;

      case '09d':
        $weather_emoji = '🌧';
        break;

      case '09n':
        $weather_emoji = '🌧🌚';
        break;

      case '10d':
        $weather_emoji = '🌦';
        break;

      case '10n':
        $weather_emoji = '🌦🌚';
        break;

      case '11d':
        $weather_emoji = '🌩';
        break;

      case '11n':
        $weather_emoji = '🌩🌚';
        break;

      case '13d':
        $weather_emoji = '🌨';
        break;

      case '13n':
        $weather_emoji = '🌨🌚';
        break;

      case '50d':
        $weather_emoji = '🌬🌫';
        break;

      case '50n':
        $weather_emoji = '🌬🌫🌚';
        break;

      default:
        $weather_emoji = '🌁';
        break;
    }
    return $weather_emoji;
  }

  /**
   * Gets Wind Direction from Degree.
   *
   * @param int $wind_value
   *   Wind Degree.
   *
   * @return string
   *   Wind Direction.
   */
  public function getWindDirection(int $wind_value):string {
    // Converting INT degree to string values.
    $wind_degree = '';
    if (11.25 <= $wind_value && $wind_value < 33.75) {
      $wind_degree = 'NNE';
    }
    elseif (33.75 <= $wind_value && $wind_value < 56.25) {
      $wind_degree = 'NE';
    }
    elseif (56.25 <= $wind_value && $wind_value < 78.75) {
      $wind_degree = 'ENE';
    }
    elseif (78.75 <= $wind_value && $wind_value < 101.25) {
      $wind_degree = 'S';
    }
    elseif (101.25 <= $wind_value && $wind_value < 123.75) {
      $wind_degree = 'ESE';
    }
    elseif (123.75 <= $wind_value && $wind_value < 146.25) {
      $wind_degree = 'SSE';
    }
    elseif (146.25 <= $wind_value && $wind_value < 168.75) {
      $wind_degree = 'SE';
    }
    elseif (168.75 <= $wind_value && $wind_value < 191.25) {
      $wind_degree = 'S';
    }
    elseif (191.25 <= $wind_value && $wind_value < 213.75) {
      $wind_degree = 'SSW';
    }
    elseif (213.75 <= $wind_value && $wind_value < 236.25) {
      $wind_degree = 'SW';
    }
    elseif (236.25 <= $wind_value && $wind_value < 258.75) {
      $wind_degree = 'WSW';
    }
    elseif (258.75 <= $wind_value && $wind_value < 281.25) {
      $wind_degree = 'W';
    }
    elseif (281.25 <= $wind_value && $wind_value < 303.75) {
      $wind_degree = 'WNW';
    }
    elseif (303.75 <= $wind_value && $wind_value < 326.25) {
      $wind_degree = 'NW';
    }
    elseif (326.25 <= $wind_value && $wind_value < 348.75) {
      $wind_degree = 'NNW';
    }
    elseif ((0 <= $wind_value && $wind_value < 11.25) || (348.75 <= $wind_value && 360 >= $wind_value)) {
      $wind_degree = 'WNW';
    }
    return $wind_degree;
  }

}
