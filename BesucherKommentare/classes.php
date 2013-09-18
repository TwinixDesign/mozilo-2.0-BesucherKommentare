<?php if(!defined('IS_CMS')) die();

/***************************************************************
 *
* Teil des Plugin fuer moziloCMS, welches eine Kommtarfunktion für Besucher bereitstellt.
* by black-night - Daniel Neef
*
***************************************************************/

class bkComment {
	public $ID;
	public $Date;
	public $Name;
	public $Comment;
	public $Web;
	public $EMail;
	
	public function __construct($Date,$Name,$Comment,$Web,$EMail,$ID="") {
		global $specialchars;
		if ($ID == "") {
			$this->ID = $this->guid();
		}else{
			$this->ID = $ID;
		}
		$this->Date = $Date;
		$this->Name = htmlspecialchars(trim($Name));
		$this->Comment = htmlspecialchars(trim($Comment));
		$this->Web = htmlspecialchars(trim($Web));
		$this->EMail = htmlspecialchars(trim($EMail));
	}
	
	protected function guid(){
		if (function_exists('com_create_guid')){
			return com_create_guid();
		}else{
			mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = chr(123)// "{"
			.substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12)
			.chr(125);// "}"
			return $uuid;
		}
	}	
	
	public static function compare($a,$b) {
		return (strtotime($a->Date)-strtotime($b->Date))*-1;
	}
}

class bkCommentNew extends bkComment {
	const BK_NEWCOMMENTS_FILENAME = "NewComments.data.php";	
	public $Group;
	
	public function __construct($Date,$Name,$Comment,$Web,$EMail,$Group,$ID="") {
		global $specialchars;
		parent::__construct($Date,$Name,$Comment,$Web,$EMail,$ID);
		$this->Group = $specialchars->replacespecialchars($specialchars->getHtmlEntityDecode($Group),false);
	}	
}

class bkDatabase {
	private static function lookFile($filename) {
		if (!file_exists($filename))
			touch($filename);
		$fp = fopen($filename, "c+");
		$retries = 0;
		$max_retries = 100;
		
		if (!$fp) {
			return false;
		}
		
		// keep trying to get a lock as long as possible
		do {
			if ($retries > 0) {
				usleep(rand(1, 10000));
			}
			$retries += 1;
		} while (!flock($fp, LOCK_EX) and $retries <= $max_retries);
		
		// couldn't get the lock, give up
		if ($retries == $max_retries) {
			return false;
		}		
		return $fp;
	}
	
	private static function unlookFile($fileHandle) {
		flock($fileHandle, LOCK_UN);
		fclose($fileHandle);		
		return true;
	}
		
	public static function deleteEntry($id,$filename) {
		$fp = self::lookFile($filename);
		if (!$fp) 
			return false;	
		$newData = array();
		if (filesize($filename) > 0) {
			$newData = fread($fp,filesize($filename));
			$newData = trim(str_replace("<?php die(); ?>","",$newData));
			$newData = unserialize($newData);			
		}
				
		for ($i = 0; $i < count($newData); $i++) {
			$Comment = $newData[$i];
			if ($Comment->ID == $id) {
				unset($newData[$i]);
				break;
			}
		}
		$newData = array_values($newData);
		
		rewind($fp);
		ftruncate($fp,0);
		
		fwrite($fp, "<?php die(); ?>\n".serialize($newData));
		self::unlookFile($fp);

		return true;	
	}
	
	public static function copyEntry($id,$sourceFilename,$destinationFilename) {
		$fpSource = self::lookFile($sourceFilename);
		if (!$fpSource) 
			return false;		
		$fpDestination = self::lookFile($destinationFilename);
		if (!$fpSource) {
			self::unlookFile($fpSource);
			return false;
		}		
		rewind($fpSource);
		$sourceData = array();
		if (filesize($sourceFilename) > 0) {
			$sourceData = fread($fpSource,filesize($sourceFilename));
			$sourceData = trim(str_replace("<?php die(); ?>","",$sourceData));
			$sourceData = unserialize($sourceData);			
		}
		$found = false;
		for ($i = 0; $i < count($sourceData); $i++) {
			$SourceComment = $sourceData[$i];
			if ($SourceComment->ID == $id) {
				$found = true;
				$DestinationComment = new bkComment($SourceComment->Date,$SourceComment->Name,$SourceComment->Comment,$SourceComment->Web,$SourceComment->EMail,$SourceComment->ID);
				break;
			}
		}
		if ($found) {
			$newData = array();
			if (filesize($destinationFilename) > 0) {
				$newData = fread($fpDestination,filesize($destinationFilename));
				$newData = trim(str_replace("<?php die(); ?>","",$newData));
				$newData = unserialize($newData);				
			}
		
			$newData[] = $DestinationComment;
		
			rewind($fpDestination);
			ftruncate($fpDestination,0);
			fwrite($fpDestination, "<?php die(); ?>\n".serialize($newData));		
				
			unset($sourceData[$i]);
			$sourceData = array_values($sourceData);
			rewind($fpSource);
			ftruncate($fpSource,0);
			fwrite($fpSource, "<?php die(); ?>\n".serialize($sourceData));			
		}
		self::unlookFile($fpDestination);
		self::unlookFile($fpSource);
		return true;
	}
	
	public static function loadArray($filename) {
		if (!file_exists($filename))
			touch($filename);		
		$data = file_get_contents($filename);
		$data = trim(str_replace("<?php die(); ?>","",$data));
		return unserialize($data);
	}
	
	public static function saveArray($filename, $data) {
		$fp = self::lookFile($filename);
		if (!$fp) 
			return false;
		rewind($fp);
		ftruncate($fp,0);		

		fwrite($fp, "<?php die(); ?>\n".serialize($data));
		self::unlookFile($fp);

		return true;		
	}
	
	public static function appendArray($filename, $data) {
		$fp = self::lookFile($filename);
		if (!$fp) 
			return false;
		$newData = array();
		if (filesize($filename) > 0) {
			$newData = fread($fp,filesize($filename));
			$newData = trim(str_replace("<?php die(); ?>","",$newData));
			$newData = unserialize($newData);						
		}
				
		$newData[] = $data;
		
		rewind($fp);
		ftruncate($fp,0);

		fwrite($fp, "<?php die(); ?>\n".serialize($newData));
		self::unlookFile($fp);

		return true;		
	}
}

?>