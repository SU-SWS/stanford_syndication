<?php

namespace Drupal\stanford_syndication;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Syndicator plugin manager.
 */
class SyndicatorPluginManager extends DefaultPluginManager {

  /**
   * Constructs SyndicatorPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Syndicator',
      $namespaces,
      $module_handler,
      'Drupal\stanford_syndication\SyndicatorInterface',
      'Drupal\stanford_syndication\Annotation\Syndicator'
    );
    $this->alterInfo('syndicator_info');
    $this->setCacheBackend($cache_backend, 'syndicator_plugins');
  }

}
