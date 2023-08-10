<?php

namespace Drupal\stanford_syndication\Events;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

class SyndicationEntityActionEvent extends Event implements SyndicationEntityActionEventInterface {

  /**
   * Event constructor.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   * @param string $action
   *   Insert, update, or delete action preformed.
   */
  public function __construct(protected NodeInterface $node, protected string $action) {}

  /**
   * {@inheritDoc}
   */
  public function getNode(): NodeInterface {
    return $this->node;
  }

  /**
   * {@inheritDoc}
   */
  public function getAction(): string {
    return $this->action;
  }

}
