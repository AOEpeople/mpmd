<?php

namespace Mpmd\Util;

/**
 * Class HTML Table Renderer
 *
 * @author Fabrizio Branca
 * @since 2014-11-26
 */
class HtmlTableRenderer {

    /**
     * @var array
     */
    protected $_decorators = array();

    /**
     * Render table
     *
     * @param array $table
     * @param array $labels
     * @return string
     */
    public function render(array $table, array $labels=null) {
        $output = '<table>';
        if (!is_null($labels)) {
            $output .= '<thead><tr>';
            foreach ($labels as $column) {
                $output .= '<th>'.$column.'</th>';
            }
            $output .= '</thead>';
        }
        $output .= '</tr><tbody>';
        foreach ($table as $row) {
            $output .= '<tr>';
            foreach ($labels as $column) {
                $output .= '<td>'.$this->renderCell($column, $row[$column]).'</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';

        return $output;
    }

    /**
     * Add decorator
     *
     * @param $column
     * @param $callback
     * @return $this
     */
    public function addDecorator($column, $callback)
    {
        if (empty($column)) {
            throw new \InvalidArgumentException('Invalid column name');
        }
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback');
        }
        if (!isset($this->_decorators[$column])) {
            $this->_decorators[$column] = array();
        }
        $this->_decorators[$column][] = $callback;
        return $this;
    }

    /**
     * Render cell
     *
     * @param $column
     * @param $value
     *
     * @return mixed
     */
    public function renderCell($column, $value)
    {
        if (isset($this->_decorators[$column])) {
            foreach ($this->_decorators[$column] as $callback) {
                $value = call_user_func($callback, $value);
            }
        }

        // If the option array has a value for this $column/$value combo, switch to that value
        if (isset($this->_options[$column]) && isset($this->_options[$column][$value])) {
            $value = $this->_options[$column][$value];
        }

        if (is_array($value)) {
            $value = '<pre>' . var_export($value, true) . '</pre>';
        }
        return $value;
    }

} 