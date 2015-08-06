<?php

namespace htmlpelements;

use \HTMLP\Element as Element;

class BaseElement extends Element {

    public function __construct( $type = '' ) {
        $this->set_type( \HTMLP_Helpers::parse_str($type) );
        $this->set_format( '<%1$s %2$s>%3$s</%1$s>' );
    }

    public function add_child_element( Element $element ) {
        $this->children[] = $element;
    }

    public function append_content( $content, $is_new_line = false  ) {
        if( ! ( $this instanceof EmptyHE ) ) {
            if( $is_new_line || empty( $this->last_paragraph ) ) {
                $this->last_paragraph = new EmptyHE();
                $this->add_child_element( $this->last_paragraph );
            }
            $this->last_paragraph->append_content( str_replace( array( "\n", "\r" ), "", $content ) );
        } else {
            $this->content .= $content;
        }
    }

    public function set_format( $format ) {
        $this->render_format = $format;
    }

    public function set_attributes( $attributes ) {
        $this->attributes = $attributes;
    }

    public function set_content( $content ) {
        $this->content = $content;
    }

    public function set_type( $type ) {
        $this->type = $type;
    }

    public function get_children() {
        return $this->children;
    }

    public function get_the_type() {
        return $this->type;
    }

    public function get_the_attributes() {
        $temp_attr = array();
        foreach( $this->attributes as $key => $value ) {
            if( empty( $value ) || !$value ) {
                $temp_attr[] = $key;
            } else {
                $temp_attr[] = $key . '="' . implode( ' ', $value ) . '"';
            }
        }

        return implode( ' ', $temp_attr );
    }

    public function get_the_content() {
        $renders = '';
        foreach($this->children as $child) {
            $renders .= (string)$child;
        }
        return $renders;
    }

    public function get_the_format() {
        return $this->render_format;
    }

    public function __toString() {
        return sprintf( $this->get_the_format(), $this->get_the_type(), $this->get_the_attributes(), $this->get_the_content() );
    }
}