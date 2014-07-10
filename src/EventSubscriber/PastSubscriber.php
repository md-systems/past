<?php
namespace Drupal\past\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PastSubscriber implements EventSubscriberInterface {

  public function checkForRedirection(GetResponseEvent $event) {
    if ($event->getRequest()->query->get('redirect-me')) {
      $event->setResponse(new RedirectResponse('http://example.com/'));
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('registerExceptionHandler');
    $events[KernelEvents::REQUEST][] = array('registerShutdownFunction');
    return $events;
  }

  public function registerShutdownFunction(GetResponseEvent $event) {
    drupal_register_shutdown_function('_past_shutdown_function');
  }

  public function registerExceptionHandler(GetResponseEvent $event) {
    if (config('past.settings')->get('past_exception_handling')) {
      set_exception_handler('_past_exception_handler');
    }
  }

}
