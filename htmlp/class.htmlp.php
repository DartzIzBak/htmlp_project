<?php

namespace htmlp;

require_once('inc/elements/htmlelement.class.php');
require_once('inc/elements/custom.htmlelement.class.php');

use htmlpelements;
use htmlpelements\HTMLElement;
use htmlpelements\DocumentHE;
use htmlpelements\BrokenHE;
use htmlpelements\EmptyHE;
use htmlpelements\SelfClosingHE;
use htmlpelements\CommentHE;

class HTMLP {
    /**
     * This contains the whole HTMLP document.
     *
     * @var HTMTElement
     */
    private $document = null;
    /**
     * List of self closing tags.
     *
     * @var array
     */
    private $self_closing = array(
        'img', 'link', 'meta', 'br', 'hr', 'input', 'area', 'base', 'basefont', 'param', 'embed', 'keygen', 'menuitem', 'source', 'wbr', 'track'
    );

    /**
     * List of allowed characters for element names.
     *
     * @var array
     */
    private $allowed_elem_characters;

    /**
     * Prints out the HTML output of the processed HTMLP document.
     */
    public function render() {
        echo $this->document->get_render();
    }

    /**
     * Returns the HTML output of the processed HTMLP document.
     *
     * @return string
     */
    public function get_render() {
        return $this->document->get_render();
    }

    /**
     * Test whether the element given is self-closing
     *
     * @param string $type Element Name
     *
     * @return bool
     */
    public function is_self_closing_element($type) {
        return in_array($type, $this->self_closing);
    }

    /**
     * Process the given content.
     *
     * @param string $file HTMLP Document content
     */
    public function process($file) {

        /* Allowed characters in class & ID */
        $this->allowed_elem_characters = array_merge(range('A', 'Z'), range('a', 'z'));
        $this->allowed_elem_characters = array_merge($this->allowed_elem_characters,  array('@', '(', ')', '-', '_', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'));

        $WORKING_DIR = dirname($file);

        $this->document = new DocumentHE('');
        $content = file_get_contents($file);
        $file = implode('', explode("\n", $content));

        $index = 0;
        $max_index = strlen($file);

        while($index < $max_index) {
            $this->Get_HTMLElement($file, $index, $this->document);
            break;
        }
    }

    /**
     * The name is a little mis-leading, this will be changed in the future. It gets the name
     * of the elements from the nice piece of data we send to it.
     *
     * @param string $elem_name Element name, this also contains attributes. Example: div.my-class
     *
     * @return string
     */
    public function get_name_from_name($elem_name) {
        $i = 0;
        $elem_length = strlen($elem_name);

        while($i < $elem_length) {
            if(in_array($elem_name[$i], $this->allowed_elem_characters)) {
                $i++;
            }
            else {
                break;
            }
        }

        return substr($elem_name, 0, $i);
    }

    /**
     * Get the attributed from the nice piece of data that we send to it.
     *
     * @param string $elem_name Element name, this also contains attributes - which we return. Example: div.my-class
     * @return array
     */
    public function get_attributes_from_name($elem_name) {
        $i = 0;
        $elem_length = strlen($elem_name);

        while($i < $elem_length) {
            if(in_array($elem_name[$i], $this->allowed_elem_characters)) {
                $i++;
            }
            else {
                break;
            }
        }

        $attr = substr($elem_name, $i, $elem_length);

        $attrs = array();
        $key = '';

        if($attr != '') {


            $index = 0;
            $elem_length = strlen($attr);
            $string = '';

            $is_custom_attr = false;
            $is_custom_key = true;
			
			$custom_key = '';
			$custom_value = '';

            while($index < $elem_length) {
				
				if($is_custom_attr) {
					if($attr[$index] == "=") {
						$is_custom_key = false;
					}
					elseif($attr[$index] == "]") {
						$is_custom_attr = false;
						$is_custom_key = true;
						if($custom_key != '') {
							if(!array_key_exists($custom_key, $attrs)) {
								$attrs[$custom_key] = array();
							}
							
							$v_index = 0;
        					$max_length = strlen($custom_value);
							$is_php_echo = false;
							$new_val = '';
							$val_php_echo = '';
							while($v_index < $max_length) {
								if($custom_value[$v_index] == '{') {
									$is_php_echo = true;
								}
								elseif($is_php_echo && $custom_value[$v_index] == '}') {
									$is_php_echo = false;
									$new_val .= eval('global '.$val_php_echo.'; if('.$val_php_echo.' != \'\') { return '.$val_php_echo.'; } else { return \'undefined: '.$val_php_echo.'\'; }');
								}
								elseif($is_php_echo) {
									$val_php_echo .= $custom_value[$v_index];
								} else {
									$new_val .= $custom_value[$v_index];
								}
								$v_index++;
							}
							
							$custom_value = $new_val;
							
							$attrs[$custom_key][] = $custom_value;
							$custom_key = '';
							$custom_value = '';
						}
					}
					elseif($is_custom_key) {
						$custom_key .= $attr[$index];
					} else {
						$custom_value .= $attr[$index];
					}
				}
                elseif($attr[$index] == "[") {
					$is_custom_attr = true;
				}
                elseif($attr[$index] == ".") {
                    if($key != '') {
                        if(!array_key_exists($key, $attrs)) {
                            $attrs[$key] = array();
                        }
                        $attrs[$key][] = $string;
                        $string = '';
                    }
                    $key = 'class';
                }
                elseif($attr[$index] == '#') {
                    if($key != '') {
                        if(!array_key_exists($key, $attrs)) {
                            $attrs[$key] = array();
                        }
                        $attrs[$key][] = $string;
                        $string = '';
                    }
                    $key = 'id';
                }
                elseif($attr[$index] == ' ') {
                    if($key != '') {
                        if(!array_key_exists($key, $attrs)) {
                            $attrs[$key] = array();
                        }
                        $attrs[$key][] = $string;
                        $string = '';
                    }
                    $key = '';
                } else {
                    $string .= $attr[$index];
                }

                $index++;
            }

            if($key != '') {
                if(!array_key_exists($key, $attrs)) {
                    $attrs[$key] = array();
                }
                $attrs[$key][] = $string;
            }
			
            if($custom_key != '') {
                if(!array_key_exists($key, $attrs)) {
                    $attrs[$key] = array();
                }
                $attrs[$key][] = $custom_value;
			}
        }

        return $attrs;
    }

    /**
     * Get the next HTMLElement
     *
     * @param string $file The entire file content.
     * @param int $index The current position in our walker.
     * @param HTMLElement $parent The parent of the next element.
     * 
     * @return HTMLElement
     */
    public function Get_HTMLElement($file, &$index, $parent) {
        $elements_name = '';
        $elements_attr = '';
        $elements_text = '';

        $is_php = false;
        $is_php_hidden = false;
        $is_alt_script = false;
        $is_inclusion = false;

        $thisElement = new HTMLElement('');

        while($file[$index] != '{') {
            $elements_name .= $file[$index];
            $index++;
        }
        $index++;

        if($elements_name[strlen($elements_name)-1] == ' ') {
            $elements_name = substr($elements_name, 0, strlen($elements_name)-1);
        }

        $elements_true_name = $this->get_name_from_name($elements_name);

        $thisElement->type = $elements_true_name;

        $thisElement->set_attributes($this->get_attributes_from_name($elements_name));
		
		/*if($elements_name == '@php') {
            $is_php = true;
			$is_php_hidden = true;
        }*/
        if($elements_name == '@php' || $elements_true_name == '@php') {
            $is_php = true;
			$is_php_hidden = true;
        }
        if($elements_name == 'php' || $elements_true_name == 'php') {
            $is_php = true;
			$is_php_hidden = false;
        }
		
        if($elements_name == 'style' || $elements_true_name == 'style' || $elements_name == 'script' || $elements_true_name == 'script') {
            $is_alt_script = true;
        }
		
        if($elements_name == 'import' || $elements_true_name == 'import') {
            $is_inclusion = true;
    		$thisElement = new EmptyHE("");
        }

        $comment = false;
        $php_closed = true;
        $script_closed = true;
        $php_depth = 0;

        while($file[$index] != '}' || !$php_closed || $comment) {
            if(!$is_php && !$is_alt_script) {
                if(($file[$index] == " " && !$comment) || $file[$index] == "\t" || $file[$index] == "\r") {

                } elseif(($file[$index] == '"' && $file[$index-1] != "\\") || $comment) {
                    if(!$comment && $file[$index] == '"') {
                        $comment = true;
                        $index++;
                    }
                    if($comment && $file[$index] == '"' && $file[$index-1] != "\\") {
                        $comment = false;
                        $index++;
                        if($is_inclusion) {
                        	$htmlp = new \htmlp\HTMLP();
							$htmlp->process($elements_text.'.template');
                        	$thisElement->append_content($htmlp->get_render(), true);
                        	//$elements_text
                        } else {
                        	$thisElement->append_content($elements_text, true);
						}

                        $elements_text = '';
                        continue;
                    }
                    if($file[$index] == '\\' && $file[$index-1] != '\\') {
                        $index++;
                        continue;
                    }

                    $elements_text .= $file[$index];
                } else {
                    /*echo 'Name: ' . $elements_name . '<br/>';
                    echo 'Comment: ' . $elements_text . '<br/>';
                    echo 'Attr: ' . $elements_attr . '<br/><br/>';*/
                    $this->Get_HTMLElement($file, $index, $thisElement);
                }
            } else {
                $elements_text .= $file[$index];

                if($file[$index] == '{') {
                    $php_depth++;
                    $php_closed = false;
                }
                elseif($file[$index] == '}') {
                    $php_depth--;

                    if($php_depth == 0) {
                        $php_closed = true;
                    }
                }
            }
            $index++;
        }

        if($is_php) {

            $line = str_replace("\t", "", $elements_text);
            $script = substr($line, 1 + (($line[1] == ' ') ? 1 : 0), strlen($line));
            $php_result = '';
            if(strlen($script) > 1) {
                ob_start();
                eval($script);
                $php_result = ob_get_clean();
            }
			
			if(!$is_php_hidden && strlen($php_result) > 0) {
		        $thisElement = new EmptyHE("");
		        $thisElement->append_content($php_result);
		        //$thisElement->add_child($phpelement);
			
        		$parent->add_child($thisElement);
        	}
		} elseif($is_alt_script) {

            $line = str_replace("\t", "", $elements_text);
            $script = substr($line, 1 + (($line[1] == ' ') ? 1 : 0), strlen($line));
			
	        $thisElement->append_content($script);
		
    		$parent->add_child($thisElement);
		}/* elseif($is_inclusion) {
			
		} */else {
        	$parent->add_child($thisElement);
		}

        return $this->document;
    }
}