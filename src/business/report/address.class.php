<?php
/**
 * address.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\business\report;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Appointment report
 */
class address extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );

    // get the qnaire restrictions
    $db_site = NULL;
    $db_qnaire = NULL;
    foreach( $this->get_restriction_list() as $restriction )
    {
      if( 'site' == $restriction['name'] ) $db_site = lib::create( 'database\site', $restriction['value'] );
      if( 'qnaire' == $restriction['name'] ) $db_qnaire = lib::create( 'database\qnaire', $restriction['value'] );
    }

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'queue_has_participant', 'participant.id', 'queue_has_participant.participant_id' );
    $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
    $modifier->join( 'language', 'participant.language_id', 'language.id' );
    $modifier->join( 'address', 'queue_has_participant.address_id', 'address.id' );
    $modifier->left_join( 'region', 'address.region_id', 'region.id' );
    $modifier->left_join( 'country', 'region.country_id', 'country.id' );
    $modifier->left_join( 'country', 'address.international_country_id', 'icountry.id', 'icountry' );
    $modifier->left_join( 'availability_type', 'participant.availability_type_id', 'availability_type.id' );

    $modifier->where( 'queue_has_participant.site_id', '=', $db_site->id );
    $modifier->where( 'queue_has_participant.qnaire_id', '=', $db_qnaire->id );
    $modifier->where(
      'queue.name',
      'IN',
      ['outside calling time', 'callback', 'new participant', 'old participant']
    );
    $modifier->order( 'participant.uid' );

    $this->apply_restrictions( $modifier );

    $select = lib::create( 'database\select' );
    $select->add_column( 'participant.uid', 'UID', false );
    $select->add_column( 'language.name', 'Language', false );
    $select->add_column( 'IFNULL( availability_type.name, "any" )', 'Availability', false );
    $select->add_column( $this->get_datetime_column( 'participant.callback' ), 'Callback', false );
    $select->add_column( 'participant.global_note', 'Special Note', false );
    $select->add_column(
      'IF( '.
        'international, '.
        'CONCAT_WS( " ", address1, address2, city, international_region, icountry.name, postcode ), '.
        'CONCAT_WS( " ", address1, address2, city, region.name, country.name, postcode )'.
      ')',
      'Address',
      false
    );

    $this->add_table_from_select( NULL, $participant_class_name::select( $select, $modifier ) );
  }
}
