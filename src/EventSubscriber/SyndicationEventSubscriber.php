<?php

namespace Drupal\stanford_syndication\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\stanford_syndication\Events\SyndicationEntityActionEvent;
use Drupal\stanford_syndication\Events\SyndicationEvents;
use Drupal\stanford_syndication\SyndicatorPluginManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Stanford Syndication event subscriber.
 */
class SyndicationEventSubscriber implements EventSubscriberInterface {

  /**
   * Module config settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $syndicationConfig;

  /**
   * Logger channel service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Syndicator plugin manager.
   *
   * @var \Drupal\stanford_syndication\SyndicatorPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs the event subscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory, SyndicatorPluginManager $pluginManager) {
    $this->syndicationConfig = $configFactory->get('stanford_syndication.settings');
    $this->logger = $loggerFactory->get('stanford_syndication');
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [SyndicationEvents::ENTITY_ACTION => ['onEntityAction']];
  }

  /**
   * Syndication action event listener.
   *
   * @param \Drupal\stanford_syndication\Events\SyndicationEntityActionEvent $event
   *   Triggered event.
   */
  public function onEntityAction(SyndicationEntityActionEvent $event): void {
    $plugin_defs = $this->pluginManager->getDefinitions();
    foreach (array_keys($plugin_defs) as $plugin_id) {
      try {
        /** @var \Drupal\stanford_syndication\SyndicatorInterface $plugin */
        $plugin = $this->pluginManager->createInstance($plugin_id, $this->syndicationConfig->get("syndicators.$plugin_id") ?? []);
        switch ($event->getAction()) {
          case SyndicationEntityActionEvent::INSERT_ACTION:
            $plugin->insert($event->getNode());
            break;

          case SyndicationEntityActionEvent::UPDATE_ACTION:
            $plugin->update($event->getNode());
            break;

          default:
            $plugin->delete($event->getNode());
            break;
        }
      }
      catch (\Throwable $e) {
        $this->logger->error('An error occurred syndicating content: ' . $e->getMessage());
      }
    }
  }

}
