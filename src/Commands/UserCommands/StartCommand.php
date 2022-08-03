<?php

namespace Drupal\telegram_bot\Commands\UserCommands;

use Drupal\user\Entity\User;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

/**
 * Class that Provides Start Command.
 */
class StartCommand extends UserCommand {
  /**
   * Name of the Command.
   *
   * @var string
   */
  protected $name = 'start';

  /**
   * Description of the Command.
   *
   * @var string
   */
  protected $description = 'Start Command';

  /**
   * Usage of This Command.
   *
   * @var string
   */
  protected $usage = '/start';

  /**
   * Version of the Command.
   *
   * @var string
   */
  protected $version = '1.1.0';

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
    // 1st step - get basic data and sends a greetings.
    $message = $this->getMessage();
    $chat_id = $message->getChat()->getId();
    $user_id = $message->getFrom()->getId();
    $sender_nick = $message->getFrom()->getUsername();
    $text = $message->getText(TRUE);

    /**
     * @var \Longman\TelegramBot\Entities\Keyboard[] $keyboards
     */
    $keyboards[] = new Keyboard(
      ['Weather', 'Forecast'],
      ['Course', 'Exchange'],
      ['FAQ', 'Enterprises'],
      ['Help', 'Flight'],
      ['Main Page', 'Visit a Website'],
    );

    // Keyboard building.
    $keyboard = end($keyboards)
      ->setResizeKeyboard(TRUE)
      ->setOneTimeKeyboard(TRUE)
      ->setSelective(FALSE);
    // Greeting message.
    $this->replyToChat(
      "Hello, $sender_nick! Welcome to My Bot \u{1F44B}. It's Great to See You in Our Telegram Bot. What Can I Offer You?
            \nIf You Need Any Help, Please Use Command /help to Check All Commands or Take a Look at Menu on the Left Side\u{1F44C}", [
              'reply_markup' => $keyboard,
            ]);

    // 2nd step - logging on the site.
    // If successfully - set user field value('field_telegram_id'),
    // which was created by installing this module chat_id value.
    // Stores hash data.
    $data = [];
    $result = preg_match('/^(?P<token>.+)-(?P<uid>\d+)$/s', $text, $data);
    // Basically hash.
    $token = $data['token'] ?? NULL;
    // UID of user that wanted to log in.
    $uid = $data['uid'] ?? NULL;
    $site_url = str_replace("http", "https", \Drupal::request()
      ->getSchemeAndHttpHost()) . '/user';
    // There is no hash; just /start command.
    if ($result === FALSE || $token === NULL || $uid === NULL) {
      return Request::emptyResponse();
    }
    // Getting User.
    else {
      $user = User::load($uid);
    }
    // If there is no user with this UID.
    if (!empty($user)) {
      // Checking hashes.
      $datetime = strtotime(date('m-Y', \Drupal::time()->getCurrentTime()));
      $hash_data = user_pass_rehash($user, $datetime);
      // False Hash.
      if (!hash_equals($token, $hash_data)) {
        $user->set('field_telegram_id', $user_id);
        return $this->replyToChat('Sorry, authorisation failed, different tokens');
      }
      // One user - one Telegram account.
      // Getting user with the same field_telegram_id value, and it's uid.
      $same_user = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['field_telegram_id' => $chat_id]);
      $same_user_id = reset($same_user)->get('uid')->value;
      if ($uid != $same_user_id) {
        return $this
          ->replyToChat("Sorry, You already authorised under another account.\n\n$site_url/$uid");
      }
      // 'Successful' result.
      if ($user->get('field_telegram_id')->isEmpty()) {
        $user->set('field_telegram_id', $user_id);
        $user->save();
        return $this->replyToChat(
          "Hello, @$sender_nick! Welcome to My Bot \u{1F44B}. You Successfully Authorised\n\n$site_url/$uid");
      }
      // We logged into telegram already.
      else {
        return $this->replyToChat("Sorry, authorisation failed, because You already authorised
        $site_url");
      }
    }
    else {
      return $this->replyToChat("Sorry, authorisation failed, You aren't user on our site(.
      Please, visit and register...
      $site_url");
    }
  }

}
