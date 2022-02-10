<?php
/**
 * DokuWiki Plugin xcom (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Myron Turner <turnermm02@shaw.ca>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
define('XCOM_ROOT', DOKU_INC . 'lib/plugins/xcom/');
class helper_plugin_xcom extends DokuWiki_Plugin {

    /**
     * Return info about supported methods in this Helper Plugin
     *
     * @return array of public methods
     */
    public function getMethods() {
        return array(
            array(
                'name'   => 'basic',
                'desc'   => 'initializes',
                'params' => array(
                    'namespace'         => 'string'
                ),
                'return' => array('names' => 'array')
            ),
            array(
                // and more supported methods...
            )
        );
    }
   public function basic($namespace) {
       return array();
   }
   
   function write_debug($data) {
        // return;
        if (!$handle = fopen(XCOM_ROOT .'xcom_dbg.txt', 'a')) {
            return;
        }
        if(is_array($data)) {
            $data = print_r($data,true);
        }
        // Write $somecontent to our opened file.
        fwrite($handle, "$data\n");
        fclose($handle);
   }
}

// vim:ts=4:sw=4:et:
