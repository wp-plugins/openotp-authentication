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

	var redirect_to = document.getElementsByName("redirect_to")[0].value;
	var overlay = document.createElement("div");
	overlay.id = 'openotp_overlay';
	overlay.style.position = 'absolute'; 
	overlay.style.top = '165px'; 
	overlay.style.left = '50%'; 
	overlay.style.width = '280px'; 
	overlay.style.marginLeft = '-180px';
	overlay.style.padding = '65px 40px 50px 40px';
	overlay.style.background = 'url('+otp_settings.openotp_path+'openotp_banner.png) no-repeat top left #E4E4E4';
	overlay.style.border = '5px solid #545454';
	overlay.style.borderRadius = '10px';
	overlay.style.MozBorderRadius = '10px';
	overlay.style.WebkitBorderRadius = '10px';
	overlay.style.boxShadow = '1px 1px 12px #555555';
	overlay.style.WebkitBoxShadow = '1px 1px 12px #555555';
	overlay.style.MozBoxShadow = '1px 1px 12px #555555';
	overlay.style.zIndex = "9999"; 
	oinnerHTML = '<a style="position:absolute; top:-12px; right:-12px; background-color:transparent;" href="wp-login.php" title="close"><img src="'+otp_settings.openotp_path+'openotp_closebtn.png"/></a>'
	+ '<style>'
	+ 'blink { -webkit-animation: blink 1s steps(5, start) infinite; -moz-animation:    blink 1s steps(5, start) infinite; -o-animation:      blink 1s steps(5, start) infinite; animation: blink 1s steps(5, start) infinite; }'
	+ '	@-webkit-keyframes blink { to { visibility: hidden; } }'
	+ '@-moz-keyframes blink { to { visibility: hidden; } }'
	+ '@-o-keyframes blink { to { visibility: hidden; } }'
	+ '@keyframes blink { to { visibility: hidden; } }'
	+ '</style>'	
	+ '<div style="background-color:red; margin:0 -40px 0; height:4px; width:360px; padding:0;" id="count_red"><div style="background-color:orange; margin:0; height:4px; width:360px; padding:0;" id="div_orange"></div></div>'
	+ '<form style="margin:30px 0 0 0; padding:0; background:none; box-shadow:none;" action="wp-login.php" name="loginform1" id="openotpform" method="POST">'
	+ '<input type="hidden" name="redirect_to" value="'+redirect_to+'">'
	+ '<input type="hidden" name="testcookie" value="1">'
	+ '<input type="hidden" name="rememberme" value="'+otp_settings.openotp_rememberme+'">'
	+ '<input type="hidden" name="openotp_state" value="'+otp_settings.openotp_session+'">'
	+ '<input type="hidden" name="openotp_domain" value="'+otp_settings.openotp_domain+'">'
	+ '<input type="hidden" name="openotp_username" value="'+otp_settings.openotp_username+'">'
	+ '<input type="hidden" name="openotp_ldappw" value="'+otp_settings.openotp_ldappw+'">'
	+ '<input type="hidden" name="form_send" value="1">'
	+ '<table width="100%">'
	+ '<tr style="border:none;"><td style="text-align:center; font-weight:bold; font-size:14px; border:none;">'+otp_settings.openotp_message+'</td></tr>'
	+ '<tr style="border:none;"><td id="timout_cell" style="text-align:center; padding-top:4px; font-weight:bold; font-style:italic; font-size:11px; border:none;">Timeout: <span id="timeout">'+otp_settings.openotp_timeout+' seconds</span></td></tr>';
		
	if( otp_settings.openotp_otpChallenge || ( !otp_settings.openotp_otpChallenge && !otp_settings.openotp_u2fChallenge ) ){
	oinnerHTML += '<tr style="border:none;"><td id="inputs_cell" style="text-align:center; padding-top:25px; border:none;"><input style="border:1px solid grey; background-color:white; margin-top:0; margin-bottom:0; padding:3px; vertical-align:middle; font-size:14px; width:auto;" type="text" size=15 name="openotp_password">&nbsp;'
		+ '<input style="vertical-align:middle; padding:0 10px;" name="submit1" type="submit" value="Ok" class="button btn btn-primary"></td></tr>';
	}
	
	if( otp_settings.openotp_u2fChallenge){
		oinnerHTML += '<tr style=\"border:none;\"><td id=\"inputs_cell\" style=\"text-align:center; padding-top:5px; border:none;\"><input type=\"hidden\" name=\"openotp_u2f\" value=\"\">';
		if( otp_settings.openotp_otpChallenge){
			oinnerHTML += '<br/><b>U2F response</b> &nbsp; <blink id=\"u2f_activate\">[Activate Device]</blink></td></tr>';
		} else {
			oinnerHTML += '<img src=\"'+otp_settings.openotp_path+'/u2f.png\"><br><br><blink id=\"u2f_activate\">[Activate Device]</blink></td></tr>';
		}
	}
		
	oinnerHTML += '</table></form>';
	overlay.innerHTML = oinnerHTML;
	
	document.body.appendChild(overlay_bg);    
	document.body.appendChild(overlay); 
}

addOpenOTPDivs();


/* Compute Timeout */	
var c = otp_settings.openotp_timeout;
var base = otp_settings.openotp_timeout;
function count()
{
	plural = c <= 1 ? "" : "s";
	document.getElementById("timeout").innerHTML = c + " second" + plural;
	var div_width = 360;
	var new_width =  Math.round(c*div_width/base);
	document.getElementById('div_orange').style.width=new_width+'px';
	
	if( document.getElementById('openotp_password') ){
		document.getElementById('openotp_password').focus();
	}
	
	if(c == 0 || c < 0) {
		c = 0;
		clearInterval(timer);
		document.getElementById("timout_cell").innerHTML = " <b style='color:red;'>Login timedout!</b> ";
		document.getElementById("inputs_cell").innerHTML = "<input style='padding:0 20px;' type='button' value='Retry' class='button btn btn-primary' onclick='window.location.href=\""+document.URL+"\"'>";
	}
	c--;
}
count();

function getInternetExplorerVersion() {

	var rv = -1;

	if (navigator.appName == "Microsoft Internet Explorer") {
		var ua = navigator.userAgent;
		var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
		if (re.exec(ua) != null)
			rv = parseFloat(RegExp.$1);
	}
	return rv;
}

var ver = getInternetExplorerVersion();

if (navigator.appName == "Microsoft Internet Explorer"){
	if (ver <= 10){
		toggleItem = function(){
			
		    var el = document.getElementsByTagName("blink")[0];
		    if (el.style.display === "block") {
		        el.style.display = "none";
		    } else {
		        el.style.display = "block";
		    }
		}
		var t = setInterval(function() {toggleItem; }, 1000);
	}
}

var timer = setInterval(function() {count();  }, 1000);


if( otp_settings.openotp_u2fChallenge){
	if (typeof u2f !== 'object' || typeof u2f.sign !== 'function'){ var u2f_activate = document.getElementById('u2f_activate'); u2f_activate.innerHTML = '[Not Supported]'; u2f_activate.style.color='red'; }
	else { u2f.sign([ JSON.parse(otp_settings.openotp_u2fChallenge)], 
		function(response) {  
			document.getElementsByName('openotp_u2f')[0].value = JSON.stringify(response); 
			document.getElementById("openotpform").submit(); }, 
			otp_settings.openotp_timeout
		); 
	}
}