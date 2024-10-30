<?php

namespace Includes;

class imagecompresssqueezeimg_includes {

    private $adminStyle;

    private $adminStyleUrl;

    private $adminJs;

    private $adminJsUrl;

    private $pluginsUrl;

    private $configSet;

    private $mediaJs;

    public function __construct()
    {
        $this->setConfigs();
    }

    public function getAdminStyleUrl() {
        return $this->pluginsUrl . '/' . $this->adminStyleUrl;
    }

    public function getAdminStyle() {
        return $this->adminStyle;
    }

    public function getAdminJsUrl() {
        return $this->pluginsUrl . '/' . $this->adminJsUrl;
    }

    public function getAdminJs() {
        return $this->adminJs;
    }

    public function getMediaJs() {
        return $this->mediaJs;
    }

    private function setConfigs(){
        $this->configSet = true;
        $this->pluginsUrl = plugins_url();
        $this->adminStyle = 'imagecompresssqueezeimg-admin';
        $this->adminStyleUrl = 'imagecompresssqueezeimg/admin/css';
        $this->adminJs = 'imagecompresssqueezeimg-admin.js';
        $this->mediaJs = 'imagecompresssqueezeimg-media.js';
        $this->adminJsUrl = 'imagecompresssqueezeimg/admin/js';
    }
}