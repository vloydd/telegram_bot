<?php

namespace Drupal\telegram_bot\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class for OpenWeather.
 *
 * @link https://openweathermap.org/api
 *
 * @package Drupal\telegram_bot\Services
 */
class OpenWeather implements OpenWeatherInterface {

  /**
   * GuzzleHttp's Client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * LoggerFactory Object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The ConfigFactory Object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * OpenWeather Constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Http Client Interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   LoggerChannel Interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   LoggerChannel Interface.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * Getting Weather by CityName.
   *
   * @param string $name
   *   CityName Value.
   *
   * @return false|array|mixed
   *   Returns Response or False.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   HTTP Client Exception.
   */
  public function getWeatherByName(string $name) {
    $api_key = $this->configFactory
      ->get('telegram_bot.settings')
      ->get('openweather_api');
    if (!empty($api_key)) {
      try {
        $response = $this->httpClient
          ->request('get', 'https://api.openweathermap.org/data/2.5/weather?q=' . $name . '&units=metric&appid=' . $api_key);

        $code = $response->getStatusCode();
        if ($code == 200) {
          $result = JSON::decode($response->getBody());
          return $result;
        }
      }
      catch (\Exception $e) {
        $this
          ->loggerFactory
          ->get('openweather')
          ->warning(
            "OpenWeather: There's an Error for OpenWeather Request(City): @warning.",
            [
              '@warning' => $e->getMessage(),
            ]
          );
        return FALSE;
      }
    }
  }

  /**
   * Gets Weather by Coordinates.
   *
   * @param string $latitude
   *   Latitude Value.
   * @param string $longitude
   *   Longitude Value.
   *
   * @return false|array|mixed
   *   Returns Response or False.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   HTTP Client Exception.
   */
  public function getWeatherByCoord(string $latitude, string $longitude) {
    $api_key = $this->configFactory
      ->get('telegram_bot.settings')
      ->get('openweather_api');
    if (!empty($api_key)) {
      try {
        $response = $this->httpClient
          ->request('get', 'https://api.openweathermap.org/data/2.5/weather?lat=' . $latitude . '&lon=' . $longitude . '&units=metric&appid=' . $api_key);
        $code = $response->getStatusCode();
        if ($code == 200) {
          $result = JSON::decode($response->getBody());
          return $result;
        }
      }
      catch (\Exception $e) {
        $this
          ->loggerFactory
          ->get('openweather')
          ->warning(
            "OpenWeather: There's an Error for OpenWeather Request(Location): @warning.",
            [
              '@warning' => $e->getMessage(),
            ]
          );
        return FALSE;
      }
    }
  }

  /**
   * Getting Forecast by CityName.
   *
   * @param string $name
   *   CityName Value.
   *
   * @return false|array|mixed
   *   Returns Response or False.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   HTTP Client Exception.
   */
  public function getForecastByName(string $name) {
    $api_key = $this->configFactory
      ->get('telegram_bot.settings')
      ->get('openweather_api');
    if (!empty($api_key)) {
      try {
        $response = $this->httpClient
          ->request('get', 'https://api.openweathermap.org/data/2.5/forecast?q=' . $name . '&units=metric&appid=' . $api_key);
        $code = $response->getStatusCode();
        if ($code == 200) {
          $result = JSON::decode($response->getBody());
          return $result;
        }
      }
      catch (\Exception $e) {
        $this
          ->loggerFactory
          ->get('openweather')
          ->warning(
            "OpenWeather: There's an Error for OpenWeather Request(City): @warning.",
            [
              '@warning' => $e->getMessage(),
            ]
          );
        return FALSE;
      }
    }
  }

  /**
   * Gets Forecast by Coordinates.
   *
   * @param string $latitude
   *   Latitude Value.
   * @param string $longitude
   *   Longitude Value.
   *
   * @return false|array|mixed
   *   Returns Response or False.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   HTTP Client Exception.
   */
  public function getForecastByCoord(string $latitude, string $longitude) {
    $api_key = $this->configFactory
      ->get('telegram_bot.settings')
      ->get('openweather_api');
    if (!empty($api_key)) {
      try {
        $response = $this->httpClient
          ->request('get', 'https://api.openweathermap.org/data/2.5/forecast?lat=' . $latitude . '&lon=' . $longitude . '&units=metric&appid=' . $api_key);
        $code = $response->getStatusCode();
        if ($code == 200) {
          $result = JSON::decode($response->getBody());
          return $result;
        }
      }
      catch (\Exception $e) {
        $this
          ->loggerFactory
          ->get('openweather')
          ->warning(
            "OpenWeather: There's an Error for OpenWeather Request(Location): @warning.",
            [
              '@warning' => $e->getMessage(),
            ]
          );
        return FALSE;
      }
    }
  }

}
