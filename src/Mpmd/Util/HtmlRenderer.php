<?php

namespace Mpmd\Util;

/**
 * Class HTML Renderer
 *
 * @author Fabrizio Branca
 * @since 2014-11-26
 */
class HtmlRenderer {

    /**
     * @var array
     */
    protected $head = array();
    protected $body = array();

    /**
     * Render HTML page
     *
     * @return string
     */
    public function render() {

        $output = '<html>';
        foreach ($this->head as $h) {
            $output .= $h;
        }
        foreach ($this->body as $b) {
            $output .= $b;
        }
        $output .= '</html>';
        return $output;
    }

    public function addHead($head) {
        $this->head[] = $head;
        return $this;
    }

    public function addBody($body) {
        $this->body[] = $body;
        return $this;
    }

    public function addJsFile($file) {
        $this->addHead('<script type="text/javascript" src="'.$file.'"></script>');
        return $this;
    }

    public function addJsContent($jsContent) {
        $this->addHead('<script type="text/javascript">'.$jsContent.'</script>');
        return $this;
    }

    public function addCssFile($file) {
        $this->addHead('<link type="text/css" rel="stylesheet" href="'.$file.'" media="all" />');
        return $this;
    }

    public function addCssContent($cssContent) {
        $this->addHead('<style type="text/css">'.$cssContent.'</style>');
        return $this;
    }

    public function addTag($tag, $text, $attributes=array()) {
        foreach ($attributes as $key => $value) {
            $attributes[$key] = "$key=\"$value\"";
        }
        $attributes = implode(' ', $attributes);
        $this->addBody("<$tag $attributes>$text</$tag>");
        return $this;
    }

    public function addHeadline($text, $level=1, $attributes=array()) {
        $this->addTag("h$level", $text, $attributes);
        return $this;
    }

} 