<?php
/**
 * @file
 *   Form for RSVP List module.
 */
namespace Drupal\rsvplist\Form;

use \Drupal\Core\Database\Database;
use \Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an RSVP Email form.
 */
class RSVPForm extends FormBase {
  /**
   * (@inheritDoc)
   */
  public function getFormId() {
    return 'rsvplist_email_form';
  }

  /**
   * (@inheritDoc)
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = \Drupal::routeMatch()->getParameter('node');
    $form['email'] = [
      '#title' => t('Email Address'),
      '#type' => 'email',
      '#size' => 25,
      '#description' => t("We'll send updates to the email address you provide."),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('RSVP'),
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $node->id() ? $node->id() : 0,
    ];
    return $form;
  }

  /**
   * (@inheritDoc)
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('email');
    $invalid_email = !\Drupal::service('email.validator')->isValid($value);
    if ($invalid_email) {
      $form_state->setErrorByName('email', t('The value "%mail" is not a valid email.', ['%mail' => $value]));
      return;
    }

    $node = \Drupal::routeMatch()->getParameter('node');
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
   * (@inheritDoc)
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      \Drupal::database()
        ->insert('rsvplist')
        ->fields([
          'mail' => $form_state->getValue('email'),
          'nid' => $form_state->getValue('nid'),
          'uid' => $user->id(),
          'created' => time(),
        ])
        ->execute();
      \Drupal::messenger()->addMessage(t('Thank you for registering for this event!'));
    } catch (\Exception $e) {
      \Drupal::logger('warning')->critical(t('RSVP List database error: "%error"', ['%error' => $e->getMessage()]));
      \Drupal::messenger()->addError(t('Generic error, please contact our support team.'));
    }
  }
}
