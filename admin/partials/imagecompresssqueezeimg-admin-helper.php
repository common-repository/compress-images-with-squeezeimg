<?php

$image = new \images_compress_squeezeimg\images();

function getMagicCheckBox($settings, $name) {
    $activeCecked = '';
    $disableCecked = '';
    $activeClass = '';
    if (isset($settings[$name]) and $settings[$name] == 'A') {
        $activeCecked = 'checked=\"checked\"';
        $activeClass = 'active-imagecompresssqueezeimg-radio-mgic';
    } else {
        $disableCecked = 'checked=\"checked\"';
    }
    $html = "<div data-target-name='$name' class=\"imagecompresssqueezeimg-radio-mgic  $activeClass\">
                        <div>
                            <input
                                    data-target-switch=\"active\"
                                    data-state-switch=\"disable\"
                                    type=\"radio\"
                                    name=\"$name\"
                                    value=\"A\"
                                    $activeCecked
                            >
                            <input
                                    data-target-switch=\"disable\"
                                    data-state-switch=\"active\"
                                    type=\"radio\"
                                    name=\"$name\"
                                    value=\"D\"
                                    $disableCecked
                            >
                        </div>
                    </div>";
    return $html;
}

function  getMagicSelect($settings, $name, $selects) {

    $html = "<select name='$name'>";
    foreach ($selects as $select) {
        if (isset($settings[$name]) and $settings[$name] == $select['val']) {
            $activeSelected = 'selected=\"selected\"';
        } else {
            $activeSelected = '';
        }
        $html .= "<option $activeSelected value='" . $select['val'] . "'>" . $select['text'] . "</option>";
    }
    $html .= "</select>";
    return $html;
}

function getMagicInput($settings, $name, $value = null) {

    if (!$value) {
        if(isset($settings[$name])){
            $value = $settings[$name];
        }
    }

    $html = '<input type="text" name="' . $name . '" value="' . esc_html(urldecode($value)) . '">';
    return $html;
}

function getMagicTrueFalse($array, $key)
{
    if(isset($array[$key]) and $array[$key] == 'A')
    {
        return _e(" On",'imagecompresssqueezeimg');
    } else {
        return _e(" Off",'imagecompresssqueezeimg');
    }
}

function getPrloadersPath(){
    $output = [];
    $preloaderPath = IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/images/prealoaders';
    $preloaderUrl = plugins_url('admin/images/prealoaders', IMAGECOMPRESSSQUEEZ_PLUGIN_FILE);
    $files = [];
    foreach (scandir($preloaderPath) as $key => $file)
    {
        if($file == '..' or $file == '.'){continue;}
        $files[] = $preloaderUrl . '/' . $file;
    }
    $output['folder'] = ' ' . IMAGECOMPRESSSQUEEZ_CONTENT_BASE
        . '/plugins/imagecompresssqueezeimg/admin/images/prealoaders';
    $output['files'] = $files;
    return $output;
}
