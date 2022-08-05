<?php

namespace Drupal\telegram_bot\Service;

/**
 * This is WordsAPI Interface.
 *
 * @link https://rapidapi.com/dpventures/api/wordsapi
 *
 * @package Drupal\telegram_bot\Services
 */
interface WordsInterface {

  /**
   * Gets Basic and Advanced Info to Requested Word.
   *
   * @param string $word
   *   Word We Request.
   *
   * @return array|false
   *   Returns Array Response of False.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getWordsInfo(string $word);

}
