<?php

namespace Drupal\telegram_bot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Longman\TelegramBot\Exception\TelegramException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Telegram Bot Settings for Our Site.
 */
class TelegramBotAdminSettingsForm extends ConfigFormBase {

  /**
   * The Telegram Bot Manager.
   *
   * @var \Drupal\telegram_bot\Service\TelegramBotManagerInterface
   */
  protected $telegramBotManager;

  /**
   * Config Object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->telegramBotManager = $container->get('telegram_bot.manager');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'telegram_bot_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['telegram_bot.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get our configs.
    $config = $this->configFactory->get('telegram_bot.settings');

    $form['config'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure Telegram Bot'),
      '#open' => TRUE,
    ];
    $form['config']['telegram_bot_name'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('name'),
      '#required' => TRUE,
      '#title' => $this->t('Telegram Bot Username'),
    ];
    $form['config']['telegram_bot_token'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('token'),
      '#required' => TRUE,
      '#title' => $this->t('Telegram Bot Token'),
    ];
    $form['config']['telegram_bot_admin'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('admin_id'),
      '#required' => FALSE,
      '#title' => $this->t('Telegram Bot Admin'),
    ];
    // Set Webhook Input.
    $form['config']['set_webhook'] = [
      '#type' => 'submit',
      '#value' => $this->t('Set Webhook'),
      '#submit' => ['::setWebhook'],
      '#disabled' => !$config->get('token'),
    ];
    // Delete Webhook Input.
    $form['config']['delete_webhook'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Webhook'),
      '#submit' => ['::deleteWebhook'],
      '#disabled' => !$config->get('token'),
    ];
    $form['config']['data'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure Your Database'),
      '#open' => FALSE,
    ];
    $form['config']['data']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#default_value' => $config->get('database_host'),
    ];
    $form['config']['data']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $config->get('database_name'),
    ];
    $form['config']['data']['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#default_value' => $config->get('database_user'),
    ];
    $form['config']['data']['pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('database_pass'),
    ];
    $form['config']['openweather'] = [
      '#type' => 'details',
      '#title' => $this->t('OpenWeather Settings'),
      '#open' => FALSE,
    ];
    $form['config']['openweather']['openweather_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('openweather_api'),
    ];
    $form['config']['openweather']['openweather_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Preferred City'),
      '#default_value' => $config->get('openweather_city'),
    ];
    $form['config']['currency'] = [
      '#type' => 'details',
      '#title' => $this->t('Currency Data Settings'),
      '#open' => FALSE,
    ];
    $form['config']['currency']['currency_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('currency_api'),
    ];
    $form['config']['words'] = [
      '#type' => 'details',
      '#title' => $this->t('WordsAPI Settings'),
      '#open' => FALSE,
    ];
    $form['config']['words']['words_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('words_api'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Sets Webhook for Our Telegram.
   */
  public function setWebhook(): void {
    try {
      $config = $this->config('telegram_bot.settings');
      $this->telegramBotManager->setWebhook();
      // Notifying admin that webhook was set.
      $message = 'The Webhook Was Set on ' . \Drupal::request()
        ->getSchemeAndHttpHost() . '/webhook';
      $this->telegramBotManager->sendMessage($message, $config->get('admin_id'));
      $this->messenger()->addStatus($this->t('Webhook was Set!'));
    }
    catch (TelegramException $e) {
      $this->messenger()->addError($e->getMessage());
    }
  }

  /**
   * Deletes Webhook for Our Telegram.
   */
  public function deleteWebhook(): void {
    try {
      $config = $this->config('telegram_bot.settings');
      $this->telegramBotManager->deleteWebhook();
      $message = 'The Webhook Was Deleted from ' . \Drupal::request()
        ->getSchemeAndHttpHost();
      // Notifying admin that webhook was removed.
      $this->telegramBotManager->sendMessage($message, $config->get('admin_id'));
      $this->messenger()->addStatus($this->t('Webhook was Deleted!'));
    }
    catch (TelegramException $e) {
      $this->messenger()->addError($e->getMessage());
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form values and save it as config.
    $this->config('telegram_bot.settings')
      ->set('name', $form_state->getValue('telegram_bot_name'))
      ->set('token', $form_state->getValue('telegram_bot_token'))
      ->set('admin_id', $form_state->getValue('telegram_bot_admin'))
      ->set('database_host', $form_state->getValue('host'))
      ->set('database_name', $form_state->getValue('name'))
      ->set('database_user', $form_state->getValue('user'))
      ->set('database_pass', $form_state->getValue('pass'))
      ->set('openweather_api', $form_state->getValue('openweather_api'))
      ->set('openweather_city', $form_state->getValue('openweather_city'))
      ->set('currency_api', $form_state->getValue('currency_api'))
      ->set('words_api', $form_state->getValue('words_api'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
