<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * Class for specific command which provide search method by plane number.
 */
class NumberSearchCommand extends UserCommand {

  /**
   * Name for command.
   *
   * @var string
   */
  protected $name = 'number';

  /**
   * Short command description.
   *
   * @var string
   */
  protected $description = 'Number search';

  /**
   * Text for using command in telegram.
   *
   * @var string
   */
  protected $usage = '/number';

  /**
   * Version of command.
   *
   * @var string
   */
  protected $version = '1.0.0';

  /**
   * We point to the use of databases.
   *
   * @var bool
   */
  protected $need_mysql = TRUE;

  /**
   * We point that the command is available for private dialogues.
   *
   * @var bool
   */
  protected $private_only = TRUE;

  /**
   * Conversation Object.
   *
   * @var \Longman\TelegramBot\Conversation
   */
  protected $conversation;

  /**
   * Main command execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Return Server Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    // Getting default variables from response.
    $message = $this->getMessage();
    $chat    = $message->getChat();
    $user    = $message->getFrom();
    $text    = trim($message->getText(TRUE));
    $chat_id = $chat->getId();
    $user_id = $user->getId();
    // Getting values from Flights entity.
    try {
      $storage = \Drupal::entityTypeManager()->getStorage('flights');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
    }
    $query = $storage->getQuery()->execute();
    $flights_entities_array = $storage->loadMultiple($query);
    // Get entity list.
    $entity_list = array_values($flights_entities_array);
    // Construction of the necessary arrays.
    for ($i = 0; $i < count($entity_list); $i++) {
      $flight_status[] = $entity_list[$i]->get('state')->getValue()[0]['value'];
      $flight_number[] = $entity_list[$i]->get('flight_number')->getValue()[0]['value'];
      $flight_time[] = $entity_list[$i]->get('scheduled_time')->getValue()[0]['value'];
      $time = date("H:i", $flight_time[$i]);
      $flight_cities[$entity_list[$i]->get('city')
        ->getValue()[0]['value'] . ' ' . $i] = $flight_number[$i] . ' ' . $flight_status[$i] . ' ' . $time;
    }
    // Preparing response.
    $data = [
      'chat_id'      => $chat_id,
      // Remove any keyboard by default.
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
    $state = $notes['state'] ?? 0;

    $result = Request::emptyResponse();

    // State machine
    // Every time a step is achieved the state is updated.
    switch ($state) {
      case 0:
        if ($text === '') {
          $notes['state'] = 0;
          $this->conversation->update();
          break;
        }
        $text = '';
      case 1:
        if ($text === '' || !in_array($text, $flight_number, TRUE)) {
          $notes['state'] = 1;
          $this->conversation->update();
          $data['text'] = 'Type plane number (you need use full number) :';
          $result = Request::sendMessage($data);
          break;
        }

        $notes['number'] = $text;
        $text            = '';

      case 2:
        $this->conversation->update();
        $number = $notes['number'];
        $data['text'] = 'Sorry,we can`t find your plane, try again :)';
        $m_array = preg_grep("/^$number\s.*/", $flight_cities);
        foreach ($m_array as $k => $v) {
          $out_text .= PHP_EOL . 1 . '.' . ' ' . substr($k, 0, -2) . ' ' . $v;
        }
        if ($out_text) {
          $data['text'] = $out_text;
        }
        $this->conversation->stop();
        $result = Request::sendMessage($data);
        break;
    }

    return $result;
  }

}
