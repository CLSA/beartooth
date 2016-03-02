<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\service\qnaire;
use cenozo\lib, cenozo\log, beartooth\util;

class post extends \cenozo\service\post
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    // repopulate the queues immediately
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::delayed_repopulate();
  }
}
