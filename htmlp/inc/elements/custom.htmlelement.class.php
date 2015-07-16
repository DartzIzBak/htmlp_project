<?php

namespace htmlpelements;

class EmptyHE extends HTMLElement {
    public function get_content() {
        return $this->content;
    }
    public function render_format() {
        return '%3$s';
    }
}

class DocumentHE extends HTMLElement {
    public function render_format() {
        return '%3$s';
    }
}

class SelfClosingHE extends HTMLElement {
    public function render_format() {
        return '<%1$s %2$s />';
    }
}

class BrokenHE extends HTMLElement {
    public function render_format() {
        return '<%1$s %2$s>';
    }
    public function get_attributes() {
        $temp_attr = array();
        foreach($this->attributes as $key=>$value) {
            $temp_attr[] = $key;
        }

        return implode(' ', $temp_attr);
    }
}

class PHPIOHE extends HTMLElement {
    public function render_format() {
        return '<?%1$s %3$s ?>';
    }
    public function get_attributes() {
        $temp_attr = array();
        foreach($this->attributes as $key=>$value) {
            $temp_attr[] = $key;
        }

        return implode(' ', $temp_attr);
    }
}

class CommentHE extends HTMLElement {
    public function render_format() {
        return '<!-- %3$s -->';
    }
}

class PHPHE extends HTMLElement {
    public function get_render() {
        echo 'Executing... ' . $this->content;
        if(strlen($this->content) > 0) {
            return eval($this->content);
        }
        return '';
    }
    public function append_content($content, $new_line = false) {
        $this->content = $content;
    }
}