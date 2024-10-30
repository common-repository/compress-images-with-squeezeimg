<?php

namespace ImagecompressproModalConstructor;

class imagecompresssqueezeimg_modal_constructor {


    private $header;
    private $footer;
    private $bodyTop;
    private $bodyBottom;
    private $back;
    private $output;

    public function __construct()
    {
        $this->back = $this->laod('back');
        $this->header = $this->laod('header');
        $this->bodyBottom = '';
    }

    public function getModal()
    {
        return $this->wrap('',
            $this->back
            . $this->wrap
            (
                'img-compress-heandler',
                $this->wrap('imagecompress-modal-head', $this->header, 'div')
                . $this->bodyBottom
            ),
            'div',
            'id="imagecompress-media-modal"'
        );
    }

    public function addTableBottom($matrix) {
        $table = '<table>';
        foreach ($matrix as $line){
            $table .= '<tr>';
            foreach ($line as $column){
                $table .= '<td>' . $column . '</td>';
            }
            $table .= '</tr>';
        }
        $table .= '</table>';
        $this->bodyBottom .= $table;
    }

    public function addSpanBottom($text)
    {
        $this->bodyBottom .= $this->wrap('', $text, 'span');
    }

    private function wrap($class, $content, $tag = 'div', $other = ''){
        return '<' . $tag .' class="' . $class . '" ' . $other . '>' . $content . '</' . $tag .'>';
    }

    private function laod($template)
    {
        return file_get_contents(IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/partials/modal/' . $template . '.php');
    }
}