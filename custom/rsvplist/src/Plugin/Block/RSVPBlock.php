<?php

namespace Drupal\rsvplist\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'RSVP' List Block.
 *
 * @Block(
 *   id = "rsvp_block",
 *   admin_label = @Translation("RSVP Block")
 * )
 */
class RSVPBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal container.
   *
   * @var \Drupal
   */
  protected $drupalContainer;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal $drupalContainer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->drupalContainer = $drupalContainer;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      new \Drupal(),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    return $this->drupalContainer::formBuilder()->getForm('Drupal\rsvplist\Form\RSVPForm');
  }

  /**
   * {@inheritDoc}
   */
  public function blockAccess(AccountInterface $account) {
    $node_not_found = !$this->drupalContainer::routeMatch()->getParameter('node');
    if ($node_not_found) {
      return AccessResult::forbidden();
    }

    $enabler = $this->drupalContainer::service('rsvplist.enabler');
    $node = $this->drupalContainer::routeMatch()->getParameter('node');
    $node_may_collect_rsvp = $enabler->isEnabled($node);
    if ($node_may_collect_rsvp) {
      return AccessResult::allowedIfHasPermission($account, 'view rsvplist');
    }

    return AccessResult::forbidden();
  }

}
