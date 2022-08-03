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
 * Class for Getting Weather Forecast From OpenWeather.
 */
class ForecastCommand extends UserCommand {

  /**
   * Command Name.
   *
   * @var string
   */
  protected $name = 'forecast';

  /**
   * Short Command Description.
   *
   * @var string
   */
  protected $description = 'Forecast By Location and Other Features';

  /**
   * Text for Using Command in Telegram.
   *
   * @var string
   */
  protected $usage = '/forecast';

  /**
   * Version of Command.
   *
   * @var string
   */
  protected $version = '1.0.0';

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
      if ($state == 2) {
        $state--;
      }
      elseif ($state == 3) {
        $state = 1;
      }
    }
    switch ($state) {
      // 1st Step. Getting basic info.
      case 0:
        // For the quick way in which we type '/weather city'.
        if ($text != '' && !in_array($text_lowercase, ['weather', 'your', 'city', 'coord', 'location'])) {
          $weather = \Drupal::service('telegram_bot.openweather')->getForecastByName($text);
          if ($weather) {
            $notes['state'] = 3;
            $notes['dates'] = $this->getDaysAndHours($weather);
            $notes['weather'] = $weather;
            $this->conversation->update();
            $this->formMessagefromRequest($weather);
            $this->sendLocation($chat_id, $weather);
            break;
          }
          else {
            $this->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check That Command is Correct(", 1);
          }
          break;
        }
        // Other cases - let user choose.
        elseif ($text === '' || in_array($text, ['weather', 'Weather'], TRUE)) {
          $notes['state'] = 1;
          $notes['type'] = $text;
          $this->conversation->update();
          $this->buildCurrencyKeyboard('Choose The Type of Weather', 1);
          break;
        }
        // 2nd Step - response by keyboard.
      case 1:
        if ($text === '' || in_array($text, ['Your', 'City', 'Coord', 'Location', 'Get Back'], TRUE)) {
          switch ($text) {
            case 'Location':
              $notes['state'] = 2.1;
              $notes['type'] = 'Location';
              $this->conversation->update();
              $this->buildCurrencyKeyboard('Please, send your Location', 6);
              break 2;

            case 'Your':
              $your_city = \Drupal::config('telegram_bot.settings')->get('openweather_city');
              $weather = \Drupal::service('telegram_bot.openweather')->getForecastByName($your_city);
              if ($weather) {
                $notes['state'] = 3;
                $notes['dates'] = $this->getDaysAndHours($weather);
                $notes['weather'] = $weather;
                $this->conversation->update();
                $this->formMessagefromRequest($weather);
                $this->sendLocation($chat_id, $weather);
              }
              else {
                $this->conversation->stop();
                $this->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check if You Set Up the City in Your Form(", 1);
              }
              break 2;

            case 'City':
              $notes['state'] = 2.2;
              $notes['type'] = 'City';
              $this->conversation->update();
              $this->buildCurrencyKeyboard('Please, Enter City Name', 5);
              break 2;

            case 'Coord':
              $notes['state'] = 2.3;
              $notes['type'] = 'Coord';
              $this->conversation->update();
              $this->buildCurrencyKeyboard('Please, Your Coordinates Example in Format (Latitude, Longitude)', 5);
              break 2;

            default:
              $this->buildCurrencyKeyboard('Please, choose the type of weather', 1);
              $notes['state'] = 1;
              $this->conversation->update();
              break 2;
          }
        }
        $notes['type'] = $text;
        // 3rd step - by sending location.
      case 2.1:
        if ($type == 'location') {
          $loc = $message->getLocation();
          $location = [];
          $location['lat'] = $loc->getLatitude();
          $location['lon'] = $loc->getLongitude();
          $weather = \Drupal::service('telegram_bot.openweather')->getForecastByCoord($location['lat'], $location['lon']);
          if ($weather) {
            $notes['state'] = 3;
            $notes['dates'] = $this->getDaysAndHours($weather);
            $notes['weather'] = $weather;
            $this->conversation->update();
            $this->formMessagefromRequest($weather);
            $this->sendLocation($chat_id, $weather);
          }
          else {
            $this->conversation->stop();
            $this->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check That Command is Correct(", 5);
          }
        }
        else {
          $notes['state'] = 2.1;
          $this->conversation->update();
          $this->buildCurrencyKeyboard('Please, send your Location as marker', 5);
        }
        break;

      // 3rd step - by entering dedicated city.
      case 2.2:
        $text = trim($message->getText(TRUE));
        $weather = \Drupal::service('telegram_bot.openweather')->getForecastByName($text);
        if ($weather) {
          $notes['state'] = 3;
          $notes['dates'] = $this->getDaysAndHours($weather);
          $notes['weather'] = $weather;
          $this->conversation->update();
          $this->formMessagefromRequest($weather);
          $this->sendLocation($chat_id, $weather);
        }
        else {
          $this->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check if You Set Up the Correct City-name(", 5);
        }
        break;

      // 3rd step - by entering coordinates.
      case 2.3:
        $text = trim($message->getText(TRUE));
        $coordinates = explode(", ", $text);
        try {
          $weather = \Drupal::service('telegram_bot.openweather')
            ->getForecastByCoord($coordinates[0], $coordinates[1]);
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
          $notes['state'] = 3;
          $notes['dates'] = $this->getDaysAndHours($weather);
          $notes['weather'] = $weather;
          $this->conversation->update();
          $this->formMessagefromRequest($weather);
          $this->sendLocation($chat_id, $weather);
        }
        else {
          $this->buildCurrencyKeyboard("Sorry, Something Went Wrong, Please, Check That Your Coordinates is Correct(", 5);
        }
        break;

      // 4th step - we've got the day and now time for hours.
      case 3:
        if (empty($notes['day'])) {
          $notes['day'] = $text;
        }
        $keyboard = $this->getKeyboard($notes['dates']['days'][$notes['day']]);
        $notes['state'] = 4;
        $this->conversation->update();
        $this->replyToChat("We've got Your day and choose a hour:", [
          'reply_markup' => $keyboard,
        ]);
        break;

      // 5th step - result by hour.
      case 4:
        $day_values = $notes['dates']['days'][$notes['day']];
        $day_key = array_search($text, $day_values);
        $weather = $notes['weather']['list'][$day_key];
        $this->buildCurrencyKeyboard($this->formMessagefromHour($weather), 4);
        break;

      default:
        $this->conversation->stop();
        $this->buildCurrencyKeyboard('Conversation Finished', 3);
        return $result;
    }
    return $result;
  }

  /**
   * Set up Keyboard with Days or Hours Info.
   *
   * @param array $data
   *   Days/Hours Array.
   *
   * @return \Longman\TelegramBot\Entities\Keyboard
   *   Returns Keyboard Object.
   */
  public function getKeyboard(array $data):Keyboard {
    $keys = array_values($data);
    if (is_string($keys[1])) {
      $keyboard_obj = new Keyboard([$keys[0], $keys[1]]);
      for ($i = 2; $i < count($keys); $i++) {
        if ($i % 2 == 0  && $i != count($keys) - 1) {
          $keyboard_obj->addRow($keys[$i], $keys[$i + 1]);
        }
      }
    }
    else {
      foreach ($data as $key => $value) {
        $keyboard_obj = new Keyboard($value[$key]);
      }
    }
    $keyboard_obj->addRow('Get Back', 'Rollback to Main Page');
    $keyboard = $keyboard_obj
      ->setResizeKeyboard(TRUE)
      ->setOneTimeKeyboard(TRUE)
      ->setSelective(FALSE);
    return $keyboard;
  }

  /**
   * Forms Message by Weather Response by Selected Hour.
   *
   * @param array $weather
   *   Weather Day Response.
   *
   * @return string
   *   Message Itself.
   */
  public function formMessagefromHour(array $weather):string {
    // Getting wind degree.
    $wind_degree = $this->getWindDirection($weather['wind']['deg']);
    // Getting weather emoji.
    $weather_emoji = $this->getEmoji($weather['weather'][0]['icon']);
    // Getting visibility.
    $weather_visibility = (float) $weather['visibility'] / 1000;
    // Convert timestamp into time.
    $requested_date = date("F j, Y, g:i A", $weather['dt']);
    // Forming up message.
    $message = "⚡We've got for {$requested_date}:
    Main:
      {$weather_emoji} {$weather['weather'][0]['main']}({$weather['weather'][0]['description']})
      🌡Current Temperature: {$weather['main']['temp']}°C
      🤒Feels Like: {$weather['main']['temp']}°C
      🌪Wind: {$weather['wind']['speed']}M/S {$wind_degree}
      💧Precipitation Probability: {$weather['pop']}%

    Other Features:
        🌠Visibility: {$weather_visibility}km
        ✨Pressure: {$weather['main']['pressure']}PA
        🌊Sea Level Pressure: {$weather['main']['sea_level']}hPa
        ⛰Ground Level Pressure: {$weather['main']['grnd_level']}hPa
        ☁Clouds: {$weather['clouds']['all']}%
        💦Humidity: {$weather['main']['humidity']}%

    ";
    // If rain is going down.
    if ($weather['rain']) {
      $rain_message = "Rain:
      ☔Rain Volume(Last 3h): {$weather['rain']['3h']}mm
      ";
      $message = $message . $rain_message;
    }
    // If rain is going down.
    if ($weather['snow']) {
      $snow_message = "Snow:
      ❄Snow Volume(Last 3h): {$weather['snow']['3h']}mm
      ";
      $message = $message . $snow_message;
    }
    return $message;
  }

  /**
   * Forms Message by Weather Response.
   *
   * @param array $weather
   *   Weather Response Array.
   *
   * @return string
   *   Message Itself.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function formMessagefromRequest(array $weather):string {
    $dates = $this->getDaysAndHours($weather);
    $days = array_keys($dates['days']);
    $weather_sunrise = date('H:i', $weather['city']['sunrise']);
    $weather_sunset = date('H:i', $weather['city']['sunset']);
    // Forming up message.
    $message = "⚡We've got weather forecast by request: {$weather['name']}
      Main:
          🗺You Are In: {$weather['city']['country']}({$weather['city']['name']})
          🧭Coordinates: ({$weather['city']['coord']['lat']}, {$weather['city']['coord']['lon']})
          👨‍👩‍👦‍👦Population: {$weather['city']['population']}
      Daytime:
         🌄Sunrise(Local time): {$weather_sunrise}
         🌇Sunset(Local time): {$weather_sunset}
        ";
    // Getting keyboard with days.
    $keyboard = $this->getKeyboard($days);
    $this->replyToChat($message, [
      'reply_markup' => $keyboard,
    ]);
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
        break;

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
        'latitude' => $weather['city']['coord']['lat'],
        'longitude' => $weather['city']['coord']['lon'],
      ]);
  }

  /**
   * Forming up Arrays with Dates and Hours.
   *
   * @param array $data
   *   Array with All Date that We Need.
   *
   * @return array
   *   Returns Array with Dates.
   */
  public function getDaysAndHours(array $data):array {
    $dates = $data['list'];
    foreach ($dates as $key => $value) {
      $tm = strtotime($value['dt_txt']);
      $keys['days'][] = date('d.m', $value['dt']);
      $day = date('d.m', $value['dt']);
      $hour = date('H', $value['dt']);
      $prev_date = $key - 1;
      if ($keys['days'][$key] != $keys['days'][$prev_date]) {
        $result['days'][$keys['days'][$key]] = [];
      }
      if (array_key_exists($day, $result['days'])) {
        $result['days'][$keys['days'][$key]][$key] = $hour;
      }
      $result['timestamps'][] = $value['dt'];
    }
    return $result;
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
  public function getEmoji(string $picture_id) {
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
  public function getWindDirection(int $wind_value) {
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
