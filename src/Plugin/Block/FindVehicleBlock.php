<?php

namespace Drupal\findvehicle\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Help Form Block' block.
 *
 * @Block(
 *   id = "findvehicle_block",
 *   admin_label = @Translation("Find Your Vehicle"),
 * )
 */
class FindVehicleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form = \Drupal::formBuilder()->getForm('Drupal\findvehicle\Form\FindVehicleForm');
    return $form;
  }

}
