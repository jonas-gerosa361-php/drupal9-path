<?php

namespace Drupal\rsvplist\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an RSVP Email form.
 */
class RSVPForm extends FormBase {

  /**
   * Drupal container.
   *
   * @var \Drupal
   */
  protected $drupalContainer;

  /**
   * Drupal User container.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $user;

  /**
   * Constructor.
   */
  public function __construct(\Drupal $drupalContainer, AccountProxy $user) {
    $this->drupalContainer = $drupalContainer;
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      new \Drupal(),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'rsvplist_email_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = $this->drupalContainer::routeMatch()->getParameter('node');
    $form['email'] = [
      '#title' => $this->t('Email Address'),
      '#type' => 'email',
      '#size' => 25,
      '#description' => $this->t("We'll send updates to the email address you provide."),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('RSVP'),
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $node->id() ? $node->id() : 0,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('email');
    $invalid_email = !$this->drupalContainer::service('email.validator')->isValid($value);
    if ($invalid_email) {
      $form_state->setErrorByName('email', $this->t('The value "%mail" is not a valid email.', ['%mail' => $value]));
      return;
    }

    $node = $this->drupalContainer::routeMatch()->getParameter('node');
    // Check if email already is set for this node.
    $select = Database::getConnection()->select('rsvplist', 'r')
      ->fields('r', ['nid'])
      ->condition('nid', $node->id())
      ->condition('mail', $value)
      ->execute();

    $email_already_subscribed = !empty($select->fetchCol());
    if ($email_already_subscribed) {
      $form_state->setErrorByName('email', $this->t('The address %mail is already subscribed to this list.', [
        '%mail' => $value,
      ]));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->drupalContainer::database()
        ->insert('rsvplist')
        ->fields([
          'mail' => $form_state->getValue('email'),
          'nid' => $form_state->getValue('nid'),
          'uid' => $this->user->id(),
          'created' => time(),
        ])
        ->execute();
      $this->drupalContainer::messenger()->addMessage($this->t('Thank you for registering for this event!'));
    }
    catch (\Exception $e) {
      $this->drupalContainer::logger('warning')->critical($this->t('RSVP List database error: "%error"', ['%error' => $e->getMessage()]));
      $this->drupalContainer::messenger()->addError($this->t('Generic error, please contact our support team.'));
    }
  }

}
