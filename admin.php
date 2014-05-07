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
class admin_plugin_xcom extends DokuWiki_Admin_Plugin {


  
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
      ptln('<button onclick="xmlrpc()">' .  $this->getLang('send')  .'</button>');  
      ptln('<button style="float:right;margin-left:8px;"  title="' . $this->getLang('results_tip').  '"onclick="xcom_toggle(\'#xcom_results\');">' . $this->getLang('results'). '</button>');
       ptln('<button style="float:right;margin-left:8px;"  onclick="xcom_toggle(\'#xcom_editable\');">' . $this->getLang('editable'). '</button>');
       ptln('<button style="float:right;margin-left:8px;"  onclick="xcom_toggle(\'#xcom_htm\');">' . $this->getLang('html'). '</button>');
       ptln('<button style="float:right;margin-left:8px;"  onclick="xcom_toggle(\'#xcom_pre\');">' . $this->getLang('pre'). '</button>');
      ptln('</div>');
      
      ptln('<form action="'.wl($ID).'" method="post" name ="xcom_post">');            
      ptln('  <input type="hidden" name="do"   value="admin" />');
      ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
      formSecurityToken();
    /**
            credentials
       */
      ptln( $this->getLang('url').': <input type="text"  size = "40"  name="xcom_url" id = "xcom_url">&nbsp;');
      ptln( $this->getLang('user').': <input type="text"  name="xcom_user"  id = "xcom_user">&nbsp;');
      ptln( $this->getLang('pwd').': <input type="text"  name="xcom_pwd"  id = "xcom_pwd"><br />');         
      ptln('</form>');  
     
    /**  
          Selection buttons
     */          
      ptln ('<div style = "padding-top: 8px;">');
      ptln('<form>');
      ptln('<select id = "xcom_sel" onchange="xcom_select(this);"><option value="none">' .  $this->getLang('select')  .'</option></select>&nbsp;' );      
      ptln( $this->getLang('pageid').': <input type="text"  name="xcom_pageid" value="start" id = "xcom_pageid">&nbsp;');   
      ptln( $this->getLang('options').': <input type="text"  name="xcom_opts" size="40" id = "xcom_opts">');         
      ptln('</form>');      
      ptln( '</div>');
  
      /**
         Output
      */  
      ptln('<div>');      
      ptln('<div id = "xcom_results"  style ="display:none;border: 1px solid #ddd;" >');   //start results
      ptln('<div class="xcom_editdiv"><form><textarea  style ="display:none;margin:auto;"  name="xcom_editable" cols="120" rows="16" id = "xcom_editable" style="margin-bottom:8px;"></textarea></form></div>');    
      ptln('<div id = "xcom_pre"  style ="display:none;white-space:pre;" ></div>');  
      ptln('<div id = "xcom_htm"  style ="display:none;" ></div>');          
      ptln('<br /><button onclick="xcom_hide(\'xcom_results\');">' . $this->getLang('close'). '</button>&nbsp;</div>'); //close/end results
      ptln( '</div>');  
      ptln('<div id = "xcom_status">');   
      ptln('Clear window:&nbsp;&nbsp;');
      ptln('<a href="javascript:xcom_clear(\'xcom_pre\');void 0;">[Code view]</a>&nbsp;');
      ptln('<a href="javascript:xcom_clear(\'xcom_htm\');void 0;">[HTML view]</a>&nbsp;');
      ptln('<a href="javascript:xcom_clear(\'xcom_editable\');void 0;">[Edit window]</a>&nbsp;&nbsp;');
      ptln( '</div>');        
    
    }
}