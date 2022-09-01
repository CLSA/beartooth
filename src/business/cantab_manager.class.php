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
  public function __construct( $db_study_phase )
  {
    $setting_manager = lib::create( 'business\setting_manager' );
    $this->db_study_phase = $db_study_phase;
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

    if( $this->get_enabled() ) $this->get_identifiers();
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
          $this->db_study_phase->get_study()->name,
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
          $this->db_study_phase->get_study()->name,
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
            'text' => 'LOE_1', // TODO: do we get this from Opal?
            'locale' => NULL,
            'integer' => NULL,
            'multiText' => NULL,
            'hidesPII' => NULL
          )
        )
      )
    );

    $added = false;
    if( $response )
    {
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
      if( $response ) $added = true;
    }

    return $added;
  }

  /**
   * TODO: document
   */
  protected function get_identifiers()
  {
    // get the organisation ID
    foreach( $this->get( 'organisation?limit=10' )->records as $organisation )
    {
      if( $this->organisation === $organisation->name )
      {
        $this->identifiers['organisation'] = $organisation->id;
        break;
      }
    }

    // get the study ID
    $db_study = $this->db_study_phase->get_study();
    foreach( $this->get( 'study?limit=10' )->records as $study )
    {
      if( $db_study->name === $study->description )
      {
        $this->identifiers['study'] = $study->id;
        break;
      }
    }

    // get the list of all site IDs, studyDef IDs, and groupDef IDs
    $study_def_list = array();
    $object = $this->get( sprintf( 'site?limit=50&filter={"study":"%s"}', $this->identifiers['study'] ) );
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
        $study_def_object = $this->get( sprintf(
          'studyDef?limit=10&filter={"study":"%s"}',
          $this->identifiers['study']
        ) );

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
    try
    {
      if( is_null( $data ) ) $data = new \stdClass;
      $response = $this->send( $api_path, 'POST', $data );
    }
    catch( \cenozo\exception\runtime $e )
    {
      // ignore duplicate errors
      if( false === strpos( $e->get_message(), 'duplicate.subject.id' ) ) throw $e;
    }

    return $response;
  }

  /**
   * Sends curl requests
   * 
   * @param string $api_path The CANTAB endpoint (not including base url)
   * @return curl resource
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

    $code = 0;

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
      throw lib::create( 'exception\runtime',
        sprintf(
          'Got error code %s when trying %s:%s request to CANTAB API.  Message: %s',
          curl_errno( $curl ),
          $method,
          $api_path,
          curl_error( $curl )
        ),
        __METHOD__
      );
    }

    $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
    if( 300 <= $code )
    {
      throw lib::create( 'exception\runtime',
        sprintf(
          'Got response code %s when trying %s:%s request to CANTAB API.  Response %s',
          $code,
          $method,
          $api_path,
          $response
        ),
        __METHOD__
      );
    }

    return util::json_decode( $response );
  }

  /**
   * Which study-phase to interact with in the CANTAB application
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
}
