<?php
/**
 * cenozo_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package cenozo\business
 * @filesource
 */

namespace beartooth\business;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Extends Cenozo's manager with custom methods
 * 
 * @package beartooth\business
 */
class cenozo_manager extends \cenozo\business\cenozo_manager
{
  /**
   * Override the parent method to specify comprehensive as the cohort.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array& $arguments
   * @access protected
   */
  protected function set_site_and_role( &$arguments )
  {
    $session = lib::create( 'business\session' );
    $arguments['request_site.name'] = 'comprehensive////'.$session->get_site()->name;
    $arguments['request_site.role'] = $session->get_role()->name;
  }
}
