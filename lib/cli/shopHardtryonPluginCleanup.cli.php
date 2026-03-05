<?php
class shopHardtryonPluginCleanupCli extends waCliController
{
    public function execute()
    {
        shopHardtryonPlugin::cleanupOldFiles();
        echo "HARD AI Try-On cleanup completed\n";
    }
}
