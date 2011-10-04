<?php 

jimport('joomla.factory');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.archive');
jimport('joomla.installer.installer');
jimport('joomla.plugin.helper');
jimport('joomla.application.component.helper');
jimport('joomla.application.module.helper');
jimport('joomla.environment.request');

require_once('module.php');
require_once('component.php');
require_once('plugin.php');   

class JBuilder {
    
    private $extName;
    private $className;
    private $extType;
    private $extPrefix;
    private $extInstall;
    private $scaffoldPath;
    private $manifestPath;
    private $manifestFields;
    private $manifest;
    private $filesArray = array();
    private $cli;
    private $prefixes = array(
        'component' => 'com',
        'module'    => 'mod',
        'plugin'    => ''
    );
    
    public function __construct($name, $type, $install = false) {
        if(empty($name) || empty($type)) 
            return false;
        
        if(empty($this->extName)) {
            $this->extName = $name;
        }
        
        if(empty($this->className)) {
            $this->className = ucfirst($name);
        }
        
        if(empty($this->extType)) {
            $this->extType = $type;
        }
        
        if(empty($this->extPrefix) && !empty($this->prefixes[$this->extType])) {
            $this->extPrefix = $this->prefixes[$this->extType].'_';
        }
        
        if($this->extInstall == null) {
            $this->extInstall = (bool)$install;
        }
        
        if(empty($this->cli)) {
            $this->cli = JCli::getInstance();
        }
        
        $this->scaffoldPath = dirname(__FILE__).'/scaffold';
    }
    
    public function build() { 
        $manifest = &$this->getManifestFile();
        $this->setManifestFile($manifest);
        
        $this->createDirectoryStructure($this->extType);
        $this->setFilesArray($this->scaffoldPath.'/'.$this->extType);
        $this->moveFiles();
        $result = $this->createZipArchive();
        
        if($this->extInstall) {
            $this->installPackage();
        }
        
        if($result) {
            JFolder::delete(JPATH_TMP.'mod_'.$this->extName);
        }
    }
    
    public function getManifestFile() {
        if(!empty($this->manifest))
            return $this->manifest;
        
        $this->manifestPath = $this->scaffoldPath.'/'.$this->extType.'/'.$this->extPrefix.'default.xml';
        if(!JFile::exists($this->manifestPath))
            return false;
        
        return $this->manifest = new SimpleXMLElement(JFile::read($this->manifestPath));
    }
    
    function setManifestFile( &$xml, $parent = '' ) {
        $parent .= '/'.$xml->getName();
        $xml = array_shift($xml->xpath($parent));
    
        foreach($xml->attributes() as $attr => $val) {
            $attrValue = (string)$xml->attributes()->{$attr};
            if(empty($attrValue)) {
                $this->cli->out('Enter a value for node\'s "'.$xml->getName().'" attribute "'.$attr.'"');
                $xml->attributes()->{$attr} = $this->cli->in();
            }
        }
        
        foreach($xml->children() as $node => $val) {
            $nodeValue = (string)$xml->{$node};
            if(empty($nodeValue)) {
                $this->cli->out('Enter a value for node "'.(string)$node.'"');
                $xml->{$node} = $this->cli->in();
            }
    
            $this->setManifestFile($xml->{$node}, $parent);
        }
    }
    
    protected function createZipArchive( $archiveName='', $filesPath='', $files=array() ) {
        
        $archiveName = JPATH_TMP.$this->extPrefix.$this->extName.'.zip';
        JFolder::makeSafe($archiveName);
        if(JFile::exists($archiveName)) {
            return false;
        }
        
        $zipAdapter =& JArchive::getAdapter('zip');
        $result = $zipAdapter->create($archiveName, $this->filesArray);

        return $result;
    }
    
    protected function moveFiles() { 
        foreach($this->filesArray as $file) {
            $filePathFrom   = $this->scaffoldPath.'/'.$this->extType.'/'.$file['name'];
            $filePathTo     = JPATH_TMP.$this->extPrefix.$this->extName.'/'.$file['name']; 
            $res = JFile::copy($filePathFrom, $filePathTo);
            
            if($file['name'] == $this->extPrefix.$this->extName.'.xml') {
                $contents = $this->manifest->asXML();
            } else if($file['name'] == $this->extPrefix.$this->extName.'.php') {
                $contents = JFile::read($this->scaffoldPath.'/'.$this->extType.'/'.$this->extPrefix.'default.php');
            } else {
                $contents = JFile::read($filePathFrom);
            }
            
            preg_match_all('#\{\{([a-zA-Z0-9]*)\}\}#', $contents, $placeholders);
            $placeholders = $placeholders[1];
            foreach($placeholders as $val) {
                if(!empty($this->{$val})) {
                    $contents = str_replace('{{'.$val.'}}', $this->{$val}, $contents);
                    JFile::write($filePathTo, $contents);
                }
            }
        }
    }
    
    protected function setFilesArray( $dirPath, $subDir='' ) {
        
        $files = JFolder::files($dirPath, '.', false, true);
        $dirs = JFolder::folders($dirPath);
        
        if(!empty($subDir)) {
            $subDir .= '/';
        }
        
        if(!empty($files) && is_array($files)) {
            foreach($files as $k => $file) {
                $fileName = str_replace($this->extPrefix.'default', $this->extPrefix.$this->extName, JFile::getName($file));
                $this->filesArray[] = array(
                    'name' => $subDir.$fileName,
                    'data' => JFile::read($file)
                ); 
            } 
        }
        
        if(!empty($dirs) && is_array($dirs)) { 
            foreach($dirs as $dir) { 
                $this->setFilesArray($dirPath.'/'.$dir, $subDir.$dir);
            }
        }
        
    }
    
    protected function createDirectoryStructure( $dirName, $subDir = '' ) {
        $dirs = JFolder::folders($this->scaffoldPath.'/'.$dirName);
        
        if(!empty($dirs)) { 
            foreach($dirs as $dir) {
                $this->createDirectoryStructure($dirName.'/'.$dir, $subDir.'/'.$dir);
            }
        } else {
            $extDir = JPATH_TMP.$this->extPrefix.$this->extName.'/'.$subDir; 
            if(!JFolder::exists($extDir)) {
                JFolder::create($extDir);
            }
        }
    }
    
    protected function installPackage() {
        $packagePath = JPATH_TMP.$this->extPrefix.$this->extName;
        if(!JFolder::exists($packagePath)) {
            return false;
        }

        $installer = new JInstaller();
        $result = $installer->install($packagePath);
        
        return $result;
    }
    
    public function setName( $name ) {
        $this->extName = $name;
    }
    
    public function getName() {
        return $this->extName;
    }
    
    public function getType() {
        return $this->extType;
    }
    
    public function getPrefix() {
        return $this->extPrefix;
    }
    
    private function setScaffoldPath( $path ) {
        if(JFolder::exists($path)) {
            $this->scaffoldPath = $path;
        } else {
            return false;
        }
    }
    
    public function getPath() {
        return $this->scaffoldPath;
    }
    
}