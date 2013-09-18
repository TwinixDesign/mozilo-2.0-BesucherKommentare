<?php if(!defined('IS_ADMIN') or !IS_ADMIN) die();

class BK_Admin extends BesucherKommentare {
	const TAB_NEW_COMMENTS = 'newComments';
	const TAB_ALL_COMMENTS = 'allComments';

	public function output() {
		global $lang_bk_admin;
		$dir = PLUGIN_DIR_REL."BesucherKommentare/";
		
		$deleteId = '';
		$deleteId = getRequestValue('delete',false,false);
		$this->deleteComment($deleteId);
		
		$approveId = '';
		$approveId = getRequestValue('approve',false,false);
		$this->approveComment($approveId,getRequestValue('group',false,false));
		
		
		$html  = '';
		$html .= '<div id="bk-admin" class="d_mo-td-content-width ui-tabs ui-widget ui-widget-content ui-corner-all mo-ui-tabs" style="position:relative;width:96%;margin:auto auto;">';
		$html .= '<ul id="js-menu-tabs" class="mo-menu-tabs ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-top">'
	          .'<li class="ui-state-default ui-corner-top'.$this->isActiveTabHTML(self::TAB_NEW_COMMENTS).'"><a href="'.PLUGINADMIN_GET_URL.'&amp;actab='.self::TAB_NEW_COMMENTS.'" title="'.$lang_bk_admin->get("config_BesucherKommentare_newComments").'" tabindex="0"><span class="mo-bold">'.$lang_bk_admin->get("config_BesucherKommentare_newComments").'</span></a></li>'
              .'<li class="ui-state-default ui-corner-top'.$this->isActiveTabHTML(self::TAB_ALL_COMMENTS).'"><a href="'.PLUGINADMIN_GET_URL.'&amp;actab='.self::TAB_ALL_COMMENTS.'" title="'.$lang_bk_admin->get("config_BesucherKommentare_allComments").'" tabindex="0"><span class="mo-bold">'.$lang_bk_admin->get("config_BesucherKommentare_allComments").'</span></a></li>';	          		
		$html .= '</ul>';
		$html .= '<div class="d_plugins mo-ui-tabs-panel ui-widget-content ui-corner-bottom mo-no-border-top">';
		$html .= '<div style="padding: 5px;">';		
		
		if (!getRequestValue('actab',false,false) or getRequestValue('actab',false,false) == self::TAB_NEW_COMMENTS) {
			$html .= $this->getNewCommentsHTML();
		}elseif (getRequestValue('actab',false,false) == self::TAB_ALL_COMMENTS) {
			$html .= $this->getAllCommentsHTML();
		}
		
		$html .= '</div></div></div>';	
		return $html;
	}	
	
	private function deleteComment($id) {
		if ($id <> '') {
			if (!getRequestValue('actab',false,false) or getRequestValue('actab',false,false) == self::TAB_NEW_COMMENTS) 		
				return bkDatabase::deleteEntry($id,PLUGIN_DIR_REL."BesucherKommentare/data/".bkCommentNew::BK_NEWCOMMENTS_FILENAME);
			elseif (getRequestValue('actab',false,false) == self::TAB_ALL_COMMENTS)
				return bkDatabase::deleteEntry($id,PLUGIN_DIR_REL."BesucherKommentare/data/".getRequestValue('commentGroup',false,false).'.data.php');
			else 
				return false;
		}else 
			return true;
	}
	
	private function approveComment($id,$group) {
		if ($id <> '') {
			return bkDatabase::copyEntry($id,PLUGIN_DIR_REL."BesucherKommentare/data/".bkCommentNew::BK_NEWCOMMENTS_FILENAME,PLUGIN_DIR_REL."BesucherKommentare/data/".$group.'.data.php');
		}else 
			return true;
	}
	
	private function getNewCommentsHTML() {
		global $lang_bk_admin;
		$html  = '';
		$html .= '<ul class="mo-ul"><li class="mo-li ui-widget-content ui-corner-all">';
		$html .= '<div class="mo-li-head-tag mo-tag-height-from-icon mo-li-head-tag-no-ul mo-middle ui-state-default ui-corner-top">';
		$html .= '<span class="mo-bold">'.$lang_bk_admin->get("config_BesucherKommentare_newCommentsNotApproved").'</span></div>';
		$html .= '<ul class="mo-in-ul-ul">';
		$NewComments = bkDatabase::loadArray(PLUGIN_DIR_REL."BesucherKommentare/data/".bkCommentNew::BK_NEWCOMMENTS_FILENAME);		
		if (is_array($NewComments) and count($NewComments) > 0) {
			foreach ($NewComments as $Comment) {
				$html .= '<li class="mo-in-ul-li mo-inline ui-widget-content ui-corner-all ui-helper-clearfix">';
				$html .= $Comment->Group.' - ';
				$html .= $this->getCommentAsHTML($Comment);
				$html .= '<br/>';
				$html .= '<a href="'.PLUGINADMIN_GET_URL.'&amp;actab='.self::TAB_NEW_COMMENTS.'&amp;delete='.$Comment->ID.'">'.$lang_bk_admin->get("config_BesucherKommentare_Delete").'</a>';
				$html .= ' | <a href="'.PLUGINADMIN_GET_URL.'&amp;actab='.self::TAB_NEW_COMMENTS.'&amp;approve='.$Comment->ID.'&amp;group='.$Comment->Group.'">'.$lang_bk_admin->get("config_BesucherKommentare_Approve").'</a>';				
				$html .= '</li>';
			};
		}else{
			$html .= '<li class="mo-in-ul-li mo-inline ui-widget-content ui-corner-all ui-helper-clearfix">';
			$html .= $lang_bk_admin->get("config_BesucherKommentare_NoNewComments");
			$html .= '</li>';
		}
		$html .= '</ul></li></ul>';
		return $html;
	}
	
	private function getAllCommentsHTML() {
		global $lang_bk_admin;
		$showGroup = getRequestValue('commentGroup',false,false);
		$html  = '<div class="js-tools-show-hide mo-li-head-tag mo-li-head-tag-no-ul ui-state-active ui-corner-all ui-helper-clearfix">';
		$html .= '<form name="bkAllComments" method="post" action="#bkAllComments">';
		$html .= '<select name="commentGroup" size="1">';
		$commentGroups = $this->getFilesAsArray(PLUGIN_DIR_REL.'BesucherKommentare/data/');
		foreach ($commentGroups as $group) {
			if ($showGroup == $group) 
				$html .= '<option selected>';
			else 
				$html .= '<option>';				
			$html .= $group.'</option>';
		}
		$html .= '</select>';
		$html .= '<input name="bksubmit" type="submit" value="'.$lang_bk_admin->get("config_BesucherKommentare_Show").'" />';
		$html .= '</form></div>';		
		if ($showGroup !== false) {
			$html .= '<ul class="mo-ul"><li class="mo-li ui-widget-content ui-corner-all">';
			$html .= '<div class="mo-li-head-tag mo-tag-height-from-icon mo-li-head-tag-no-ul mo-middle ui-state-default ui-corner-top">';
			$html .= '<span class="mo-bold">'.$lang_bk_admin->get("config_BesucherKommentare_CommentsOfGroup").' '.$showGroup.'</span></div>';
			$html .= '<ul class="mo-in-ul-ul">';
			$Comments = bkDatabase::loadArray(PLUGIN_DIR_REL."BesucherKommentare/data/".$showGroup.'.data.php');
			if (is_array($Comments)) {
				foreach ($Comments as $Comment) {
					$html .= '<li class="mo-in-ul-li mo-inline ui-widget-content ui-corner-all ui-helper-clearfix">';
					$html .= $this->getCommentAsHTML($Comment);
					$html .= '<br/>';
					$html .= '<a href="'.PLUGINADMIN_GET_URL.'&amp;actab='.self::TAB_ALL_COMMENTS.'&amp;delete='.$Comment->ID.'&amp;commentGroup='.$showGroup.'">'.$lang_bk_admin->get("config_BesucherKommentare_Delete").'</a>';
					$html .= '</li>';
				};
			}else{
				$html .= '<li class="mo-in-ul-li mo-inline ui-widget-content ui-corner-all ui-helper-clearfix">';
				$html .= $lang_bk_admin->get("config_BesucherKommentare_NoComments");
				$html .= '</li>';
			}			
			$html .= '</ul></li></ul>';
		}		
		return $html;
	}
	
	private function isActiveTabHTML($name) {
		$html = ' ui-tabs-selected ui-state-active';
		$activeTab = getRequestValue('actab',false,false);
		if ((!$activeTab) and ($name == self::TAB_NEW_COMMENTS))
			return $html;
		elseif ($activeTab == $name)
			return $html;
		else
			return '';
	}
	
	private function getCommentAsHTML($Comment) {
		global $specialchars;
		return $Comment->Date.' - '.$Comment->Name.'<br/>www:'.$Comment->Web.' mail:'.$Comment->EMail.'<br/>'.nl2br($specialchars->getHtmlEntityDecode($Comment->Comment)).'<br/>';
	}
	
	private function getFilesAsArray($dir) {
		$dateien = array();
		if(is_dir($dir) and false !== ($currentdir = opendir($dir))) {
			while(false !== ($file = readdir($currentdir))) {
				if ($file == bkCommentNew::BK_NEWCOMMENTS_FILENAME)
					continue;
				if ($file == '.' or $file == '..')
					continue;				
				$dateien[] = trim(str_replace(".data.php","",$file));			
			}
			closedir($currentdir);
			sort($dateien);
		}
		return $dateien;
	}
}

$bk_Admin = new BK_Admin($plugin);
return $bk_Admin->output();
?>