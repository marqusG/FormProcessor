<?php
/**
 * PDO Singleton Class v.1.0
 *
 * @author Ademílson F. Tonato
 * @link https://twitter.com/ftonato
 *
 */
class DbConnection
{

    protected static $instance;

    protected function __construct()
    {}

    public static function getInstance()
    {

        if (empty(self::$instance)) {

            $config = require 'config.php';
            $dsn = $config['database']['dbDriver'] . ':dbname=' . $config['database']['dbName'] . ';host=' . $config['database']['dbHost'] . ';charset=utf8';
            $options = [
                PDO::ATTR_EMULATE_PREPARES => false, // turn off emulation mode for "real" prepared statements
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
            ];

            try {
                self::$instance = new PDO($dsn, $config['database']['dbUsername'], $config['database']['dbPassword'], $options);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
                self::$instance->query('SET NAMES utf8');
                self::$instance->query('SET CHARACTER SET utf8');

            } catch (PDOException $error) {
                echo $error->getMessage();
            }

        }

        return self::$instance;
    }

    public static function setCharsetEncoding()
    {
        if (self::$instance == null) {
            self::getInstance();
        }

        self::$instance->exec(
            "SET NAMES 'utf8';
			SET character_set_connection=utf8;
			SET character_set_client=utf8;
			SET character_set_results=utf8");
    }
}
