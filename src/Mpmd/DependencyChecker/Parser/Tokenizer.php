<?php

namespace Mpmd\DependencyChecker\Parser;

/**
 * Tokenizer
 *
 * @package Mpmd\DependencyChecker
 *
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class Tokenizer extends AbstractParser
{

    protected $fileExtensions = array('php', 'phtml');

    /**
     * @var array
     */
    protected $tokens;

    /**
     * Run tokenizer
     *
     * @param string filename
     * @throws \Exception
     */
    public function parse($source)
    {
        if (count($this->handlers) == 0) {
            throw new \Exception('No handlers found');
        }

        $this->tokens = token_get_all($source);

        foreach ($this->tokens as $i => $token) {
            foreach ($this->handlers as $handler) { /* @var $handler Tokenizer\Handler\AbstractHandler */
                if ($handler->listenTo($this->getType($i))) {
                    try {
                        $handler->handle($i);
                    } catch (\Exception $e) {
                        for ($k=$i-3; $k<$i+10; $k++) {
                            if ($k == $i) {
                                echo '==> ';
                            }
                            echo $this->getType($k, true) . ': ' . $this->getValue($k) . "\n";
                        }
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * Get token for a given position
     *
     * @param $position
     * @return mixed
     */
    public function getToken($position)
    {
        return $this->tokens[$position];
    }

    /**
     * Assert token
     *
     * @param $position
     * @param array|string|int $type
     * @throws \Exception
     */
    public function assertToken($position, $type)
    {
        if (!$this->is($position, $type)) {
            $expected = token_name($type);
            $actual = $this->getType($position, true);
            throw new \Exception(sprintf('Expected token "%s", got token "%s"', $expected, $actual));
        }
    }

    /**
     * Get type (no matter if it's simple or an array)
     *
     * @param $position
     * @param bool $resolveConstants
     * @return string
     */
    public function getType($position, $resolveConstants=false)
    {
        if (!isset($this->tokens[$position])) {
            return false;
        }
        $token = $this->tokens[$position];
        if (is_string($token)) {
            $type = $token;
        } elseif (is_array($token)) {
            $type = $token[0];
            if ($resolveConstants) {
                $type = token_name($type);
            }
        } else {
            throw new \InvalidArgumentException('Invalid type');
        }
        return $type;
    }

    /**
     * Get value (no matter if it's simple or an array)
     *
     * @param $position
     * @return string
     */
    public function getValue($position)
    {
        if (!isset($this->tokens[$position])) {
            return false;
        }
        $token = $this->tokens[$position];
        if (is_string($token)) {
            $value = $token;
        } elseif (is_array($token)) {
            $value = $token[1];
        } else {
            throw new \InvalidArgumentException('Invalid type');
        }
        return $value;
    }

    /**
     * Check if a token at a position if of a specific type
     *
     * @param $position
     * @param array|string|int $type
     * @return bool
     * @throws \Exception
     */
    public function is($position, $type)
    {
        if (!isset($this->tokens[$position])) {
            return false;
        }
        if (!is_array($type)) {
            $type = array($type);
        }
        return in_array($this->getType($position), $type);
    }

    /**
     * Skip tokens and return new position
     *
     * @param $i
     * @param array|string|int $skip
     * @return int
     */
    public function skip($i, $skip)
    {
        while ($this->is($i, $skip)) {
            $i++;
        }
        return $i;
    }

    /**
     * Skip tokens until one of the given tokens is found and return new position
     *
     * @param $i
     * @param array|string|int $skip
     * @return int
     */
    public function skipUntil($i, $until)
    {
        while (!$this->is($i, $until)) {
            $i++;
        }
        return $i;
    }

}