<?php

namespace Drupal\telegram_bot\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\telegram_bot\Service\TelegramBotManagerInterface;
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
   * Constructor for an Our Form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Factory for Configuration Objects.
   * @param \Drupal\telegram_bot\Service\TelegramBotManagerInterface $telegram_bot_manager
   *   Our Telegram Bot Manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TelegramBotManagerInterface $telegram_bot_manager) {
    parent::__construct($config_factory);
    $this->telegramBotManager = $telegram_bot_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('telegram_bot.manager')
    );
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
    // Get Our Configs.
    $config = $this->config('telegram_bot.settings');

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
    return parent::buildForm($form, $form_state);
  }

  /**
   * Sets Webhook for Our Telegram.
   */
  public function setWebhook(): void {
    try {
      $this->telegramBotManager->setWebhook();
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
      $this->telegramBotManager->deleteWebhook();
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
    // Get Form Values And Seve it as Config.
    $this->config('telegram_bot.settings')
      ->set('name', $form_state->getValue('telegram_bot_name'))
      ->set('token', $form_state->getValue('telegram_bot_token'))
      ->set('database_host', $form_state->getValue('host'))
      ->set('database_name', $form_state->getValue('name'))
      ->set('database_user', $form_state->getValue('user'))
      ->set('database_pass', $form_state->getValue('pass'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
