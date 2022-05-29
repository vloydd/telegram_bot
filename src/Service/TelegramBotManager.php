<?php

namespace Drupal\telegram_bot\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\telegram_bot\Commands\UserCommands\ExchangeCommand;
use Drupal\telegram_bot\Commands\UserCommands\CallbackqueryCommand;
use Drupal\telegram_bot\Commands\UserCommands\CancelCommand;
use Drupal\telegram_bot\Commands\UserCommands\CitySearchCommand;
use Drupal\telegram_bot\Commands\UserCommands\EnterprisesCommand;
use Drupal\telegram_bot\Commands\UserCommands\EnterprisesInfo;
use Drupal\telegram_bot\Commands\UserCommands\FaqCommand;
use Drupal\telegram_bot\Commands\UserCommands\FlightMenuCommand;
use Drupal\telegram_bot\Commands\UserCommands\GenericCommand;
use Drupal\telegram_bot\Commands\UserCommands\GenericMessageCommand;
use Drupal\telegram_bot\Commands\UserCommands\HelpCommand;
use Drupal\telegram_bot\Commands\UserCommands\NumberSearchCommand;
use Drupal\telegram_bot\Commands\UserCommands\StartCommand;
use Drupal\telegram_bot\Commands\UserCommands\GetFaqAnswersCommand;
use Drupal\telegram_bot\Commands\UserCommands\GetFaqQuestionsCommand;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;

/**
 * This is Our TelegramBotManager.
 */
class TelegramBotManager implements TelegramBotManagerInterface {

  /**
   * Telegram Bot Token.
   *
   * @var mixed
   */
  protected $bot_token;

  /**
   * Telegram Bot Username.
   *
   * @var mixed
   */
  protected $bot_username;

  /**
   * Database Host.
   *
   * @var array|mixed|null
   */
  private $host;

  /**
   * Database Name.
   *
   * @var array|mixed|null
   */
  private $database;

  /**
   * Database User.
   *
   * @var array|mixed|null
   */
  private $user;

  /**
   * Database pass.
   *
   * @var array|mixed|null
   */
  private $password;

  /**
   * Constructs a New TelegramBotManager Object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $config = $config_factory->get('telegram_bot.settings');
    $this->bot_username = $config->get('name');
    $this->bot_token = $config->get('token');
    $this->host = $config->get('database_host');
    $this->database = $config->get('database_name');
    $this->user = $config->get('database_user');
    $this->password = $config->get('database_pass');

  }

  /**
   * Checks if Our Bot is Alive.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function connect(string $bot_token = NULL, string $bot_username = NULL): Telegram {
    // An Telegram Object.
    $telegram = new Telegram($this->bot_token ?: $bot_token, $this->bot_username ?: $bot_username);
    return $telegram;
  }

  /**
   * Collects Command List and Handles Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function handle():void {
    // Connect to Bot.
    $telegram = $this->connect();
    // Database Configuration.
    // Please, Check Your Database for Every Case.
    $mysql = [
      'host'     => $this->host,
      'user'     => $this->user,
      'password' => $this->password,
      'database' => $this->database,
    ];
    // Connect SQL (Needed for Conversations).
    $telegram->enableMySql($mysql);
    // Add Path to Our Commands.
    $telegram->addCommandsPath('/www/web/modules/custom/telegram_bot/src/Commands/UserCommands');
    // Add Commands Classes.
    $telegram->addCommandClasses([
      StartCommand::class,
      FaqCommand::class,
      EnterprisesCommand::class,
      EnterprisesInfo::class,
      CallbackqueryCommand::class,
      GenericMessageCommand::class,
      GenericCommand::class,
      GetFaqQuestionsCommand::class,
      GetFaqAnswersCommand::class,
      ExchangeCommand::class,
      CancelCommand::class,
      FlightMenuCommand::class,
      CitySearchCommand::class,
      NumberSearchCommand::class,
      HelpCommand::class,
    ]);
    // Handle Webhook.
    $telegram->handle();
  }

  /**
   * Sets Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function setWebhook():void {
    // Connect to Bot.
    $telegram = $this->connect();
    // Set Webhook Through Route.
    $telegram->setWebhook("https://{$_SERVER['HTTP_HOST']}/webhook");
  }

  /**
   * Deletes Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function deleteWebhook():void {
    // Connect to Bot.
    $telegram = $this->connect();
    // Delete Webhook.
    $telegram->deleteWebhook();
  }

  /**
   * Sends Message to Dedicated User.
   *
   * @param string $message
   *   Message to Send.
   * @param string $chat_id
   *   User to Receive.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   *
   * @return bool
   *   Sent/Unsent Message.
   */
  public function sendMessage(string $message, string $chat_id): bool {
    // Connect to Bot.
    $this->connect();
    // Get Chat ID to Send Message.
    $request = ['chat_id' => $chat_id, 'text' => $message];
    // Send Request.
    $result = Request::sendMessage($request);
    // Check if it's OK.
    return $result->isOk();
  }

}
