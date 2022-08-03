<?php

namespace Drupal\rsvplist\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides an 'RSVP' List Block.
 *
 * @Block(
 *   id = "rsvp_block",
 *   admin_label = @Translation("RSVP Block")
 * )
 */
class RSVPBlock extends BlockBase {

  /**
   * {@inheritDoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\rsvplist\Form\RSVPForm');
  }

  /**
   * {@inheritDoc}
   */
  public function blockAccess(AccountInterface $account) {
    $node_not_found = !\Drupal::routeMatch()->getParameter('node');
    if ($node_not_found) {
      return AccessResult::forbidden();
    }

    $enabler = \Drupal::service('rsvplist.enabler');
    $node = \Drupal::routeMatch()->getParameter('node');
    $node_may_collect_rsvp = $enabler->isEnabled($node);
    if ($node_may_collect_rsvp) {
      return AccessResult::allowedIfHasPermission($account, 'view rsvplist');
    }

    return AccessResult::forbidden();
  }

}
