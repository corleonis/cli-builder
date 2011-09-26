<?php
define( '_JEXEC', 1 );
define('JPATH_BASE', dirname(__FILE__));
define('JPATH_SITE', JPATH_BASE);
define('JPATH_TMP', JPATH_SITE.'/tmp');
define('JPATH_SCAFFOLD', JPATH_SITE.'/scaffold');
 
require_once('libraries/import.php');
jimport( 'joomla.application.cli' );
jimport('joomla.factory');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.archive');

class JBuilder extends JCli {
    
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
    
    public function execute() {
        
        $this->out('Hello. What do you want to build today: 1 - Component; 2 - Plugin; 3 - Module');
        $choice = $this->in();
        
        switch($choice) {
            case 1:
            case 2:
                $this->out('Sorry but we can offer you only Modules :)'); 
                $this->type = 'mod';
                break;
            case 3: 
            default:
                $this->type = 'mod';
        }

        $this->buildModule();
    }
    
    protected function buildModule() {
        // There is a bug if you pass a true as 
        // a second parameter it returns the carriage-return 
        // (#xD) character as part of the folowing input
        $this->out('Enter a module name:');
        // Make the module name safe for directories
        $this->modName = JFile::makeSafe($this->in());
        
        $this->out('Author:');
        $this->author = $this->in();

        $this->out('Enter creation date:');
        $this->creationDate = $this->in();
        
        $this->out('Copyright:');
        $this->copyright = $this->in();
        
        $this->out('License:');
        $this->license = $this->in();
        
        $this->out('Author email:');
        $this->authorEmail = $this->in();    
        
        $this->out('Author URL:');
        $this->authorUrl = $this->in();
        
        $this->out('Module version:');
        $this->version = $this->in();
        
        $this->out('Module description:');
        $this->description = $this->in();
        
        $this->out('Help url:');
        $this->helpKey = $this->in();
        
        $this->createDirectoryStructure();
        
        $this->createZipArchive();
    }
    
    protected function getXMLContents() {
        $extension = new SimpleXMLElement('<extension></extension>');        
        $extension->addAttribute('type', 'module');
        $extension->addAttribute('version', '1.7');
        $extension->addAttribute('client', 'site');
        $extension->addAttribute('method', 'upgrade');
            $extension->addChild('name', 'mod_'.$this->modName);
            $extension->addChild('author', $this->author);
            
            $extension->addChild('creationDate', $this->creationDate);
            $extension->addChild('copyright', $this->copyright);
            $extension->addChild('license', $this->license);
            $extension->addChild('authorEmail', $this->authorEmail);
            $extension->addChild('authorUrl', $this->authorUrl);
            $extension->addChild('version', $this->version);
            $extension->addChild('description', $this->description);
            
            $files = $extension->addChild('files');
                $files->addChild('filename', 'mod_'.$this->modName.'.php')
                      ->addAttribute('module', 'mod_'.$this->modName.'.php');
                $files->addChild('folder', 'tmpl');
                $files->addChild('folder', 'fields');
                $files->addChild('filename', 'helper.php');
                $files->addChild('filename', 'index.html');
                $files->addChild('filename', 'mod_'.$this->modName.'.xml');
            $languages = $extension->addChild('languages');
                $languages->addChild('language', 'en-GB.mod_'.$this->modName.'.ini')
                          ->addAttribute('tag', 'en-GB');
                $languages->addChild('language', 'en-GB.mod_'.$this->modName.'.sys.ini')
                          ->addAttribute('tag', 'en-GB');
            $help = $extension->addChild('help')->addAttribute('key', $this->helpKey);
            $config = $extension->addChild('config');
                $fields = $config->addChild('fields');
                $fields->addAttribute('name', 'params');
                    $fieldset = $fields->addChild('fieldset');
                    $fieldset->addAttribute('name', 'basic');
                    $fieldset->addAttribute('addfieldpath', '/modules/mod_'.$this->modName.'/fields');
        
        // Ugly hack to format the XML file        
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($extension->asXML());
        return $dom->saveXML();
    }
    
    protected function createDirectoryStructure() {
        $moduleDir = JPATH_TMP.'/mod_'.$this->modName;
        JFolder::create($moduleDir);
        JFolder::create($moduleDir.'/tmpl');
        JFolder::create($moduleDir.'/fields');
        
        $moduleScaffold = JPATH_SCAFFOLD.'/module';
        $indexFile = JFile::read($moduleScaffold.'/mod_default.php');
        $indexFile = str_replace('{module_name}', $this->modName, $indexFile);
        JFile::write($moduleDir.'/mod_'.$this->modName.'.php', $indexFile);
        
        $helperFile = JFile::read($moduleScaffold.'/helper.php');
        $helperFile = str_replace('{module_name}', $this->modName, $helperFile);
        JFile::write($moduleDir.'/helper.php', $helperFile);
        
        //$xmlFile = JFile::read(JPATH_SCAFFOLD.'/helper.php');
        JFile::write($moduleDir.'/mod_'.$this->modName.'.xml', $this->getXMLContents());
        
        JFile::copy($moduleScaffold.'/index.html', $moduleDir.'/index.html');
        
        JFile::copy($moduleScaffold.'/tmpl/index.html', $moduleDir.'/tmpl/index.html');
        
        JFile::copy($moduleScaffold.'/tmpl/default.php', $moduleDir.'/tmpl/default.php');
    }
    
    protected function createZipArchive( $archiveName='', $filesPath='/mod_testmodel', $files=array() ) {
        $random = rand(1, 99999);
        $archiveName = JPATH_TMP.'/testing_' . strval($random) . '.zip';
        
        $zipAdapter =& JArchive::getAdapter('zip');
        
        $files = JFolder::files(JPATH_TMP.'/mod_testmodel', '', true, true);
        $archiveFiles = array();
        foreach($files as $k => $file) {
            $archiveFiles[$k]['name'] = $file;
        } 
        
        $res = $zipAdapter->create($archiveName, $archiveFiles, 'zip', JPATH_TMP.$filesPath);

        return $archiveName;
    }
    
}
 
JCli::getInstance('JBuilder')->execute( );