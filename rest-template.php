<?php
// get the HTTP method, path and body of the request
$method  = $_SERVER['REQUEST_METHOD'];
$request = explode( '/', trim( $_SERVER['PATH_INFO'], '/' ) );

// Get database details.
// TODO: exit if this file does not exist and spit out the example of how this file should look.
$infile 	= fopen( 'db_env.json', 'r' );
$db_details = json_decode( fread( $infile, filesize( 'db_env.json' ) ) );
fclose( $infile );

// Retrieve the table name and primary key OR fulltext index from the path.
$object 	  = preg_replace( '/[^a-z0-9_]+/i', '', array_shift( $request ) );
$key 		  = array_shift( $request );
$object_query = array_shift( $request );

header( 'Content-Type: application/json' );

// TODO: only add this header if origins are defined in the config.
header( 'Access-Control-Allow-Origin: *' );

// create SQL based on HTTP method
// TODO: Remove the need for the switch here and move function routing to the RestObject
switch ( $method ) {
	case 'GET':
		// Construct db objects.
		$table2 		= new Table2( $db_details, $key );
		$table1 		= new Table1( $db_details, $key );

		if ( empty( $object_query ) ) {
			$result = $$object->get();
			echo $result;
			exit();
		} else {
			$result = $$object->$object_query();
			echo json_encode( $result );
			exit();
		}

		break;
	case 'POST':
		echo mysqli_insert_id( $link );
		//   $sql = "insert into `$table` set $set"; break;
	case 'PUT':
		//   $sql = "update `$table` set $set where id=$key"; break;
	case 'DELETE':
		echo mysqli_affected_rows( $link );
	default:
		http_response_code( 400 );
		exit( "Only GET requests allowed here." );
}

// excecute SQL statement
$result = mysqli_query( $link, $sql );

// die if SQL statement failed
if ( ! $result ) {
	http_response_code( 404 );
	die( mysqli_error() );
}

abstract class RestObject{
	protected $db_link;
	protected $table_name;
	protected $fields;
	protected $id = null;
	protected $name_field = null;
	protected $name = null;

	/**
	 * Abstract function to initialize a class based on which object they represent.
	 */
	abstract protected function init();

	/**
	 * Constructor
	 *
	 * @param $db_link 	  [type] description.
	 * @param $id_or_name Mixed description.
	 * @param $operation  String
	 */
	function __construct( $db_details, $id_or_name=null, $operation='GET' ) {
		// connect to the mysql database
		$this->db_details = $db_details;
		$this->init();

		// Set the given id or name.
		if ( ! empty( $id_or_name ) ) {
			// If this is an int, we have a row id, otherwise it's a string index.
			if ( is_numeric($id_or_name) ) {
				$this->id = (int)$id_or_name;
			} else {
				$this->name = mysqli_real_escape_string( $this->db_link, $id_or_name );
			}
		}
	}

	/**
	 * [description].
	 *
	 * @param String $field The name of the field to get.
	 */
	function query_field( $field ) {
		$result = mysqli_real_escape_string( $this->get_db_link(), $_GET[ $field ] );
		return $result;
	}

	/**
	 * [description]
	 */
	function execute_operation() {
		switch ( $this->operation ) {
			case 'GET':
				return $this->get();
				break;
			case 'POST':
				return $this->insert();
				break;
			case 'PUT':
				return $this->update();
				break;
			case 'DELETE':
				return $this->delete();
				break;
		}
	}

	/**
	 * [description].
	 */
	function get() {
		$get_query = "SELECT * FROM {$this->table_name} ";
		if ( ! empty( $this->id ) ) {
			$get_query .= " WHERE id='" . $this->id . "'";
		} elseif ( ! empty( $this->name ) ) {
			$get_query .= " WHERE {$this->name_field}='" . $this->name . "'";
		}

		return $this->dump( $get_query );
	}

	/**
	 * [Description]
	 */
	function insert() {
		// TODO:
	}

	/**
	 * [Description]
	 */
	function update() {
		// TODO:
	}

	/**
	 * [Description]
	 */
	function delete() {
		// TODO:
	}

	/**
	 * [description].
	 *
	 * @param String $get_query The query to execute.
	 */
	function dump( $get_query ) {
		$query_result = mysqli_query( $this->get_db_link(), $get_query );

		$result = '[';
		for ( $i = 0; $i < mysqli_num_rows( $query_result ); $i++ ) {
			$result .= ( $i > 0 ? ',' : '' ) . json_encode( mysqli_fetch_object( $query_result ) );
		}

		$result .= ']';
		return $result;
	}

	/**
	 * [description]
	 *
	 * @param String $query the query to execute on the db.
	 */
	function get_query_as_array( $query ) {
		$this->db_link = mysqli_connect( $this->db_details->db_host, $this->db_details->db_user, $this->db_details->db_password, $this->db_details->db_name );
		mysqli_set_charset( $this->db_link, 'utf8' );
		$query_result = mysqli_query( $this->db_link, $query );

		$result = [];
		// $result = mysqli_fetch_all( $query_result );
		while ( $row = $query_result->fetch_assoc() ) {
			$result[] = $row;
		}

		mysqli_close( $this->db_link );
		return $result;
	}

	/**
	 * [description].
	 */
	function get_db_link() {
		$this->db_link = mysqli_connect( $this->db_details->db_host, $this->db_details->db_user, $this->db_details->db_password, $this->db_details->db_name );
		mysqli_set_charset( $this->db_link, 'utf8' );
		return $this->db_link;
	}

	/**
	 * [description].
	 */
	function close_link() {
		// Close mysql connection.
		mysqli_close( $this->db_link );
	}
}

class Table1 extends RestObject{
	function init() {
		$this->table_name="table1";
		$this->fields=['id', 'type', 'description'];
		$this->name_field='type';
	}
}

class Table2 extends RestObject{
	function init() {
		$this->table_name="table2";
		$this->fields=['id', 'name', 'value'];
	}

	/**
	* Override the default function to apply any list of filters here
	*/
	function get(){
		// TODO: addmovie, cinema, show_type, date
	}
} ?>

