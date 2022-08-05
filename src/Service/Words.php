<?php

namespace Drupal\telegram_bot\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class for OpenWeatherAPI.
 *
 * @link https://rapidapi.com/dpventures/api/wordsapi
 *
 * @package Drupal\telegram_bot\Services
 */
class Words implements WordsInterface {

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
   * Gets Rhymes to Requested Word.
   *
   * @param string $word
   *   Word We Request.
   *
   * @return array|false
   *   Returns Array Response of False.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getWordsInfo(string $word) {
    $api_key = $this->configFactory
      ->get('telegram_bot.settings')
      ->get('words_api');
    if (!empty($api_key)) {
      try {
        $response = $this->httpClient->request('GET', 'https://wordsapiv1.p.rapidapi.com/words/' . $word, [
          'headers' => [
            'X-RapidAPI-Key' => $api_key,
          ],
        ]);

        $code = $response->getStatusCode();
        if ($code == 200) {
          $result = JSON::decode($response->getBody());
          return $result;
        }
      }
      catch (\Exception $e) {
        $this
          ->loggerFactory
          ->get('words')
          ->warning(
          "WordsAPI: There's an Error for WordsAPI Request(Rhyme): @warning.",
          [
            '@warning' => $e->getMessage(),
          ]
              );
        return FALSE;
      }
    }
  }

}
