<?php

define("MYSQL_HOST","127.0.0.1");
define("MYSQL_USER","root");
define("MYSQL_PASS","embonds");
define("MYSQL_DB","embonds");
define("AUDIO_FILE_PATH","/Library/WebServer/Documents/embx-a/downloads/");

$cwd = getcwd();

ini_set('upload_tmp_dir',$cwd."../uploads");

// Create connection
//$conn = mysqli_connect($servername, $username, $password,$database);

// Check connection
//if (!$conn) {
//    die("Connection failed: " . mysqli_connect_error());
//} 
//echo "Connected successfully";

//mysql_select_db('embonds',$conn);

class EMBXDB
{
   private static $instance; // stores the MySQLi instance

   private function __construct() { } // block directly instantiating
   private function __clone() { } // block cloning of the object
   public static function get() {
      // create the instance if it does not exist
      if(!isset(self::$instance)) {
         // the MYSQL_* constants should be set to or
         //  replaced with your db connection details
         self::$instance = new MySQLi(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
         if(self::$instance->connect_error) {
            throw new Exception('MySQL connection failed: ' . self::$instance->connect_error);
         }
      }
      // return the instance
      return self::$instance;
   }
}
//
//
//	USAGE: $result = EMBXDB::get()->query("SELECT * FROM ...");	
//
//



?>

