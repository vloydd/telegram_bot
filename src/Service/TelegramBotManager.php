<?php

namespace Drupal\telegram_bot\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\telegram_bot\Commands\UserCommands\CourseCommand;
use Drupal\telegram_bot\Commands\UserCommands\ExchangeCommand;
use Drupal\telegram_bot\Commands\UserCommands\CallbackqueryCommand;
use Drupal\telegram_bot\Commands\UserCommands\CancelCommand;
use Drupal\telegram_bot\Commands\UserCommands\CitySearchCommand;
use Drupal\telegram_bot\Commands\UserCommands\EnterprisesCommand;
use Drupal\telegram_bot\Commands\UserCommands\EnterprisesInfo;
use Drupal\telegram_bot\Commands\UserCommands\FaqCommand;
use Drupal\telegram_bot\Commands\UserCommands\FlightMenuCommand;
use Drupal\telegram_bot\Commands\UserCommands\ForecastCommand;
use Drupal\telegram_bot\Commands\UserCommands\GenericCommand;
use Drupal\telegram_bot\Commands\UserCommands\GenericMessageCommand;
use Drupal\telegram_bot\Commands\UserCommands\HelpCommand;
use Drupal\telegram_bot\Commands\UserCommands\MeInfoCommand;
use Drupal\telegram_bot\Commands\UserCommands\NumberSearchCommand;
use Drupal\telegram_bot\Commands\UserCommands\StartCommand;
use Drupal\telegram_bot\Commands\UserCommands\GetFaqAnswersCommand;
use Drupal\telegram_bot\Commands\UserCommands\GetFaqQuestionsCommand;
use Drupal\telegram_bot\Commands\UserCommands\WeatherCommand;
use Drupal\user\UserInterface;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;

/**
 * This is Our TelegramBotManager, It's Heart.
 */
class TelegramBotManager implements TelegramBotManagerInterface {

  /**
   * Telegram Bot Token.
   *
   * @var mixed
   */
  protected $botToken;

  /**
   * Telegram Bot Username.
   *
   * @var mixed
   */
  protected $botUsername;

  /**
   * Database Host.
   *
   * @var array|mixed|null
   */
  private $host;

  /**
   * Database Data.
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
   * Telegram Administrator.
   *
   * @var array|mixed|null
   */
  private $admin;

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
   * Constructs a New TelegramBotManager Object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactory Interface.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   LoggerChannel Interface.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $config = $config_factory->get('telegram_bot.settings');
    $this->botUsername = $config->get('name');
    $this->botToken = $config->get('token');
    $this->host = $config->get('database_host');
    $this->database = $config->get('database_name');
    $this->user = $config->get('database_user');
    $this->password = $config->get('database_pass');
    $this->admin = $config->get('admin_id');
    $this->connect();
  }

  /**
   * Checks if Our Bot is Alive.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function connect(string $botToken = NULL, string $botUsername = NULL): Telegram {
    // An Telegram Object.
    return new Telegram(
      $this->botToken ?: $botToken,
      $this->botUsername ?: $botUsername
    );
  }

  /**
   * Getter for Bot Api Key.
   *
   * @return array|mixed|null
   *   Returns Api Key.
   */
  public function getApiKey() {
    return $this->botToken;
  }

  /**
   * Getter for Bot Username.
   *
   * @return array|mixed|null
   *   Return Username Value.
   */
  public function getUserName() {
    return $this->botUsername;
  }

  /**
   * Collects Command List and Handles Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function handle() {
    // Connects to bot.
    $telegram = $this->connect();
    // Database configuration.
    // Please, check your Database for every case.
    $mysql = [
      'host'     => $this->host,
      'user'     => $this->user,
      'password' => $this->password,
      'database' => $this->database,
    ];
    // Connecting SQL (needed for conversations).
    $telegram->enableMySql($mysql);
    // Adding our commands path.
    $telegram->addCommandsPath('/www/web/modules/custom/telegram_bot/src/Commands/UserCommands');
    // Add commands classes.
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
      WeatherCommand::class,
      CourseCommand::class,
      ForecastCommand::class,
      MeInfoCommand::class,
    ]);
    // Enabling Telegram admin for features.
    if (!empty($this->admin)) {
      $telegram->enableAdmin($this->admin);
    }
    // Handle Webhook.
    try {
      $telegram->handle();
    }
    catch (TelegramException $e) {
      $this
        ->loggerFactory
        ->get('telegram_bot')
        ->warning(
          "Telegram Bot: There's an Exception: @warning.",
          [
            '@warning' => $e->getMessage(),
          ]
        );
    }
  }

  /**
   * Sets Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function setWebhook():void {
    // Connect to bot.
    $telegram = $this->connect();
    // Set Webhook through route.
    // Adding https, bc Telegram requires HTTPS.
    if (str_contains(\Drupal::request()->getSchemeAndHttpHost(), 'http')) {
      $site_url = str_replace("http", "https", \Drupal::request()
        ->getSchemeAndHttpHost());
    }
    else {
      $site_url = \Drupal::request()->getSchemeAndHttpHost();
    }
    $site_url = $site_url . '/webhook';
    $telegram->setWebhook($site_url);
  }

  /**
   * Deletes Webhook.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   */
  public function deleteWebhook():void {
    // Connect to bot.
    $telegram = $this->connect();
    // Delete webhook.
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
   *   Message Sending Status Sent/Unsent.
   */
  public function sendMessage(string $message, string $chat_id):bool {
    // Connect to Bot.
    $this->connect();
    // Get Chat ID to Send Message.
    $request = ['chat_id' => $chat_id, 'text' => $message];
    // Send Request.
    $result = Request::sendMessage($request);
    // Check if it's OK.
    return $result->isOk();
  }

  /**
   * Generate telegram bot start command with login query params.
   *
   * @param \Drupal\user\UserInterface $account
   *   User Entity.
   *
   * @return string
   *   Returns URL With Start Command.
   */
  public function invitingUrlForUser(UserInterface $account):string {
    // Structure: bot+auth(command)+hash+userid.
    // Hash id working only for one month.
    // To update, you need run cache rebuild(on the site!).
    $datetime = strtotime(date('m-Y', \Drupal::time()->getCurrentTime()));
    return "https://t.me/" . $this->botUsername . "?start=" . user_pass_rehash($account, $datetime) . "-" . $account->id();
  }

}
