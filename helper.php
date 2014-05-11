<?php
/**
 * DokuWiki Plugin xcom (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Myron Turner <turnermm02@shaw.ca>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

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
}

// vim:ts=4:sw=4:et:
