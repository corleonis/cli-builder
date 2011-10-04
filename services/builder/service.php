<?php

define( '_JEXEC', 1 );
define('JPATH_BASE', '../../');
define('JPATH_TMP', JPATH_BASE.'tmp/');

require_once(JPATH_BASE.'libraries/import.php');
jimport('joomla.application.cli');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
require_once('builder.php');

require_once(JPATH_BASE.'includes/defines.php'); 
require_once(JPATH_BASE.'includes/framework.php');
$mainframe =& JFactory::getApplication('site');
$mainframe->initialise(); 

class BuilderService extends JCli {
    
    public $type;
    public $modName;
    public $author;
    public $creationDate;
    public $copyright;
    public $license;
    public $authorEmail;
    public $authorUrl;
    public $version;
    public $description;
    public $helpKey;
    
    private $builder;
    
    private $zipFiles = array();
    
    public function execute() {
        
        $this->out('Hello. What do you want to build today:'."\n".'1 - Component; 2 - Plugin; 3 - Module');
        $choice = $this->in();
        
        switch($choice) {
            case 1:
            case 2:
                $this->out('Sorry but we can offer you only Modules :)'); 
                break;
            case 3: 
            default:
                $this->type = 'module';
        }
        
        $this->out('Please enter an extension name in lower case e.g. "testmodule"');
        $extName = JFolder::makeSafe($this->in());

        $this->out('Do you want to install the extension on completion?');
        $this->out("1 - Yes\n2 - No\n");
        $install = $this->in();
        $extInstall = ($install == 1)? true: false;
        
        $manifestPath = 'scaffold/module/';

        $this->builder = new JBuilder($extName, 'module', $extInstall);
        $this->builder->build();
    }    
}
 
JCli::getInstance('BuilderService')->execute();
