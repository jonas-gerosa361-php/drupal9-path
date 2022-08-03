<?php

namespace Drupal\rsvplist;

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

/**
 * Defines a service for managing RSVP list enabled for nodes.
 */
class EnablerService {

  /**
   * Sets an individual node to be RSVP enabled.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node param.
   */
  public function setEnabled(Node $node) {
    $node_not_enabled = !$this->isEnabled($node);
    if ($node_not_enabled) {
      Database::getConnection()
        ->insert('rsvplist_enabled')
        ->fields(['nid'], [$node->id()])
        ->execute();
    }
  }

  /**
   * Checks if an individual node is RSVP enabled.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node param.
   *
   * @return bool
   *   Whether the node is enabled for the RSVP functionality or not.
   */
  public function isEnabled(Node $node) {
    if ($node->isNew()) {
      return FALSE;
    }

    $results = Database::getConnection()->select('rsvplist_enabled', 're')
      ->fields('re', ['nid'])
      ->condition('nid', $node->id())
      ->execute();

    return !empty($results->fetchCol());
  }

  /**
   * Deletes enabled settings for an individual node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node param.
   */
  public function delEnabled(Node $node) {
    Database::getConnection()->delete('rsvplist_enabled')
      ->condition('nid', $node->id())
      ->execute();
  }

}
