<?php if(!defined('IS_CMS')) die();

/***************************************************************
*
* Plugin fuer moziloCMS, welches eine Kommtarfunktion für Besucher bereitstellt.
* by black-night - Daniel Neef
* 
***************************************************************/
require_once(PLUGIN_DIR_REL."BesucherKommentare/classes.php");
class BesucherKommentare extends Plugin {

    /***************************************************************
    * 
    * Gibt den HTML-Code zurueck, mit dem die Plugin-Variable ersetzt 
    * wird.
    * 
    ***************************************************************/		
	const SESSION_LOADTIME = 'BesucherKommentare_loadtime';
	const SESSION_SPAMCALCRESULT = 'BesucherKommentare_calc_result';
	const SETTING_SPAMPROTECTION = 'spamprotection';
	const SETTING_SPAMCALCS = 'spamcalcs';
	const SETTING_MAXLENGTH_NAME = 'maxlength_name';
	const SETTING_MAXLENGTH_WEB = 'maxlength_web';
	const SETTING_MAXLENGTH_EMAIL = 'maxlength_email';
	const SETTING_MAXLENGTH_COMMENT = 'maxlength_comment';
	const SETTING_EMAIL = 'email';
	const SETTING_DATEFORMAT = 'dateformat';
	const SETTING_LOADTIME = 'loadtime';
	
	private $bkerror = '';
	
    function getContent($value) {       
        global $CMS_CONF;
    	global $lang_BesucherKommentare_cms;
    	global $specialchars;
    	$this->bkerror = '';
    	$AddCommentSuccessful = false;
    	$dir = PLUGIN_DIR_REL."BesucherKommentare/";
    	$lang_BesucherKommentare_cms = new Language($dir."sprachen/cms_language_".$CMS_CONF->get("cmslanguage").".txt");
        $value = $specialchars->replacespecialchars($specialchars->getHtmlEntityDecode($value),false);
        if ($value.'.data.php' == bkCommentNew::BK_NEWCOMMENTS_FILENAME) {
        	return $value.' Ist nicht gültig!';
        }
        if (getRequestValue('bksubmit',false,false) !== false) {
			$this->bkerror .= $this->checkSpam();
			$this->bkerror .= $this->checkLength();
        	if ($this->bkerror == '') 
        		$AddCommentSuccessful = $this->appendNewComment($value,date("d.m.Y G:i:s"),
        														getRequestValue('bkName',false,false),
        														getRequestValue('bkWeb',false,false),
        														getRequestValue('bkEMail',false,false),
																getRequestValue('bkComment',false,false));
        }
      
        $result  = '<div class="bk">';
        //Vorhandene freigegebene Kommentare Anzeigen
        $result .= $this->getCommentsHTML($value);
        //Formular für neue Kommentare Anzeigen
        $result .= $this->getFormHTML($value);        
        //Fehlerausgabe
        $result .= $this->getErrorHTML();
        //Erfolgsausgabe
        $result .= $this->getSuccessfulAddHTML($AddCommentSuccessful);
        
		$result .= '</div>';
		
		$_SESSION[self::SESSION_LOADTIME] = time();
        return $result;

    } // function getContent
    
    
    
    /***************************************************************
    * 
    * Gibt die Konfigurationsoptionen als Array zurueck.
    * 
    ***************************************************************/
    function getConfig() {
        global $lang_bk_admin;

        $config = array();
        $config["--admin~~"] = array(
        		"buttontext" => $lang_bk_admin->get("config_BesucherKommentare_adminbuttontext"),
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_adminbutton"),
        		"datei_admin" => "admin.php"
        );      
        $config[self::SETTING_LOADTIME] = array(
        		"type" => "text",
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_LoadTime"),
        		"maxlength" => "4",
        		"regex" => "/^[1-9][0-9]?/",
        		"regex_error" => $lang_bk_admin->get("config_BesucherKommentare_number_regex_error")
        );        
        $config[self::SETTING_SPAMPROTECTION] = array(
        		"type" => "checkbox",
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_spamprotection")
        );
        $config[self::SETTING_SPAMCALCS] = array(
        		"type" => "textarea",
        		"rows" => "10",
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_calcs")
        );        
        $config[self::SETTING_MAXLENGTH_NAME] = array(
        		"type" => "text",
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_MaxLengthName"),
        		"maxlength" => "4",
        		"regex" => "/^[1-9][0-9]?/",
        		"regex_error" => $lang_bk_admin->get("config_BesucherKommentare_number_regex_error")
        );
        $config[self::SETTING_MAXLENGTH_WEB] = array(
        		"type" => "text",
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_MaxLengthWeb"),
        		"maxlength" => "4",
        		"regex" => "/^[1-9][0-9]?/",
        		"regex_error" => $lang_bk_admin->get("config_BesucherKommentare_number_regex_error")
        );
        $config[self::SETTING_MAXLENGTH_EMAIL] = array(
        		"type" => "text",
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_MaxLengthEMail"),
        		"maxlength" => "4",
        		"regex" => "/^[1-9][0-9]?/",
        		"regex_error" => $lang_bk_admin->get("config_BesucherKommentare_number_regex_error")
        );
        $config[self::SETTING_MAXLENGTH_COMMENT] = array(
        		"type" => "text",
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_MaxLengthComment"),
        		"maxlength" => "4",
        		"regex" => "/^[1-9][0-9]?/",
        		"regex_error" => $lang_bk_admin->get("config_BesucherKommentare_number_regex_error")
        );
        $config[self::SETTING_EMAIL] = array(
        		"type" => "text",
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_EMailOnNewComment"),
        		"maxlength" => "100"
        );                
        $config[self::SETTING_DATEFORMAT] = array(
        		"type" => "text",
        		"description" => $lang_bk_admin->get("config_BesucherKommentare_DateFormat"),
        		"maxlength" => "100"
        );        
        return $config;            
    } // function getConfig
    
    
    
    /***************************************************************
    * 
    * Gibt die Plugin-Infos als Array zurueck. 
    * 
    ***************************************************************/
    function getInfo() {
        global $ADMIN_CONF;
        global $lang_bk_admin;
        $dir = PLUGIN_DIR_REL."BesucherKommentare/";
        $language = $ADMIN_CONF->get("language");
        $lang_bk_admin = new Properties($dir."sprachen/admin_language_".$language.".txt",false);        
        $info = array(
            // Plugin-Name
            "<b>".$lang_bk_admin->get("config_BesucherKommentare_plugin_name")."</b> \$Revision: 1 $",
            // CMS-Version
            "2.0",
            // Kurzbeschreibung
            $lang_bk_admin->get("config_BesucherKommentare_plugin_desc"),
            // Name des Autors
           "black-night",
            // Download-URL
            array("http://software.black-night.org","Software by black-night"),
            # Platzhalter => Kurzbeschreibung
            array('{BesucherKommentare|...}' => $lang_bk_admin->get("config_BesucherKommentare_plugin_name")          		
                 )
            );
            return $info;        
    } // function getInfo
    
    /***************************************************************
    *
    * Interne Funktionen
    *
    ***************************************************************/

    function getFormHTML($groupname) {
    	global $lang_BesucherKommentare_cms;
    	$formular  = '<div class="bkFormHead"><a name="bkNew'.$groupname.'"></a>'.$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_CreateNewComment").'</div>';
    	$formular .= '<form name="bkForm" method="post" action="#bkNew'.$groupname.'">';    	
    	$formular .= '<div class="bkFormName">'.$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_Name").'</div>';
    	$formular .= '<input name="bkName" class="bkFormName" type="text" maxlength="'.$this->settings->get(self::SETTING_MAXLENGTH_NAME).'" value="'.$this->getRequestValueWithDefault('bkName').'" />';
    	$formular .= '<div class="bkFormWeb">'.$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_Web").'</div>';
    	$formular .= '<input name="bkWeb" class="bkFormWeb" type="text" maxlength="'.$this->settings->get(self::SETTING_MAXLENGTH_WEB).'" value="'.$this->getRequestValueWithDefault('bkWeb').'" />';
    	$formular .= '<div class="bkFormEMail">'.$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_EMail").'</div>';
    	$formular .= '<input name="bkEMail" class="bkFormEMail" type="text" maxlength="'.$this->settings->get(self::SETTING_MAXLENGTH_EMAIL).'" value="'.$this->getRequestValueWithDefault('bkEMail').'" />';    	
    	$formular .= '<div class="bkFormKommentar">'.$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_Comment").'</div>';
    	$formular .= '<textarea name="bkComment" class="bkFormKommentar" maxlength="'.$this->settings->get(self::SETTING_MAXLENGTH_COMMENT).'">'.$this->getRequestValueWithDefault('bkComment').'</textarea>';
    	
    	if($this->settings->get(self::SETTING_SPAMPROTECTION) == "true") {
    		// Spamschutz-Aufgabe
    		$calculation_data = $this->getRandomSpamCalc();
    		$_SESSION[self::SESSION_SPAMCALCRESULT] = $calculation_data[1];
    		$formular .= '<div class="bkFormSpamProtectionText">'.$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_SpamProtectionText").'</div>';
			$formular .= '<div class="bkFormSpamProtectionCalc">'.$calculation_data[0].'</div>';
    		$formular .= '<input name="bkSpamCalcResult" class="bkFormSpamProtectionCalcResult" type="text" autocomplete="off" />';    	
    	}    	
    	
    	$formular .= '<br/><input name="bksubmit" class="bksubmit" type="submit" value="'.$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_Send").'" />';
		$formular .= '</form>'; 
		return $formular;   	
    }   
    
    function getCommentsHTML($groupname) {    	
    	$filename = PLUGIN_DIR_REL."BesucherKommentare/data/".$groupname.'.data.php';
    	$data = bkDatabase::loadArray($filename);
    	$result = '<div class="bkKommentarListe">';    	
    	if (is_array($data)) {    		
    		usort($data,"bkComment::compare");    		
    		foreach ($data as $Comment) {
    			$result .= '<div class="bkKommentarListeEintrag">';
				$result .= '<div class="bkDatum">'.$this->getFormatedDate($Comment->Date).'</div>';
				$result .= '<div class="bkName">'.$Comment->Name.'</div>';
				if ($Comment->Web != 'http://') {
					$result .= '<div class="bkWeb"><a href="'.$Comment->Web.'" target="_blank">'.$Comment->Web.'</a></div>';;
				}else{
					$result .= '<div class="bkWeb"></div>';
				}
				$result .= '<div class="bkKommentar">'.$this->getFormatedComment($Comment->Comment).'</div>';
				$result .= '</div>';
    		}    		
    	}
    	$result .= '</div>';
    	return $result;
    }
    
    function getFormatedDate($date) {    	
    	if (strlen(trim($this->settings->get(self::SETTING_DATEFORMAT))) > 0 and $this->settings->get(self::SETTING_DATEFORMAT) !== 'd.m.Y G:i:s') 
    		return date($this->settings->get(self::SETTING_DATEFORMAT),strtotime($date));    
    	else 
    		return $date;
    }
    
    function getFormatedComment($comment) {
    	global $specialchars;
    	$result = $comment;    	
    	$result = $specialchars->getHtmlEntityDecode($result);
    	$result = nl2br($result);
    	return $result;  	
    }
    
    function appendNewComment($groupname,$date,$name,$web,$email,$comment) {
    	global $lang_BesucherKommentare_cms;
    	$filename = PLUGIN_DIR_REL."BesucherKommentare/data/".bkCommentNew::BK_NEWCOMMENTS_FILENAME;
    	$newComment = new bkCommentNew($date,$name,$comment,$web,$email,$groupname);
    	$result = bkDatabase::appendArray($filename,$newComment);
    	
    	$InfoEMail = $this->settings->get(self::SETTING_EMAIL);
    	if ($InfoEMail <> '') {
    		require_once(BASE_DIR_CMS."Mail.php");
    		sendMail(	$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_EMailSubject"), 
    					$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_EMailContent").$name.' - '.$comment, 
    					$InfoEMail, $InfoEMail, $InfoEMail);
    	}    	
    	return $result;
    }
    
    function getRandomSpamCalc() {
    	$tmp_calcs = explode("<br />",$this->settings->get(self::SETTING_SPAMCALCS));
    	foreach($tmp_calcs as $zeile) {
    		$tmp_z = explode(" = ",$zeile);
    		if(isset($tmp_z[0]) and isset($tmp_z[1]) and !empty($tmp_z[0]) and !empty($tmp_z[1]))
    			$contactformcalcs[$tmp_z[0]] = $tmp_z[1];
    	}
    	$tmp = array_keys($contactformcalcs);
    	$randnum = rand(0, count($contactformcalcs)-1);
    	return array($tmp[$randnum],$contactformcalcs[$tmp[$randnum]]);
    }    
    
    function getErrorHTML() {
    	if ($this->bkerror <> '') {
    		return '<div class="bkError">'.$this->bkerror.'</div>';
    	}
    }
    
    function getSuccessfulAddHTML($value) {
    	global $lang_BesucherKommentare_cms;    	
    	if ($value) {
    		return '<div class="bkSuccessful">'.$lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_SuccessfulAdd").'</div>';
    	}
    }
    
    function checkSpam() {
    	global $lang_BesucherKommentare_cms;
    	$error = '';
    	$loadtime = $this->settings->get(self::SETTING_LOADTIME);
    	if ($loadtime == '') 
    		$loadtime = 15;
    	if (time() - $_SESSION[self::SESSION_LOADTIME] < $loadtime) {
    		$error .= $lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_Error_SentToFast",$loadtime);
    	}
    	if($error == '' and $this->settings->get(self::SETTING_SPAMPROTECTION) == "true") {
    		if (strtolower($_SESSION[self::SESSION_SPAMCALCRESULT]) != strtolower(getRequestValue('bkSpamCalcResult',false,false))) {
    			$error .= $lang_BesucherKommentare_cms->getLanguageValue("config_BesucherKommentare_Error_SpamCalcResult");
    		}
    	}    	
    	return $error;
    }
    
    function checkLength() {
    	global $lang_BesucherKommentare_cms;
    	$error = '';    	
    	if (strlen(getRequestValue('bkName',false,false)) > $this->settings->get(self::SETTING_MAXLENGTH_NAME)) 
    		$error .= $lang_contact->getLanguageValue("config_BesucherKommentare_Error_NameToLong", $sendtime);
    	if (strlen(getRequestValue('bkWeb',false,false)) > $this->settings->get(self::SETTING_MAXLENGTH_WEB))
    		$error .= $lang_contact->getLanguageValue("config_BesucherKommentare_Error_WebToLong", $sendtime);
    	if (strlen(getRequestValue('bkEMail',false,false)) > $this->settings->get(self::SETTING_MAXLENGTH_EMAIL))
    		$error .= $lang_contact->getLanguageValue("config_BesucherKommentare_Error_EMailToLong", $sendtime);
    	if (strlen(getRequestValue('bkComment',false,false)) > $this->settings->get(self::SETTING_MAXLENGTH_COMMENT))
    		$error .= $lang_contact->getLanguageValue("config_BesucherKommentare_Error_CommentToLong", $sendtime);    	
    }
    
    function getRequestValueWithDefault($name) {
    	$result = getRequestValue($name,false,false);
    	if (!$result or $this->bkerror == '') {
    		if ($name == 'bkWeb') {
    			$result = 'http://';
    		}else{
    			$result = '';
    		}
    	}    	
    	return $result;
    }
} // class BesucherKommentare

?>