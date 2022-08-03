<?php

namespace Drupal\telegram_bot\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\user\Entity\User;
use Longman\TelegramBot\Exception\TelegramException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Webhook Endpoint.
 */
class TelegramController extends ControllerBase {

  /**
   * The Telegram Bot Manager.
   *
   * @var \Drupal\telegram_bot\Service\TelegramBotManagerInterface
   */
  protected $telegramBotManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->telegramBotManager = $container->get('telegram_bot.manager');
    return $instance;
  }

  /**
   * Handles Telegram Webhooks.
   *
   * @throws \Longman\TelegramBot\Exception\TelegramException
   *
   * @return \Symfony\Component\HttpFoundation\Response|false
   *   Returns Response.
   */
  public function handle(): Response {
    try {
      $this->telegramBotManager->handle();
      return new Response();
    }
    catch (TelegramException $e) {
      \Drupal::logger('telegram_bot')
        ->error(
          "Telegram Bot: There's an Error for Telegram: @error.",
          [
            '@error' => $e->getMessage(),
          ]
        );
      return FALSE;
    }
  }

  /**
   * Telegram Login Redirect.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\Response
   *   Redirects to Telegram Login Link.
   */
  public function userLogin(): TrustedRedirectResponse|Response {
    $user = User::load(\Drupal::currentUser()->id());
    // If user is anon or already logged through telegram.
    if ($user == NULL || !$user->get('field_telegram_id')->isEmpty()) {
      // Super secret redirect(local Easter Egg).
      return new TrustedRedirectResponse(
      'https://www.youtube.com/watch?v=dQw4w9WgXcQ&ab'
      );
      // For other cases: 418(I'm a Teapot) Code.
      // return new Response('', Response::HTTP_I_AM_A_TEAPOT)?
    }
    // Render and redirect on inviting url.
    return new TrustedRedirectResponse(
      $this->telegramBotManager->invitingUrlForUser($user)
    );
  }

}
