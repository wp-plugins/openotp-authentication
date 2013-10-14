<?php
/*
 RCDevs OpenOTP Plugin for RoundCube Webmail v2.0
 Copyright (c) 2010-2012 RCDevs, All rights reserved.
 
 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
  
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
class openotp { 

	private $plugin;
	private $home;
	private $openotp_auth;
	private $server_url;
	private $client_id;
	private $default_domain;
	private $user_settings;                                                                           
	private $proxy_host;                                                                              
	private $proxy_port;                                                                              
	private $proxy_username;
	private $proxy_password;
	private $soap_client;
	public $message;

	public function __construct($openotp_plugin, $params, $home=''){

        $this->plugin = $openotp_plugin;
	    $this->home = $home;

		// load config		
		$this->server_url = $params['openotp_server_url'];
		$this->client_id = $params['openotp_client_id'];
		$this->default_domain = $params['openotp_default_domain'];
		$this->user_settings = $params['openotp_user_settings'];                                                                                   
		$this->proxy_host = $params['openotp_proxy_host'];                                                                               
		$this->proxy_port = $params['openotp_proxy_port'];                                                                               
		$this->proxy_username = $params['openotp_proxy_login'];
		$this->proxy_password = $params['openotp_proxy_password'];
		
	}
	
	public function checkFile($file, $message)
	{
		if (!file_exists($this->home."/".$file)) {
			$this->message = $message;
			return false;
		}
		return true;
	}
	
	public function checkSOAPext()
	{
		if (!extension_loaded('soap')) {
			$this->message = 'Your PHP installation is missing the SOAP extension';
			return false;
		}
		return true;
	}
	
	public function enableOpenotp_auth()
	{
		return $this->openotp_auth;
	}
		
	public function getServer_url()
	{
		return $this->server_url;
	}
	
	public function getScope()
	{
		return $this->openotp_scope;
	}
		
	public function getDomain($username)
	{
		$username = str_replace("\\\\","\\",$username);
		$pos = strpos($username, "\\");
		if ($pos) {
			$ret['domain'] = substr($username, 0, $pos);
			$ret['username'] = substr($username, $pos+1);
		} else {                                                                                                                      
			$ret = $this->default_domain;
		}
		return $ret;
	}
	
	public static function getOverlay($message, $username, $session, $timeout, $ldappw, $path, $O_params, $domain=NULL){
		$formToken = $O_params['formToken'];
		$task = $O_params['task'];
		$option = $O_params['option'];
		$overlay = <<<EOT
		function addOpenOTPDivs(){
			var overlay_bg = document.createElement("div");
			overlay_bg.id = 'openotp_overlay_bg';
			overlay_bg.style.position = 'fixed'; 
			overlay_bg.style.top = '0'; 
			overlay_bg.style.left = '0'; 
			overlay_bg.style.width = '100%'; 
			overlay_bg.style.height = '100%'; 
			overlay_bg.style.background = 'grey';
			overlay_bg.style.zIndex = "9998"; 
			overlay_bg.style["filter"] = "0.9";
			overlay_bg.style["-moz-opacity"] = "0.9";
			overlay_bg.style["-khtml-opacity"] = "0.9";
			overlay_bg.style["opacity"] = "0.9";
		
			var tokenform = document.getElementsByName("return")[0].value;
			var overlay = document.createElement("div");
			overlay.id = 'openotp_overlay';
			overlay.style.position = 'absolute'; 
			overlay.style.top = '165px'; 
			overlay.style.left = '50%'; 
			overlay.style.width = '280px'; 
			overlay.style.marginLeft = '-180px';
			overlay.style.padding = '65px 40px 50px 40px';
			overlay.style.background = 'url($path/openotp_banner.png) no-repeat top left #E4E4E4';
			overlay.style.border = '5px solid #545454';
			overlay.style.borderRadius = '10px';
			overlay.style.MozBorderRadius = '10px';
			overlay.style.WebkitBorderRadius = '10px';
			overlay.style.boxShadow = '1px 1px 12px #555555';
			overlay.style.WebkitBoxShadow = '1px 1px 12px #555555';
			overlay.style.MozBoxShadow = '1px 1px 12px #555555';
			overlay.style.zIndex = "9999"; 
			overlay.innerHTML = '<a style="position:absolute; top:-12px; right:-12px; background-color:transparent;" href="index.php" title="close"><img src="$path/openotp_closebtn.png"/></a>'
			+ '<div style="background-color:red; margin:0 -40px 0; height:4px; width:360px; padding:0;" id="count_red"><div style="background-color:orange; margin:0; height:4px; width:360px; padding:0;" id="div_orange"></div></div>'
			+ '<form style="margin-top:30px;" action="index.php/login?task=user.login" name="login" method="POST">'
			+ '<input type="hidden" name="return" value="'+tokenform+'">'
			+ '<input type="hidden" name="$formToken" value="1">'
			+ '<input type="hidden" name="task" value="$task">'
			+ '<input type="hidden" name="option" value="$option">'
			+ '<input type="hidden" name="openotp_state" value="$session">'
			+ '<input type="hidden" name="openotp_domain" value="$domain">'
			+ '<input type="hidden" name="openotp_username" value="$username">'
			+ '<input type="hidden" name="openotp_ldappw" value="$ldappw">'
			+ '<table width="100%">'
			+ '<tr style="border:none;"><td style="text-align:center; font-weight:bold; font-size:14px; border:none;">$message</td></tr>'
			+ '<tr style="border:none;"><td id="timout_cell" style="text-align:center; padding-top:4px; font-weight:bold; font-style:italic; font-size:11px; border:none;">Timeout: <span id="timeout">$timeout seconds</span></td></tr>'
			+ '<tr style="border:none;"><td id="inputs_cell" style="text-align:center; padding-top:25px; border:none;"><input style="border:1px solid grey; background-color:white; margin-bottom:0; padding:3px; vertical-align:middle;" type="text" size=15 name="openotp_password">&nbsp;'
			+ '<input style="vertical-align:middle; padding:3px 10px;" type="submit" value="Ok" class="button btn btn-primary"></td></tr>'
			+ '</table></form>';
			
			document.body.appendChild(overlay_bg);    
			document.body.appendChild(overlay); 
		}
		
		addOpenOTPDivs();
		
		/* Compute Timeout */	
		var c = $timeout;
		var base = $timeout;
		function count()
		{
			plural = c <= 1 ? "" : "s";
			document.getElementById("timeout").innerHTML = c + " second" + plural;
			var div_width = 360;
			var new_width =  Math.round(c*div_width/base);
			document.getElementById('div_orange').style.width=new_width+'px';
			
			if(c == 0 || c < 0) {
				c = 0;
				clearInterval(timer);
				document.getElementById("timout_cell").innerHTML = " <b style='color:red;'>Login timedout!</b> ";
				document.getElementById("inputs_cell").innerHTML = "<input style='padding:3px 20px;' type='button' value='Retry' class='button btn btn-primary' onclick='window.location.href=\"./\"'>";
			}
			c--;
		}
		count();
		var timer = setInterval(function() {count(); }, 1000);
EOT;

		return $overlay;
	}
	
	private function soapRequest(){
	
		$options = array('location' => $this->server_url);
		if ($this->proxy_host != NULL && $this->proxy_port != NULL) {
			$options['proxy_host'] = $this->proxy_host;
			$options['proxy_port'] = $this->proxy_port;
			if ($this->proxy_username != NULL && $this->proxy_password != NULL) {
				$options['proxy_login'] = $this->proxy_username;
				$options['proxy_password'] = $this->proxy_password;
			}
		}
			
		$soap_client = new SoapClient(dirname(__FILE__).'/openotp.wsdl', $options);
		if (!$soap_client) {
			return false;
		}
		$this->soap_client = $soap_client;	
		return true;
	}
		
	public function openOTPSimpleLogin($username, $domain, $password){
		if (!$this->soapRequest()) return false;
		$resp = $this->soap_client->openotpSimpleLogin($username, $domain, $password, $this->client_id, $_SERVER["REMOTE_ADDR"], $this->user_settings);
		
		return $resp;
	}
	
	public function openOTPChallenge($username, $domain, $state, $password){
		if (!$this->soapRequest()) return false;
		$resp = $this->soap_client->openotpChallenge($username, $domain, $state, $password);
		
		return $resp;
	}
}

?>