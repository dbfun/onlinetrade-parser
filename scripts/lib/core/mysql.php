<?php
/**
 * @package Сore
 */

/**
 * Работа с MySQL
 */

class DataBaseMysql {
  private $dbId;
  private function connect($host, $user, $password, $database) { if (!$this->dbId = @mysql_connect($host, $user, $password)) die("<b>MySQL</b>: Unable to connect to database"); if (!mysql_select_db($database)) die("<b>MySQL</b>: Unable to select database <b>".$database."</b>"); }
  public function Query($sqlString) { if (!$resourseId =@mysql_query($sqlString, $this->dbId)) die("<b>MySQL</b>: Unable to execute<br /><b>SQL</b>: ".$sqlString."<br /><b>Error (".mysql_errno().")</b>: ".@mysql_error()); return $resourseId; }
  public function SelectValue($sqlString) { $resourseId = self::Query($sqlString); $row = array(); $row = mysql_fetch_row($resourseId); @mysql_free_result($resourseId); return $row[0]; }
  public function SelectRow($sqlString) { $resourseId = self::Query($sqlString); $row = array(); $row = mysql_fetch_assoc($resourseId); @mysql_free_result($resourseId); return $row; }
  public function SelectSet($sqlString, $idTable = '') { $resourseId = self::Query($sqlString); $row = array(); while ($rowOne = mysql_fetch_assoc($resourseId)) { if ($idTable) $row[$rowOne[$idTable]] = $rowOne; else $row[] = $rowOne; } @mysql_free_result($resourseId); return $row; }
  public function SelectLastInsertId() { return @mysql_insert_id($this->dbId); }
  public function Destroy() { if (!@mysql_close($this->dbId)) die("Cann't disconnect from database"); }
  public function getDbId() { return $this->dbId; }
  public function getNumAffectedRows() { return mysql_affected_rows($this->dbId); }
  public function selectDb($database) {
    if (!mysql_select_db($database)) die("<b>MySQL</b>: Unable to select database <b>".$database."</b>");
  }
  public function __construct($config) {
    $this->connect($config->host, $config->user, $config->password, $config->db);
    $this->Query("SET NAMES UTF8");
  }
}