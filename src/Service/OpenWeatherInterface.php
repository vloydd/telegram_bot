<?php

namespace Drupal\telegram_bot\Service;

/**
 * This is OpenWeather Interface.
 *
 * @link https://openweathermap.org/api
 *
 * @package Drupal\telegram_bot\Services
 */
interface OpenWeatherInterface {

  /**
   * Getting Weather by CityName.
   *
   * @param string $name
   *   CityName Value.
   *
   * @return false|string|array|void
   *   Returns Response or False.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   HTTP Client Exception.
   */
  public function getWeatherByName(string $name);

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
  public function getWeatherByCoord(string $latitude, string $longitude);

}
