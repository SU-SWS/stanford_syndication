<?php

namespace Drupal\stanford_syndication\Events;

use Drupal\node\NodeInterface;

interface SyndicationEntityActionEventInterface {

  const INSERT_ACTION = 'action';

  const UPDATE_ACTION = 'update';

  const DELETE_ACTION = 'delete';

  /**
   * Node object during CRUD.
   *
   * @return \Drupal\node\NodeInterface
   */
  public function getNode(): NodeInterface;

  /**
   * Insert, update or delete action.
   *
   * @return string
   */
  public function getAction(): string;

}
