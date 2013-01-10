<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** Configurable Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */

// evaluate postfix notation
// modified to perform bitwise-like operations in arrays
// * => & => array_intersect
// + => | => array_merge
// - => ^ => array_diff

 class EvalWise extends EvalMath {
		
	var $data = array();
	var $index = 0;
	
	function set_data($data){
		$this->data = $data;
		$this->index = count($this->data);
	}
	
    function pfx($tokens, $vars = array()) {
        
        if ($tokens == false) return false;
    
        $stack = new EvalMathStack;
        
        foreach ($tokens as $token) { // nice and easy

            // if the token is a function, pop arguments off the stack, hand them to the function, and push the result back on
            if (is_array($token)) { // it's a function!
                $fnn = $token['fnn'];
                $count = $token['argcount'];
                if (in_array($fnn, $this->fb)) { // built-in function:
                    if (is_null($op1 = $stack->pop())) return $this->trigger("internal error");
                    $fnn = preg_replace("/^arc/", "a", $fnn); // for the 'arc' trig synonyms
                    if ($fnn == 'ln') $fnn = 'log';
                    eval('$stack->push(' . $fnn . '($op1));'); // perfectly safe eval()
                } elseif (array_key_exists($fnn, $this->fc)) { // calc emulation function
                    // get args
                    $args = array();
                    for ($i = $count-1; $i >= 0; $i--) {
                        if (is_null($args[] = $stack->pop())) return $this->trigger("internal error");
                    }
                    $res = call_user_func(array('EvalMathCalcEmul', $fnn), $args);
                    if ($res === FALSE) {
                        return $this->trigger("internal error");
                    }
                    $stack->push($res);
                } elseif (array_key_exists($fnn, $this->f)) { // user function
                    // get args
                    $args = array();
                    for ($i = count($this->f[$fnn]['args'])-1; $i >= 0; $i--) {
                        if (is_null($args[$this->f[$fnn]['args'][$i]] = $stack->pop())) return $this->trigger("internal error");
                    }
                    $stack->push($this->pfx($this->f[$fnn]['func'], $args)); // yay... recursion!!!!
                }
            // if the token is a binary operator, pop two values off the stack, do the operation, and push the result back on
            } elseif (in_array($token, array('+', '-', '*', '/', '^'), true)) {
                if (is_null($op2 = $stack->pop())) return $this->trigger("internal error");
                if (is_null($op1 = $stack->pop())) return $this->trigger("internal error");
                
				switch ($token) {
					
                    case '+':        
						$this->index += 1;
						$stack->push($this->index); 
						$this->data[$this->index] = array_merge($this->data[$op1],$this->data[$op2]);
						break;
                    case '-':
                        $this->index += 1;
						$stack->push($this->index); 
						$this->data[$this->index] = array_diff($this->data[$op1],$this->data[$op2]);
						break;
                    case '*':
                        $this->index += 1;
						$stack->push($this->index); 
						$this->data[$this->index] = array_intersect($this->data[$op1],$this->data[$op2]);
						break;                    
                }
				
            // if the token is a unary operator, pop one value off the stack, do the operation, and push it back on
            } elseif ($token == "_") {
                $stack->push(-1*$stack->pop());
            // if the token is a number or variable, push it on the stack
            } else {
                if (is_numeric($token)) {
                    $stack->push($token);
                } elseif (array_key_exists($token, $this->v)) {
                    $stack->push($this->v[$token]);
                } elseif (array_key_exists($token, $vars)) {
                    $stack->push($vars[$token]);
                } else {
                    return $this->trigger("undefined variable '$token'");
                }
            }
        }
        // when we're out of tokens, the stack should have a single element, the final result
        if ($stack->count != 1) return $this->trigger("internal error");
        $last = $stack->pop();
		if(isset($this->data[$last]))
			return $this->data[$last];
		else
			return false;
    }
		
 }

