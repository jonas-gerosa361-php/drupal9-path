<?php

namespace Drupal\rsvplist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Component\Utility\Html;

/**
 * Controller for RSVP List Report.
 */
class ReportController extends ControllerBase {

  /**
   * Gets all RSVPs for all nodes.
   *
   * @return array
   *   Returns array with all the rsvplist email with name, title and mail.
   */
  protected function load() {
    $select = Database::getConnection()->select('rsvplist', 'r');
    // Join the users table, so we can get the entry creator's username.
    $select->join('users_field_data', 'u', 'r.uid = u.uid');
    // Join the node table, so we can get the event's name.
    $select->join('node_field_data', 'n', 'r.nid = n.nid');

    $select->addField('u', 'name', 'username');
    $select->addField('n', 'title');
    $select->addField('r', 'mail');

    return $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Creates the report page.
   *
   * @return array
   *   Render array for report output.
   */
  public function report() {
    $content = [];
    $content['message'] = [
      '#markup' => $this->t('Below is a list of all Event RSVPs including username, email address and the name of the event they will be attending.'),
    ];

    $headers = [
      $this->t('Name'),
      $this->t('Event'),
      $this->t('Email'),
    ];

    $rows = [];
    foreach ($this->load() as $entry) {
      $object_html = new Html();

      // Sanitize each entry.
      $rows[] = array_map(function ($entry) use ($object_html) {
        return $object_html::escape($entry);
      }, $entry);
    }

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('NO entries available'),
    ];

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;
    return $content;
  }

}
