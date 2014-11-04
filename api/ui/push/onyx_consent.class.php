<?php
/**
 * onyx_consent.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace beartooth\ui\push;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * push: onyx consent
 * 
 * Allows Onyx to update consent and interview details
 */
class onyx_consent extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'onyx', 'consent', $args );
  }
  
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $participant_class_name = lib::create( 'database\participant' );
    $region_class_name = lib::create( 'database\region' );
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );

    // get the body of the request
    $body = http_get_request_body();
    $data = util::json_decode( $body );

    if( !is_object( $data ) )
      throw lib::create( 'exception\runtime',
        'Unable to decode request body, received: '.print_r( $body, true ), __METHOD__ );

    // loop through the consent array
    foreach( $data->Consent as $consent_list )
    {
      foreach( get_object_vars( $consent_list ) as $uid => $consent_data )
      {
        $object_vars = get_object_vars( $consent_data );

        $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
        if( is_null( $db_participant ) )
          throw lib::create( 'exception\runtime',
            sprintf( 'Participant UID "%s" does not exist.', $uid ), __METHOD__ );

        if( !array_key_exists( 'ConclusiveStatus', $object_vars ) )
          throw lib::create( 'exception\argument',
            'ConclusiveStatus', NULL, __METHOD__ );
        $accept = 'CONSENT' == $consent_data->ConclusiveStatus ? 1 : 0;

        // try timeEnd, if null then try timeStart, if null then use today's date
        $var_name = 'timeEnd';
        if( array_key_exists( 'timeEnd', $object_vars ) &&
            0 < strlen( $consent_data->timeEnd ) )
        {
          $date_obj = util::get_datetime_object( $consent_data->timeEnd );
        }
        else if( array_key_exists( 'timeStart', $object_vars ) &&
                 0 < strlen( $consent_data->timeStart ) )
        {
          $date_obj = util::get_datetime_object( $consent_data->timeStart );
        }
        else
        {
          $date_obj = util::get_datetime_object();
        }
        $date = $date_obj->format( 'Y-m-d' );

        // update the draw blood consent if it is provided
        if( array_key_exists( 'PCF_CSTSAMP_COM', $object_vars ) )
        {
          $db_data_collection = $db_participant->get_data_collection();
          if( is_null( $db_data_collection ) )
          {
            $db_data_collection = lib::create( 'database\data_collection' );
            $db_data_collection->participant_id = $db_participant->id;
          }
          $db_data_collection->draw_blood =
            1 == preg_match( '/y|yes|true|1/i', $consent_data->PCF_CSTSAMP_COM );
          $db_data_collection->save();
        }

        // update the HIN details if any are provided
        if( array_key_exists( 'ADM_NUMB_NB_COM', $object_vars ) ||
            array_key_exists( 'ADM_NUMB_COM', $object_vars ) ||
            array_key_exists( 'ADM_PROV_COM', $object_vars ) ||
            array_key_exists( 'PCF_CSTGVDB_COM', $object_vars ) )
        {
          $db_hin = $db_participant->get_hin();
          if( is_null( $db_hin ) )
          {
            $db_hin = lib::create( 'database\hin' );
            $db_hin->participant_id = $db_participant->id;
          }

          if( array_key_exists( 'ADM_NUMB_NB_COM', $object_vars ) &&
              array_key_exists( 'ADM_NUMB_COM', $object_vars ) &&
              'HEALTH-NUMBER' == $consent_data->ADM_NUMB_COM )
            $db_hin->code = $consent_data->ADM_NUMB_NB_COM;
          
          if( array_key_exists( 'ADM_PROV_COM', $object_vars ) )
          {
            // convert province text to a region
            $province = $consent_data->ADM_PROV_COM;

            $province = 'NEW-FOUNDLAND-LABRADOR' == $province
                      ? 'Newfoundland and Labrador' // special case
                      : ucwords( trim( str_replace( '-', ' ', $province ) ) );
            $db_region = $region_class_name::get_unique_record( 'name', $province );
            if( !is_null( $db_region ) ) $db_hin->region_id = $db_region->id;
          }
          
          if( array_key_exists( 'PCF_CSTGVDB_COM', $object_vars ) )
            $db_hin->access = 1 == preg_match( '/y|yes|true|1/i', $consent_data->PCF_CSTGVDB_COM );

          $db_hin->save();
        }

        // see if this form already exists
        $consent_mod = lib::create( 'database\modifier' );
        $consent_mod->where( 'accept', '=', $accept );
        $consent_mod->where( 'written', '=', 1 );
        $consent_mod->where( 'date', '=', $date );
        if( 0 == $db_participant->get_consent_count( $consent_mod ) )
        {
          $columns = array( 'participant_id' => $db_participant->id,
                            'date' => $date,
                            'accept' => $accept,
                            'written' => 1,
                            'note' => 'Provided by Onyx.' );
          $args = array( 'columns' => $columns );

          if( array_key_exists( 'pdfForm', $object_vars ) )
          { // if a form is included we need to send the request to mastodon
            $args['form'] = $consent_data->pdfForm;
            $mastodon_manager->push( 'consent', 'new', $args );
          }
          else
          {
            try
            {
              $operation = lib::create( 'ui\push\consent_new', $args );
              $operation->process();
            }
            // ignore notice exceptions
            catch( \cenozo\exception\notice $e ) {}
          }
        }
      }
    }
  }
}
