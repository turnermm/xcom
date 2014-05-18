<?php
/**
 * Plugin XCOM"
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 */

 
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
if(!defined('DOKU_INC')) die();

class admin_plugin_xcom extends DokuWiki_Admin_Plugin {
     private $local_user;
     function __construct() {
         global $INFO;
         $this->local_user = $INFO['client'];

     }
    
     function forAdminOnly(){
        return false;
    }

    /**
     * handle user request
     */
    function handle() {

    }
     
    /**
     * output appropriate html
     */
    function html() {
   /** 
            info panels
   */

 /**
        Instructions
 */
      ptln('<div id="xcom_howto" style="display:none;border:1px black solid;padding:12px 12px 12px 8px;height:400px;overflow:auto;">' );      
      ptln('<button  style = "float:right;" onclick="xcom_toggle(\'#xcom_howto\')">' . $this->getLang('close'). '</button>&nbsp;' . $this->locale_xhtml('howto'));
      ptln('<button  style = "float:right" onclick="xcom_toggle(\'#xcom_howto\')">' . $this->getLang('close'). '</button>&nbsp;<br /><br /></div>');
    
 /**
        Functions
 */    
      ptln('<div id="xcom_functions" style="display:none;border:1px black solid;padding:12px 12px 12px 8px;height:400px;overflow:auto;">' );
      ptln('<b>' . $this->getLang('xmlrpc_fns') . ': </b><a href="https:/dokuwiki.org/xmlrpc">https://dokuwiki.org/devel:xmlrpc</a>');
      ptln('<button  style = "float:right;" onclick="xcom_toggle(\'#xcom_functions\')">' . $this->getLang('close'). '</button>&nbsp;' . $this->locale_xhtml('functions'));
      ptln('<button  style = "float:right" onclick="xcom_toggle(\'#xcom_functions\')">' . $this->getLang('close'). '</button>&nbsp;<br /><br /></div>');
 /**
      Toggles and function buttons
*/ 
      ptln('<div style="margin-bottom:8px;">');          
      ptln('<button onclick=" xcom_toggle(\'#xcom_functions\')">'. $this->getLang('functions') .'</button>&nbsp;');       
      ptln('<button onclick=" xcom_toggle(\'#xcom_howto\')">'. $this->getLang('howto') .'</button>&nbsp;');       
      ptln('<button class="xcom_send_but" onclick="xmlrpc()">' .  $this->getLang('send')  .'</button>');  
      
      ptln('<button style="float:right;margin-left:8px;"  title="' . $this->getLang('results_tip').  '"onclick="xcom_show(\'xcom_results\');">' . $this->getLang('results'). '</button>');
       ptln('<button style="float:right;margin-left:8px;"  onclick="xcom_toggle(\'#xcom_editable\');">' . $this->getLang('editable'). '</button>');
       ptln('<button style="float:right;margin-left:8px;"  onclick="xcom_toggle(\'#xcom_htm\');">' . $this->getLang('html'). '</button>');
       ptln('<button style="float:right;margin-left:8px;"  onclick="xcom_toggle(\'#xcom_pre\');">' . $this->getLang('pre'). '</button>');
      ptln('<button style="float:right;margin-left:8px;"  title="' . $this->getLang('results_tip').  '"onclick="xcom_hide_all_views();">' . $this->getLang('hideallviews'). '</button>');      
      ptln('</div>');
      
      ptln('<form action="'.wl($ID).'" method="post" name ="xcom_post">');            
      ptln('  <input type="hidden" name="do"   value="admin" />');
      ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
      formSecurityToken();
    /**
            credentials
       */
       // Remote Credentials
      ptln( $this->getLang('url').': <input type="text"  size = "40"  name="xcom_url" id = "xcom_url">&nbsp;');
      ptln( $this->getLang('user').': <input type="text" size = "12" name="xcom_user"  id = "xcom_user">&nbsp;');
      ptln( $this->getLang('pwd').': <input type="password" size = "9" name="xcom_pwd"  id = "xcom_pwd">');
      ptln('&nbsp;<img src="' . DOKU_REL .  'lib/plugins/xcom/images/eye_blk.png"  title="'. $this->getLang('pwdview') . '" name="xcom_eye" id ="xcom_eye" />');          
      ptln('</form>');  
     
    /**  
          Selection buttons
     */          
      ptln ('<div style = "padding-top: 8px;">');
      ptln('<form>');
      
      ptln('<select id = "xcom_sel"><option value="none">' .  $this->getLang('select')  .'</option></select>&nbsp;' );      
      ptln( $this->getLang('pageid').': <input type="text"  name="xcom_pageid" value="" id = "xcom_pageid">&nbsp;');   
      ptln('<span title="'. $this->getLang('options_title') .'">' . $this->getLang('options').':</span> <input type="text"  name="xcom_opts" size="40" id = "xcom_opts" title="'. $this->getLang('options_title').'">');         
   
            // Local User and Password 
      ptln('<div class="xcom_sites">');     
      ptln('<select id = "xcom_selsites" onchange="xcom_select(this);"><option value="none">' .  $this->getLang('sel_sites')  .'</option></select>&nbsp;' );           
      ptln ('<div class="local_side">');            
      ptln( $this->getLang('locuser').': <input type="text" size = "12" value="' . $this->local_user .  '" name="xcom_locuser"  id = "xcom_locuser">&nbsp;');
      ptln($this->getLang('localpwd'). ': <input type="password" size = "9" name="xcom_localpwd"  id = "xcom_localpwd">');     
      ptln('&nbsp;<img src="' . DOKU_REL .  'lib/plugins/xcom/images/eye_blk.png"  title="'. $this->getLang('pwdview') . '" name="xcom_loceye" id ="xcom_loceye" />');                
      ptln('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>') ;     
      ptln('</div>');     
      
      ptln('</form>');      
      ptln( '</div>');
  
      /**
         Output
      */  
      ptln('<div>');      
     // ptln ('<div class="xcom_view_status" id="xcom_view"></div>');
      ptln('<div id = "xcom_results"  style ="display:none;border: 1px solid #ddd;" >');   //start results
      ptln('<div id = "xcom_editable_title" style ="display:none;">' . $this->getLang('editable') . '</div><div class="xcom_editdiv"><form><textarea  style ="display:none;margin:auto;"  name="xcom_editable" cols="120" rows="16" id = "xcom_editable" style="margin-bottom:8px;"></textarea></form></div>');    
      ptln('<div id = "xcom_pre_title" style ="display:none;">' . $this->getLang('pre') . '</div><div id = "xcom_pre"  style ="display:none;white-space:pre;" ></div>');  
      ptln('<div id = "xcom_htm_title" style ="display:none;">' . $this->getLang('html') . '</div><div id = "xcom_htm"  style ="display:none;" ></div>');          
      ptln('<br /><button onclick="xcom_hide(\'xcom_results\');">' . $this->getLang('close'). '</button>&nbsp;</div>'); //close/end results
      ptln( '</div>');  
      
      /**
        Status Bar
      */
      ptln('<div id = "xcom_status">');   
      ptln($this->getLang('clear') . ':&nbsp;');
      ptln('<a href="javascript:xcom_clear(\'xcom_pre\');void 0;">[' . $this->getLang('pre') . ']</a>&nbsp;');
      ptln('<a href="javascript:xcom_clear(\'xcom_htm\');void 0;">[' . $this->getLang('html') . ']</a>&nbsp;');
      ptln('<a href="javascript:xcom_clear(\'xcom_editable\');void 0;">[' . $this->getLang('editable') . ']</a>&nbsp;&nbsp;');
      ptln('&nbsp;<span class="xcom_qslabel" id="xcom_qslabel">' . $this->getLang('query') . ':</span>');
      ptln('&nbsp;<span class="xcom_qstatus" id="xcom_qstatus"></span>');
      ptln('<a href="javascript:xmlrpc();void 0;"><span class="xcom_send_link" onmouseover="xcom_rollover(this,1);" onmouseout="xcom_rollover(this,0);">[' .  $this->getLang('send') .']</span></a>&nbsp;&nbsp;');       
      ptln( '</div>');        
    
    }
}