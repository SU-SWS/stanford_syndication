<?php

namespace Drupal\stanford_syndication;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for syndicator plugins.
 */
interface SyndicatorInterface extends PluginFormInterface, PluginInspectionInterface, ConfigurableInterface {

  /**
   * Returns the label for the plugin.
   *
   * @return string
   *   The plugin label.
   */
  public function label(): string;

  /**
   * Act on the insert of a new node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   */
  public function insert(NodeInterface $node): void;

  /**
   * Act on the update of a new node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   */
  public function update(NodeInterface $node): void;

  /**
   * Act on the deletion of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   */
  public function delete(NodeInterface $node): void;

}
