<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\service\appointment_mail;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $modifier->join( 'site', 'appointment_mail.site_id', 'site.id' );
    $modifier->join( 'qnaire', 'appointment_mail.qnaire_id', 'qnaire.id' );
    $modifier->left_join( 'appointment_type', 'appointment_mail.appointment_type_id', 'appointment_type.id' );
    $modifier->join( 'language', 'appointment_mail.language_id', 'language.id' );

    $db_mail_template = $this->get_resource();
    if( !is_null( $db_mail_template ) )
    {
      if( $select->has_column( 'validate' ) ) $select->add_constant( $db_mail_template->validate(), 'validate' );
    }
  }
}
