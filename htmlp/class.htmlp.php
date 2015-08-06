<?php

namespace htmlp;

# String class
require_once('inc/string.lib.php');

require_once('class.helpers.php');

require_once('inc/abstract/abstract.element.php');
require_once('inc/elements/base.element.php');
require_once('inc/elements/document.element.php');
require_once('inc/elements/self-closing.element.php');
require_once('inc/elements/meta.element.php');
require_once('inc/elements/php.element.php');
require_once('inc/elements/comment.element.php');
require_once('inc/elements/empty.element.php');

use htmlpelements;
use htmlpelements\BaseElement as BaseElement;
use htmlpelements\DocumentHE as DocumentHE;
use htmlpelements\BrokenHE as BrokenHE;
use htmlpelements\EmptyHE as EmptyHE;
use htmlpelements\SelfClosingHE as SelfClosingHE;
use htmlpelements\CommentHE as CommentHE;

class HTMLP
{
    /**
     * This contains the whole HTMLP document.
     *
     * @var Element
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
    public function render()
    {
        echo $this->get_render();
    }

    /**
     * Returns the HTML output of the processed HTMLP document.
     *
     * @return string
     */
    public function get_render()
    {
        return (string)$this->document;
    }

    /**
     * Test whether the element given is self-closing
     *
     * @param string $type Element Name
     *
     * @return bool
     */
    public function is_self_closing_element($type)
    {
        return in_array($type, $this->self_closing);
    }

    /**
     * Process the given content.
     *
     * @param string $file HTMLP Document content
     */
    public function process($file)
    {

        # If the file does not exist, throw an error.
        if (!file_exists($file)) {
            throw new \Exception("File cannot be found: {$file}.");
        }

        # Load the file.
        $content = file_get_contents($file);

        # List all the allowed characters.
        $this->allowed_elem_characters = array_merge(range('A', 'Z'), range('a', 'z'));
        $this->allowed_elem_characters = array_merge($this->allowed_elem_characters, array('@', '(', ')', '-', '_', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'));

        $this->document = new \htmlpelements\DocumentHE();
        $file = implode('', explode("\n", $content));

        $index = 0;
        $max_index = strlen($file);

        while ($index < $max_index) {
            $this->nextElement($file, $index, $this->document);
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
    public function get_name_from_name($elem_name)
    {
        $i = 0;
        $elem_length = strlen($elem_name);

        while ($i < $elem_length) {
            if (in_array($elem_name[$i], $this->allowed_elem_characters)) {
                $i++;
            } else {
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
    public function get_attributes_from_name($elem_name)
    {
        $i = 0;
        $elem_length = strlen($elem_name);

        while ($i < $elem_length) {
            if (in_array($elem_name[$i], $this->allowed_elem_characters)) {
                $i++;
            } else {
                break;
            }
        }

        $attr = substr($elem_name, $i, $elem_length);

        $attrs = array();
        $key = '';

        if ($attr != '') {


            $index = 0;
            $elem_length = strlen($attr);
            $string = '';

            $is_custom_attr = false;
            $is_custom_key = true;

            $custom_key = '';
            $custom_value = '';

            while ($index < $elem_length) {

                if ($is_custom_attr) {
                    if ($attr[$index] == "=") {
                        $is_custom_key = false;
                    } elseif ($attr[$index] == "]") {
                        $is_custom_attr = false;
                        $is_custom_key = true;
                        if ($custom_key != '') {
                            if (!array_key_exists($custom_key, $attrs)) {
                                $attrs[$custom_key] = array();
                            }

                            $v_index = 0;
                            $max_length = strlen($custom_value);
                            $is_php_echo = false;
                            $new_val = '';
                            $val_php_echo = '';
                            while ($v_index < $max_length) {
                                if ($custom_value[$v_index] == '{') {
                                    $is_php_echo = true;
                                } elseif ($is_php_echo && $custom_value[$v_index] == '}') {
                                    $is_php_echo = false;
                                    $new_val .= eval('global ' . $val_php_echo . '; if(' . $val_php_echo . ' != \'\') { return ' . $val_php_echo . '; } else { return \'undefined: ' . $val_php_echo . '\'; }');
                                } elseif ($is_php_echo) {
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
                    } elseif ($is_custom_key) {
                        $custom_key .= $attr[$index];
                    } else {
                        $custom_value .= $attr[$index];
                    }
                } elseif ($attr[$index] == "[") {
                    $is_custom_attr = true;
                } elseif ($attr[$index] == ".") {
                    if ($key != '') {
                        if (!array_key_exists($key, $attrs)) {
                            $attrs[$key] = array();
                        }
                        $attrs[$key][] = $string;
                        $string = '';
                    }
                    $key = 'class';
                } elseif ($attr[$index] == '#') {
                    if ($key != '') {
                        if (!array_key_exists($key, $attrs)) {
                            $attrs[$key] = array();
                        }
                        $attrs[$key][] = $string;
                        $string = '';
                    }
                    $key = 'id';
                } elseif ($attr[$index] == ' ') {
                    if ($key != '') {
                        if (!array_key_exists($key, $attrs)) {
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

            if ($key != '') {
                if (!array_key_exists($key, $attrs)) {
                    $attrs[$key] = array();
                }
                $attrs[$key][] = $string;
            }

            if ($custom_key != '') {
                if (!array_key_exists($key, $attrs)) {
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
     * @param BaseElement $parent The parent of the next element.
     *
     * @return BaseElement
     */
    public function nextElement($file, &$index, $parent)
    {
        $string = new \String;
        $string->set($file);

        $elements_name = '';
        $elements_text = '';

        $comment = false;
        $php_closed = true;
        $php_depth = 0;

        $is_php = $is_php_hidden = $is_alt_script = $is_inclusion = false;

        $thisElement = new BaseElement();

        while ($string->charAt($index) != '{') {
            $elements_name .= $string->charAt($index);
            $index++;
        }

        $index++;

        if ($elements_name[strlen($elements_name) - 1] == ' ') {
            $elements_name = substr($elements_name, 0, strlen($elements_name) - 1);
        }

        $elements_true_name = $this->get_name_from_name($elements_name);

        $thisElement->set_type($elements_true_name);
        $thisElement->set_attributes($this->get_attributes_from_name($elements_name));

        switch ($elements_true_name) {
            case '@php':
                $is_php = true;
                $is_php_hidden = true;
                break;

            case 'php':
                $is_php = true;
                $is_php_hidden = false;
                break;

            case 'style':
            case 'script':
                $is_alt_script = true;
                break;

            case 'import':
                $is_inclusion = true;
                $thisElement = new EmptyHE();
                break;
        }

        while ($file[$index] != '}' || !$php_closed || $comment) {

            if (!$is_php && !$is_alt_script) {

                if (($file[$index] == " " && !$comment) || $file[$index] == "\t" || $file[$index] == "\r") {

                    # Skip any empty characters

                } elseif (($file[$index] == '"' && $file[$index - 1] != "\\") || $comment) {

                    if (!$comment && $file[$index] == '"') {

                        $comment = true;
                        $index++;

                    }

                    if ($comment && $file[$index] == '"' && $file[$index - 1] != "\\") {

                        $comment = false;
                        $index++;

                        if ($is_inclusion) {

                            $htmlp = new \htmlp\HTMLP();
                            $htmlp->process($elements_text . '.template');
                            $thisElement->append_content($htmlp->get_render(), true);

                        } else {
                            $thisElement->append_content($elements_text, true);
                        }

                        $elements_text = '';
                        continue;
                    }
                    if ($file[$index] == '\\' && $file[$index - 1] != '\\') {
                        $index++;
                        continue;
                    }

                    $elements_text .= $file[$index];
                } else {
                    $this->nextElement($file, $index, $thisElement);
                }
            } else {
                $elements_text .= $file[$index];

                if ($file[$index] == '{') {
                    $php_depth++;
                    $php_closed = false;
                } elseif ($file[$index] == '}') {
                    $php_depth--;

                    if ($php_depth == 0) {
                        $php_closed = true;
                    }
                }
            }
            $index++;
        }

        if ($is_php) {

            $line = str_replace("\t", "", $elements_text);
            $script = substr($line, 1 + (($line[1] == ' ') ? 1 : 0), strlen($line));
            $php_result = '';
            if (strlen($script) > 1) {
                ob_start();
                eval($script);
                $php_result = ob_get_clean();
            }

            if (!$is_php_hidden && strlen($php_result) > 0) {
                $thisElement = new EmptyHE("");
                $thisElement->append_content($php_result);
                $parent->add_child_element($thisElement);
            }
        } elseif ($is_alt_script) {

            $line = str_replace("\t", "", $elements_text);
            $script = substr($line, 1 + (($line[1] == ' ') ? 1 : 0), strlen($line));

            $thisElement->append_content($script);

            $parent->add_child_element($thisElement);
        } else {
            $parent->add_child_element($thisElement);
        }

        return $this->document;
    }
}