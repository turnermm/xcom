<?php
/**
 * DokuWiki Plugin xcom (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Myron Turner <turnermm02@shaw.ca>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_xcom extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

       $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'handle_dokuwiki_started');
   
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */

    public function handle_dokuwiki_started(Doku_Event &$event, $param) {
        global $JSINFO, $INFO;
        $JSINFO['pwdhide'] = $this->getLang('pwdhide');
        $JSINFO['pwdview'] = $this->getLang('pwdview');
        $inidir = trim($this->getConf('inidir'));
         if(!$inidir) {
               $inidir = DOKU_PLUGIN . 'xcom/scripts/';
         }             
         else {
             $inidir = rtrim($inidir,'/') . '/';
         }      
        $ini_file = $inidir . 'xcom.ini';
       
        $JSINFO['xcom_sites'] = array();
        $ini = parse_ini_file($ini_file,1);
        foreach ($ini as $name=>$site) {
            $JSINFO['xcom_sites'][$name] = array('url'=>'','user'=>'','pwd'=>'');   
            foreach($site as $item=>$val) {
                $JSINFO['xcom_sites'][$name][$item] = $val;       
            }
    }

    }        
}

// vim:ts=4:sw=4:et:
