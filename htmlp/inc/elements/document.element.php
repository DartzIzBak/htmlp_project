<?php

namespace htmlpelements;

class DocumentHE extends BaseElement {
    public function __construct( $type = '' ) {
        parent::__construct();
        $this->set_format( '%3$s' );
    }
}