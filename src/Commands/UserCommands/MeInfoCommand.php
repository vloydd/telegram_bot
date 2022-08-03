<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Longman\TelegramBot\ChatAction;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * Class that Shows Basic Info About User.
 */
class MeInfoCommand extends UserCommand {

  /**
   * Command Name.
   *
   * @var string
   */
  protected $name = 'meinfo';

  /**
   * Command Description.
   *
   * @var string
   */
  protected $description = 'Show users id, name and username';

  /**
   * Usage of the Command.
   *
   * @var string
   */
  protected $usage = '/meinfo';

  /**
   * Command Version.
   *
   * @var string
   */
  protected $version = '1.0.0';

  /**
   * Sets/Unsets Privacy.
   *
   * @var bool
   */
  protected $private_only = FALSE;

  /**
   * Command Execution.
   *
   * @return \Longman\TelegramBot\Entities\ServerResponse
   *   Returns Telegram Response.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function execute(): ServerResponse {
    // Basic data.
    $message    = $this->getMessage();
    $from       = $message->getFrom();
    $user_id    = $from->getId();
    $chat_id    = $message->getChat()->getId();
    $message_id = $message->getMessageId();
    $data       = [
      'chat_id' => $chat_id,
      'reply_to_message_id' => $message_id,
    ];

    // Chat action "typing...".
    Request::sendChatAction([
      'chat_id' => $chat_id,
      'action'  => ChatAction::TYPING,
    ]);

    // User data to be sent.
    $caption = t('Your Id: @id; Name: @name; Username: @username',
      [
        '@id' => $user_id,
        '@name' => $from->getFirstName(),
        '@username' => $from->getUsername(),
      ]);

    // Fetch last profile picture.
    $limit = 1;
    $offset = NULL;
    $user_profile_photos_response = Request::getUserProfilePhotos([
      'user_id' => $user_id,
      'limit'   => $limit,
      'offset'  => $offset,
    ]);
    if ($user_profile_photos_response->isOk()) {
      /** @var \Longman\TelegramBot\Entities\UserProfilePhotos $user_profile_photos */
      $user_profile_photos = $user_profile_photos_response->getResult();
      if ($user_profile_photos->getTotalCount() > 0) {
        $photos = $user_profile_photos->getPhotos();
        $photo = end($photos[0]);
        $file_id = $photo->getFileId();
        $data['photo'] = $file_id;
        $data['caption'] = $caption;
        return Request::sendPhoto($data);
      }
    }
    // Sends Message.
    $data['text'] = $caption;
    return Request::sendMessage($data);
  }

}
