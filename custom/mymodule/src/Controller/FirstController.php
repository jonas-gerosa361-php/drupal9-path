<?php
/**
 * @file
 *   Controller of my custom module.
 */

namespace Drupal\mymodule\Controller;

use \Drupal\Core\Controller\ControllerBase;

class FirstController extends ControllerBase {
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => t('This is my menu linked custom page.'),
    ];
  }
}
