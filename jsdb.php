<?php
/**
 * Librarium JSON Flat File Database
 * 
 * Flat File Database Manipulation Script
 *  
 * @version: 1.0	Release Date: 03/2016					
 * @author Artur W. <arturwiater@gmail.com>				
 * @copyright Copyright (c) 2016 All Rights Reserved				
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

class Librarium{

	/**
	 * Array with tables
	 */
	private $database=array();
	
	/**
	 * DB File resource hook
	 */
	private $dbFile;
	
	/**
	 * Constructor class
	 * @param String $path Path to file with database
	 */
	function __construct($path)
	{
		if (file_exists($path))
		{
			$this->dbFile=$path;
		}else
		{
			die("WRONG PATH TO DATABASE");
		}
	}
	
	/**
	 * Import table object from csv file,first row from file is treated as a tabs for table
	 * @param String $name table name
	 * @param String $path path to CSV file
	 * @return Boolean
	 */
	public function getDBTblImport($name,$path)
	{
		$this->setDBConnect();
		if(file_exists($path) && !array_key_exists($name,$this->database))
		{
			$path = file($path); 
			$this->database[$name]["tabs"]=str_getcsv($path[0]);
			for ($i=1;$i<count($path);$i++)
			{ 
			 $this->database[$name]["items"][]=str_getcsv($path[$i]);
			} 
			$out=$this->saveDBFile();
		}else{$out=false;}
	return $out;}
	
	
	
	/**
	 * Search for records in database
	 * @param String $table name of table
	 * @param Array $ids optional array with tabs name  to returns
	 * @param Array $where optional lookup array
	 * @return Array|Boolean Return Array or false if failed
	 */
	public function getDBSelect($table,Array $ids=null,Array $where=null)
	{
		$this->setDBConnect();
		if(array_key_exists($table,$this->database))
		{
			$out=array();
			$out_r=array();
			$table=$this->database[$table];
			$tabs=array_flip($table["tabs"]);
			$where_key=$where==null ? null :array_keys($where);
			$ids=$ids==null ? $table["tabs"] : $ids;
				foreach($table["items"]as $key_it=>$item)
				{
					foreach($item as $k=>$v)
					{
						$k=$table["tabs"][$k];
						
						if (in_array($k,$ids))
						{
							
							$out[$key_it][$k]=$v;
						}
					}
				
				
					
					if (is_array($where) && in_array($where_key[0],$tabs) && $item[$tabs[$where_key[0]]]==$where[$where_key[0]])
					{
						$out_r[$key_it]=$out[$key_it];
					}
				}
				
				if (!is_array($where))
					{
						$out_r=$out;
					}
		}else
		{
			die("TABLE $name NOT EXISTS");
		}
	return $out_r;}
	
	/**
	 * Private function saving database object to file
	 * @param Object $object database object
	 * @return Boolean Returns True or False if failed
	 */
	private function saveDBFile()
	{
		
		return $this->setDBConnect("w");
	}
	
	/**
	 * Create new Table in database object
	 * @param String $name table name
	 * @param Array $tabs table tabs names
	 * @return Boolean Returns True or False if failed
	 */
	public function setDBNewTable($name,Array $tabs)
	{
		$this->setDBConnect();
		if (!array_key_exists($name,$this->database))
		{
			$this->database[$name]["tabs"]=$tabs;
			$out=$this->saveDBFile();
		}else
		{
			die("TABLE $name ALREADY EXISTS");
		}
	return $out;}
	
	
	/**
	 * Inserting new record to table object
	 * @param String $name table name
	 * @param Array $items new record items
	 * @return Boolean Returns True or False
	 */
	function setDBInsert($name,array $items)
	{
		$this->setDBConnect();
		$out=array();
		if (array_key_exists($name,$this->database))
		{
			$out=array();
			$tabs=array_flip($this->database[$name]["tabs"]);
			$items=$this->convertArrayIndex($items,$this->database[$name]["tabs"]);
			
			foreach($this->database[$name]["tabs"] as $k=>$v)
			{
				
				if (array_key_exists($k,$items))
				{
					$out[$k]=$items[$k];
				}else
				{
					$out[$k]="NULL";
				}
			}
			$this->database[$name]["items"][]=$out;
			$out=$this->saveDBFile();
		}else
		{
			$out=false;
		}
	return $out;}
	
		/**
	 * Load JSON formatted database file
	 * @param String $name path to database file
	 * @return Boolean Returns True or False
	 */
	public function setDBConnect($type="r")
	{
		$name=$this->dbFile;
		$out=false;
		if($type=="r" && file_exists($name))
		{
			$this->dbFile=fopen($name,$type);
			flock($this->dbFile,LOCK_SH);
			$this->database=json_decode(file_get_contents($name),true);
			$out=true;
		}else
		if($type=="w")
		{
			$this->dbFile=fopen($name,$type);
			flock($this->dbFile,LOCK_EX);
			file_put_contents($name,json_encode($this->database));
			$out=true;
		}else
		{
			die("DATABASE FILE NOT FOUND");
		}
		
		flock($this->dbFile,LOCK_UN);
		$this->dbFile=$name;
		return $out;
	}
	
	/**
	 * Export table to csv file
	 * @param String $name table name to export
	 * @param String $path export file path
	 * @return Boolean Returns True or False
	 */
	function setDBTblExport($name,$path)
	{
		$this->setDBConnect();
		if (array_key_exists($name,$this->database))
		{
			$path=fopen($path,"w");
			$out[]=fputcsv($path,$this->database[$name]["tabs"]);
			foreach($this->database[$name]["items"] as $val)
			{
				$out[]=fputcsv($path,$val);
			}
			fclose($path);
		}
	return $out;}
	
	/**
	 * Deleting record from table object
	 * @param String $name table name
	 * @param Array $where lookup array
	 * @return Integer|Boolean Returns number of deleted records or False
	 */
	public function setDBDelete($name,Array $where)
	{
		$this->setDBConnect();
		
		if (array_key_exists($name,$this->database))
		{
			$tabs=array_flip($this->database[$name]["tabs"]);
			$where_key=array_keys($where);
			
			foreach($this->database[$name]["items"] as $key=>$item)
			{
				if (array_key_exists($tabs[$where_key[0]],$item) &&  $item[$tabs[$where_key[0]]]==$where[$where_key[0]])
				{
					unset($this->database[$name]["items"][$key]);
				}
			}
			$out=$this->saveDBFile();
		}else
		{
			die("TABLE $name NOT EXISTS");
		}
	return $out;}
	
	/**
	 * Deletes table object
	 * @param String $name table name
	 * @return Boolean Returns True or False
	 */
	public function setDBDrop($name)
	{
		$this->setDBConnect();
		if(array_key_exists($name,$this->database))
		{
			unset($this->database[$name]);
			$out=$this->saveDBFile();
		}
	
	return $out;}
	
	/**
	 * Update record in table object
	 * @param String $name table name
	 * @param Array $items Array with tabs and values to update
	 * @param Array $where Lookup array
	 * @return Integer|Boolean Returns number of updated records or False if failed
	 */
	public function setDBUpdate($name,array $items,array $where)
	{
		$this->setDBConnect();
		
		if(array_key_exists($name,$this->database))
		{
			$tabs=array_flip($this->database[$name]["tabs"]);
			$where_key=array_keys($where);
			$items=$this->convertArrayIndex($items,$this->database[$name]["tabs"]);
			
			foreach($this->database[$name]["items"] as $key=>$val)
			{
				if (array_key_exists($tabs[$where_key[0]],$val) &&  $val[$tabs[$where_key[0]]]==$where[$where_key[0]])
				{
					foreach($items as $k=>$v)
					{
						$this->database[$name]["items"][$key][$k]=$v;
					}
					
				}
			}
			$out=$this->saveDBFile();
		}
	return $out;}

	/**
	 * Convert tabs name to index nr
	 * @param Array $data Source array
	 * @param Array $converter Array with table tabs
	 * @return Array Return array with numerical indexes
	 */
	private function convertArrayIndex(array $data,array $converter)
	{
		$out=array();
		$converter=array_flip($converter);
		foreach($data as $k=>$v)
		{
			if (array_key_exists($k,$converter))
			{
				$out[$converter[$k]]=$v;
			}
		}
		return $out;
	}
	
	/**
	 * Executing SQL Query
	 * @param String $sql Query to execute
	 * @return Return reuslt of query or error if failed
	 */
	public function Execute($sql)
	{
		$sql=explode(" ",$sql);
		$cols=array();
		
		$key=array_search("WHERE",$sql);
		if ($key!==false)
		{
			$where=explode("=",$sql[$key+1]);
			$where=array($where[0]=>$where[1]);
		}else{$where=null;}
		
		if (in_array("SELECT",$sql))
		{
			$key=array_search("FROM",$sql);
			$table=$sql[$key+1];
			
			$key=array_search("SELECT",$sql);
			$cols=explode(",",$sql[$key+1]);
			return $this->getDBSelect($table,$cols,$where);
		}else
		if (in_array("UPDATE",$sql))
		{
			$key=array_search("UPDATE",$sql);
			$table=$sql[$key+1];
			
			$key=array_search("SET",$sql);
			
			foreach(explode(",",$sql[$key+1]) as $v)
			{
				$v=explode("=",$v);
				$cols[$v[0]]=$v[1];
			}
			return $this->setDBUpdate($table,$cols,$where);
		}else
		if (in_array("INSERT",$sql))
		{
			$key=array_search("INTO",$sql);
			$table=$sql[$key+1];
			
			$key=array_search("VALUES",$sql);
			$cols=explode(",",str_replace(array("(",")"),"",$sql[$key-1]));
			$vals=explode(",",str_replace(array("(",")"),"",$sql[$key+1]));
			$cols=array_combine($cols,$vals);
			return $this->setDBInsert($table,$cols);
		}else
		if (in_array("DELETE",$sql))
		{
			$key=array_search("FROM",$sql);
			$table=$sql[$key+1];
			return $this->setDBDelete($table,$where);
		}else
		if (in_array("DROP",$sql) && in_array("TABLE",$sql))
		{
			$key=array_search("TABLE",$sql);
			return $this->setDBDrop($sql[$key+1]);
		}else
		if (in_array("CREATE",$sql) && in_array("TABLE",$sql))
		{
			$key=array_search("TABLE",$sql);
			$table=$sql[$key+1];
			$cols=explode(",",str_replace(array("(",")"),"",$sql[$key+2]));
			return $this->setDBNewTable($table,$cols);
		}else
		{
			die("ERROR QUERY PARSING");
		}
		
	}

}

?>
