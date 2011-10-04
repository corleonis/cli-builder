<?php 

class JBuilderModule extends JBuilder {
    
    public function setManifestFile() {
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
    }
}