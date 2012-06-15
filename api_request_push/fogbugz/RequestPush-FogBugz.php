<?php
/*
Request Push for: FogBugz API
Designed for: FogBugz version 6, FogBugz API version 3
PHP Versions required: 5.1.0+
PHP Modules required: curl
Notes: This implementation uses the comment supplied by the staff member as the case title.
*/

/*
Request Push API information can be found at:
http://www.userscape.com/helpdesk/index.php?pg=kb.page&id=153
*/

// SECURITY: This prevents this script from being called from outside the context of HelpSpot
if (!defined('cBASEPATH')) die();
	
class RequestPush_FogBugz{
	
	//FogBugz API variables 
	//MODIFY THESE FOR YOUR INSTALLATION
	var $fb_url = ''; //No trailing slash
	var $fb_user_email = '';
	var $fb_user_password = '';
	
	/*  Private variables  */
	
	//Login token
	var $_fb_login_token = '';
	
	//Request Push errors
	var $errorMsg = "";
	
	//Constructor, do setup for API
	function RequestPush_FogBugz(){
		//Connect to API URL and check version
		$xml = $this->_getURL($this->fb_url.'/api.xml');
		if($xml->version < 3 || $xml->minversion > 3){
			$this->errorMsg = "FogBugz API version not supported";
		}else{
			//Login
			$login = $this->_getURL($this->fb_url.'/api.asp',array('cmd'=>'logon','email'=>$this->fb_user_email,'password'=>$this->fb_user_password));
			if(isset($login->error)){
				$this->errorMsg = $login->error;
			}else{
				$this->_fb_login_token = $login->token;
			}
		}
	}
	
	//Helper method
	function _getURL($url,$params=false){
		//Pass variables via GET
		if($params){
			$url .= '?';
			foreach($params AS $k=>$v){
				$url .= $k.'='.urlencode($v).'&';
			}
			//Add token
			if(!empty($this->_fb_login_token)) $url .= 'token='.$this->_fb_login_token;
		}

		//Use curl to grab XML from FogBugz API
		$curl = curl_init($url);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		//Don't verify for SSL
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
		//Call FB
		$xml = curl_exec($curl);
		curl_close($curl);
		
		//Turn XML int Simple XML object and return
		return simplexml_load_string($xml);
	}
	
	function push($request){
		//Check that there's no errors
		if(empty($this->errorMsg)){
			//Find inital request note to use as case message in FogBugz
			foreach($request['request_history'] AS $row){
				if($row['fInitial'] == 1){
					//Strip/clean HTML since FogBugz doesn't support it
					$message = trim(strip_tags(html_entity_decode($row['tNote'])));
				}
			}
			
			//Build data to send to FogBugz
			//By default we use the staff comment as the title and inital request as the body though other combinations may work better in your particular installation. 
			$data = array(
				'cmd'		=> 'new',
				'sTitle'	=> $request['staff_comment'], //Use the optional staff comment as the title
				'sEvent'	=> $message, //Use the initial request as the case message
				'ixProject'	=> 1,
				'ixArea'	=> 5,
				'ixFixFor'	=> 1,
				'ixCategory'=> 1,
				//'ixPersonAssignedTo'=>2,
				//'ixPriority'=> 1
			);
			
			$xml = $this->_getURL($this->fb_url.'/api.asp',$data);

			return $xml->case['ixBug'].'';
		}
	}
	
	function details($id){
		$data = array(
			'cmd'=>'search',
			'q'=>$id,
			'cols'=>'sTitle,sProject,sArea,sPersonAssignedTo,sStatus,sPriority,sFixFor,sCategory,fOpen,sLatestTextSummary,latestEvent' //List of FB columns to return for display. 
		);
		
		$xml = $this->_getURL($this->fb_url.'/api.asp',$data);
		
		$case = $xml->cases[0]->case;
		
		//Build HTML to show HelpSpot staff member
		$html = '
			<table width="100%" cellspacing="5">
				<tr>
					<td width="85"><b>Title:</b></td>
					<td>'.$case->sTitle.'</td>
				</tr>
				<tr>
					<td><b>Project:</b></td>
					<td>'.$case->sProject.'</td>
				</tr>
				<tr>
					<td><b>Area:</b></td>
					<td>'.$case->sArea.'</td>
				</tr>				
				<tr>
					<td><b>Assigned To:</b></td>
					<td>'.$case->sPersonAssignedTo.'</td>
				</tr>					
				<tr>
					<td><b>Status:</b></td>
					<td>'.$case->sStatus.'</td>
				</tr>	
				<tr>
					<td><b>Priority:</b></td>
					<td>'.$case->sPriority.'</td>
				</tr>	
				<tr>
					<td><b>Fix For:</b></td>
					<td>'.$case->sFixFor.'</td>
				</tr>	
				<tr>
					<td><b>Category:</b></td>
					<td>'.$case->sCategory.'</td>
				</tr>
				<tr>
					<td><b>Open:</b></td>
					<td>'.($case->fOpen ? 'Yes' : 'No').'</td>
				</tr>					
				<tr>
					<td colspan="2"><b>Latest Update:</b></td>
				</tr>	
				<tr>
					<td colspan="2">'.$case->sLatestTextSummary.'</td>
				</tr>					
			</table>
		';
		
		return $html;
	}
}
?>