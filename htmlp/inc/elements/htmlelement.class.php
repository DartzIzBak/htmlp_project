<?php

namespace htmlpelements;

class HTMLElement {
    public $type = '';
    public $attributes = array();
    public $content = '';
    public $children = array();
    public $last_paragraph = null;

    public function __construct($type) {
        $this->type = preg_replace('/[^a-zA-Z0-9!]/', '', $type);
    }

    public function add_child(HTMLElement $child) {
        $this->children[] = $child;
    }

    public function get_children() {
        return $this->children;
    }

    public function append_content($content, $new_line = false) {
        if(!($this instanceof EmptyHE)) {
            if($new_line || $this->last_paragraph == null) {
                $this->last_paragraph = new EmptyHE('');
                $this->add_child($this->last_paragraph);
            }
            $this->last_paragraph->append_content(str_replace(array("\n", "\r"), "", $content));
        } else {
            $this->content .= $content;
        }
    }

    public function get_type() {
        return $this->type;
    }

    public function set_attributes(array $attr) {
        $this->attributes = $attr;
    }

    public function get_attributes() {
        $temp_attr = array();
        foreach($this->attributes as $key=>$value) {
            if($value == '' || $value == null || count($value) == 0 || !isset($value[0])) {
                $temp_attr[] = $key;
            } else {
                $temp_attr[] = $key . '="' . implode(' ', $value) . '"';
            }
        }

        return implode(' ', $temp_attr);
    }

    public function get_content() {
        $renders = '';
        foreach($this->children as $child) {
            $renders .= $child->get_render();
        }
        return $renders;
    }

    public function render_format() {
        return '<%1$s %2$s>%3$s</%1$s>';
    }

    public function get_render() {
        return sprintf($this->render_format(), $this->get_type(), $this->get_attributes(), $this->get_content());
    }
}