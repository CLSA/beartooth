<?php
/**
 * cantab_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace beartooth\business;
use cenozo\lib, cenozo\log, beartooth\util;

/**
 * Manages communication with the CANTAB API
 */
class cantab_manager extends \cenozo\base_object
{
  /**
   * Constructor.
   * 
   * @param database\study The study
   * @access public
   */
  public function __construct()
  {
    $setting_manager = lib::create( 'business\setting_manager' );
    $this->enabled = $setting_manager->get_setting( 'cantab', 'enabled' );
    $this->url = $setting_manager->get_setting( 'cantab', 'url' );
    $this->username = $setting_manager->get_setting( 'cantab', 'username' );
    $this->password = $setting_manager->get_setting( 'cantab', 'password' );
    $this->organisation = $setting_manager->get_setting( 'cantab', 'organisation' );
    $this->identifiers = array(
      'organisation' => NULL,
      'study' => NULL,
      'site_list' => array()
    );

    if( $this->get_enabled() )
    {
      $study_class_name = lib::get_class_name( 'database\study' );
      $study_phase_class_name = lib::get_class_name( 'database\study_phase' );

      $study_name = $setting_manager->get_setting( 'cantab', 'study_name' );
      $study_phase_name = $setting_manager->get_setting( 'cantab', 'study_phase_name' );

      $this->db_study = $study_class_name::get_unique_record( 'name', $study_name );
      if( is_null( $this->db_study ) )
      {
        throw lib::create( 'exception\runtime',
          sprintf( 'CANTAB manager has been enabled with invalid study name "%s".', $study_name ),
          __METHOD__
        );
      }

      $this->db_study_phase = $study_phase_class_name::get_unique_record(
        array( 'study_id', 'name' ),
        array( $this->db_study->id, $study_phase_name )
      );
      if( is_null( $this->db_study_phase ) )
      {
        throw lib::create( 'exception\runtime',
          sprintf( 'CANTAB manager has been enabled with invalid study phase name "%s".', $study_phase_name ),
          __METHOD__
        );
      }

      $this->get_identifiers();
    }
  }

  /**
   * Determines whether the CANTAB API is enabled
   * @return boolean
   * @access public
   */
  public function get_enabled()
  {
    return $this->enabled;
  }

  /**
   * Add a participant's details to the CANTAB application
   * @param database\participant $db_participant
   * @access public
   */
  public function add_participant( $db_participant )
  {
    $participant_identifier_class_name = lib::get_class_name( 'database\participant_identifier' );

    $db_identifier = $this->db_study_phase->get_identifier();
    if( is_null( $db_identifier ) )
    {
      throw lib::create( 'exception\runtime',
        sprintf(
          'Tried to add participant %s to CANTAB for study phase "%s %s" which has no identifier.',
          $db_participant->uid,
          $this->db_study->name,
          $this->db_study_phase->name
        ),
        __METHOD__
      );
    }

    // determine the participant's study ID
    $db_participant_identifier = $participant_identifier_class_name::get_unique_record(
      array( 'identifier_id', 'participant_id' ),
      array( $this->db_study_phase->get_identifier()->id, $db_participant->id )
    );
    if( is_null( $db_participant_identifier ) )
    {
      throw lib::create( 'exception\runtime',
        sprintf(
          'Tried to add participant %s to CANTAB for study phase "%s %s" but the participant has no identifier.',
          $db_participant->uid,
          $this->db_study->name,
          $this->db_study_phase->name
        ),
        __METHOD__
      );
    }

    $db_site = $db_participant->get_effective_site();
    if( is_null( $db_site ) )
    {
      throw lib::create( 'exception\runtime',
        sprintf(
          'Tried to add participant %s to CANTAB but participant has no site',
          $db_participant->uid
        ),
        __METHOD__
      );
    }
    else if( !array_key_exists( $db_site->name, $this->identifiers['site_list'] ) )
    {
      throw lib::create( 'exception\runtime',
        sprintf(
          'Tried to add participant %s to CANTAB but no identifiers found for site "%s"',
          $db_participant->uid,
          $db_site->name
        ),
        __METHOD__
      );
    }

    $site_identifiers = $this->identifiers['site_list'][$db_site->name];

    // get the participant's level of education from opal
    $data_manager = lib::create( 'business\data_manager' );
    $loe = $data_manager->get_participant_value(
      $db_participant,
      'participant.opal.clsa-sac-releases.60minQ_CoPv7_Baseline.ED_UDR11_COM'
    );

    // The loe in opal is defined as follows:
    // 1) grade 8 or lower
    // 2) grade 9-10 (age 14-15)
    // 3) grade 11-13 (age 16-18)
    // 4) secondary school graduate
    // 5) some post secondary education
    // 6) trade certificate or diploma
    // 7) non-university certificate or diploma
    // 8) university certificate below batchelor
    // 9) batchelor's degree
    // 10) university degree above batchelor
    // 11) other post secondary education
    // 99) required question(s) not answered (not available)

    // The loe in CANTAB is defined as follows:
    // LOE_1: left formal education before age 16
    // LOE_2: left formal education at age 16
    // LOE_3: left formal education at age 17-18
    // LOE_4: undergraduate degree or equivalent
    // LOE_5: master's degree or equivalent
    // LOE_6: PhD or equivalent

    $cantab_loe = NULL;
    if( 1 == $loe || 2 == $loe ) $cantab_loe = 'LOE_1';
    else if( ( 3 <= $loe && $loe <= 8 ) || 11 == $loe ) $cantab_loe = 'LOE_3';
    else if( 9 == $loe ) $cantab_loe = 'LOE_4';
    else if( 10 == $loe ) $cantab_loe = 'LOE_5';

    // post the participant's details
    $response = $this->post(
      'subject',
      array(
        'subjectIds' => [ $db_participant_identifier->value ],
        'organisation' => $this->identifiers['organisation'],
        'study' => $this->identifiers['study'],
        'site' => $site_identifiers['id'],
        'studyDef' => $site_identifiers['study_def_id'],
        'groupDef' => $site_identifiers['group_def_id'],
        'status' => 'NEW',
        'subjectItems' => array(
          array(
            'subjectItemDef' => $site_identifiers['item_def_list']['Language'],
            'date' => NULL,
            'text' => NULL,
            'locale' => 'en' == $db_participant->get_language()->code ? 'en-US' : 'fr-CA',
            'integer' => NULL,
            'multiText' => NULL,
            'hidesPII' => NULL
          ),
          array(
            'subjectItemDef' => $site_identifiers['item_def_list']['Date of Birth'],
            'date' => $db_participant->date_of_birth->format( 'Y-m-d' ),
            'text' => NULL,
            'locale' => NULL,
            'integer' => NULL,
            'multiText' => NULL,
            'hidesPII' => NULL
          ),
          array(
            'subjectItemDef' => $site_identifiers['item_def_list']['Gender at Birth'],
            'date' => NULL,
            'text' => 'male' == $db_participant->sex ? 'M' : 'F',
            'locale' => NULL,
            'integer' => NULL,
            'multiText' => NULL,
            'hidesPII' => NULL
          ),
          array(
            'subjectItemDef' => $site_identifiers['item_def_list']['Level of Education'],
            'date' => NULL,
            'text' => $cantab_loe,
            'locale' => NULL,
            'integer' => NULL,
            'multiText' => NULL,
            'hidesPII' => NULL
          )
        )
      )
    );

    // ignore duplicate errors
    if( false === $response &&
        400 == $this->last_api_code &&
        strpos( $this->last_error_message, 'duplicate.subject.id' ) )
    {
      // the participant already exists
      return false;
    }
    else
    {
      // throw a notice if there is an error
      $this->on_response_error( $response, 'Unable to send participant data to CANTAB service.' );
    }

    $subject = current( $response->records );

    // post the stimuli
    $response = $this->post(
      'stimuliAllocation',
      array(
        'subject' => $subject->id,
        'clientId' => NULL,
        'version' => 0,
        'allocations' => NULL,
        // note that org, study, site, etc, do not need to be provided
        'organisation' => NULL,
        'study' => NULL,
        'site' => NULL,
        'studyDef' => NULL,
        'groupDef' => NULL
      )
    );

    // throw a notice if there is an error
    $this->on_response_error( $response, 'Unable to load stimuli allocation data to CANTAB service.' );

    return true;
  }

  /**
   * Gets all CANTAB identifiers from the API to be used when adding new participants
   */
  protected function get_identifiers()
  {
    // get the organisation ID (throws a notice if there is an error)
    $response = $this->get( 'organisation?limit=10' );
    $this->on_response_error( $response, 'Unable to get organisation data from CANTAB service.' );
    foreach( $response->records as $organisation )
    {
      if( $this->organisation === $organisation->name )
      {
        $this->identifiers['organisation'] = $organisation->id;
        break;
      }
    }

    // get the study ID (throws a notice if there is an error)
    $response = $this->get( 'study?limit=10' );
    $this->on_response_error( $response, 'Unable to get study data from CANTAB service.' );

    foreach( $response->records as $study )
    {
      if( $this->db_study->name === $study->description )
      {
        $this->identifiers['study'] = $study->id;
        break;
      }
    }

    // get the list of all site IDs, studyDef IDs, and groupDef IDs (throws a notice if there is an error)
    $study_def_list = array();
    $object = $this->get( sprintf( 'site?limit=50&filter={"study":"%s"}', $this->identifiers['study'] ) );
    $this->on_response_error( $object, 'Unable to get site data from CANTAB service.' );

    foreach( $object->records as $site )
    {
      $this->identifiers['site_list'][$site->name] = array(
        'id' => $site->id,
        'study_def_id' => $site->activeStudyDef,
        'group_def_id' => NULL,
        'item_def_list' => array()
      );

      // the study/group def IDs are likely the same for all sites, so use a cache
      if( !array_key_exists( $site->activeStudyDef, $study_def_list ) )
      {
        // get study/group def IDs (throws a notice if there is an error)
        $study_def_object = $this->get( sprintf(
          'studyDef?limit=10&filter={"study":"%s"}',
          $this->identifiers['study']
        ) );
        $this->on_response_error( $study_def_object, 'Unable to get study definition data from CANTAB service.' );

        foreach( $study_def_object->records as $study_def )
        {
          $group_def = current( $study_def->groupDefs );
          $subject_data_def = $study_def->subjectDataDef;

          if( $group_def && $subject_data_def )
          {
            $study_def_list[$site->activeStudyDef] = array(
              'group_def_id' => $group_def->id,
              'item_def_list' => array()
            );
            foreach( $subject_data_def->subjectItemDefs as $item )
              $study_def_list[$site->activeStudyDef]['item_def_list'][$item->label] = $item->id;
            break;
          }
        }
      }

      if( array_key_exists( $site->activeStudyDef, $study_def_list ) )
      {
        $this->identifiers['site_list'][$site->name]['group_def_id'] =
          $study_def_list[$site->activeStudyDef]['group_def_id'];
        $this->identifiers['site_list'][$site->name]['item_def_list'] =
          $study_def_list[$site->activeStudyDef]['item_def_list'];
      }
    }
  }

  /**
   * 
   */
  protected function on_response_error( $response, $message )
  {
    if( false === $response )
    {
      throw lib::create( 'exception\notice',
        sprintf(
          '%s%s%s',
          $message,
          false === strpos( $this->last_error_message, "\n" ) ?
            sprintf( "\nServer responded with: \"%s\"", $this->last_error_message ) : '',
          $this->last_api_code ?
            sprintf( "\nResponse code: %s", $this->last_api_code ) : ''
        ),
        __METHOD__
      );
    }
  }

  /**
   * Sends a curl GET request to the CANTAB application
   * 
   * @param string $api_path The CANTAB endpoint (not including base url)
   * @return curl resource
   * @access protected
   */
  protected function get( $api_path )
  {
    return $this->send( $api_path );
  }

  /**
   * Sends a curl POST request to the CANTAB application
   * 
   * @param string $api_path The CANTAB endpoint (not including base url)
   * @param string $data The data to post to the application
   * @return curl resource
   * @access protected
   */
  protected function post( $api_path, $data = NULL )
  {
    $response = false;

    if( is_null( $data ) ) $data = new \stdClass;
    return $this->send( $api_path, 'POST', $data );
  }

  /**
   * Sends curl requests
   * 
   * @param string $api_path The CANTAB endpoint (not including base url)
   * @return curl resource (or false if there was an error)
   * @access public
   */
  private function send( $api_path, $method = 'GET', $data = NULL )
  {
    if( !$this->get_enabled() ) return NULL;

    $header_list = array(
      sprintf(
        'Authorization: Basic %s',
        base64_encode( sprintf( '%s:%s', $this->username, $this->password ) )
      ),
      'Accept: application/json'
    );

    $this->last_api_code = NULL;
    $this->last_error_message = NULL;

    // prepare cURL request
    $url = sprintf( '%s/%s', $this->url, $api_path );

    // set URL and other appropriate options
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_URL, $url );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, $this->timeout );
    if( 'POST' == $method ) curl_setopt( $curl, CURLOPT_POST, true );

    if( !is_null( $data ) )
    {
      $header_list[] = 'Content-Type: application/json';
      curl_setopt( $curl, CURLOPT_POSTFIELDS, util::json_encode( $data ) );
    }

    curl_setopt( $curl, CURLOPT_HTTPHEADER, $header_list );

    $response = curl_exec( $curl );
    if( curl_errno( $curl ) )
    {
      $this->last_error_message = curl_error( $curl );
      return false;
    }

    $this->last_api_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
    if( 300 <= $this->last_api_code )
    {
      $this->last_error_message = $response;
      return false;
    }

    return util::json_decode( $response );
  }

  /**
   * Which study to interact with in the CANTAB application
   * @var database\study_phase $db_study
   * @access protected
   */
  protected $db_study = NULL;

  /**
   * Which study-phase to get the identifier type from
   * @var database\study_phase $db_study_phase
   * @access protected
   */
  protected $db_study_phase = NULL;

  /**
   * Whether to use the CANTAB API
   * @var string
   * @access protected
   */
  protected $enabled = NULL;

  /**
   * The base URL to the CANTAB API
   * @var string
   * @access protected
   */
  protected $url = NULL;

  /**
   * The API username
   * @var string
   * @access protected
   */
  protected $username = NULL;

  /**
   * The API password
   * @var string
   * @access protected
   */
  protected $password = NULL;

  /**
   * The organisation name registered in the CANTAB application
   * @var string
   * @access protected
   */
  protected $organisation = NULL;

  /**
   * The number of seconds to wait before giving up on connecting to the application
   * @var integer
   * @access protected
   */
  protected $timeout = 5;

  /**
   * An array of all identifiers used by the API
   * @var array
   * @access protected
   */
  protected $identifiers = NULL;

  /**
   * The HTML response code resulting from the last call to the CANTAB API
   * @var integer
   * @access private
   */
  private $last_api_code = NULL;

  /**
   * The error message from the last call to the CANTAB API
   * @var string
   * @access private
   */
  private $last_error_message = NULL;
}
