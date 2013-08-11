<?php 

/**
 * Improves Dwoo to support a nested tag syntax like e.g. "{link url={route {config "route_name"}}}".
 * 
 * This is done by converting it into the valid Dwooish "{link url=route(config("route_name"))}" syntax.
 *
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from the use of this software.
 *
 * @author     Thomas Bachem <mail@thomasbachem.com>
 * @copyright  Copyright (c) 2013, Thomas Bachem
 * @license    http://dwoo.org/LICENSE   Modified BSD License
 * @link       http://dwoo.org/
 * @version    1.0.0
 * @date       2013-08-11
 * @package    Dwoo
 */
class Dwoo_Processor_nested_tags extends Dwoo_Processor {
	
	protected $input;
	
	
	/**
	 * @param string $input
	 * @return string
	 */
	public function process($input) {
		$this->input = $input;
		$this->replaceNestedTags();
		return $this->input;
	}
	
	/**
	 * Calls itself recursively and manipulates $this->input.
	 * 
	 * @param string $currentTag
	 * @param int $currentDepth
	 * @param int $currentOffset
	 */
	protected function replaceNestedTags($currentTag = null, $currentDepth = 0, $currentOffset = 0) {
		list($lDelim, $rDelim) = $this->compiler->getDelimiters();
		$lDelim = preg_quote($lDelim, '/');
		$rDelim = preg_quote($rDelim, '/');
		
		$allowLooseOpenings = $this->compiler->getLooseOpeningHandling();
		
		// Apply recursive RegEx pattern, see http://php.net/manual/en/regexp.reference.recursive.php
		preg_match_all('/' . $lDelim . ($allowLooseOpenings ? '\s*' : '(?!\s)' ) . '((?>[^' . $lDelim . $rDelim . ']+|(?R))*)' . $rDelim . '/', $currentTag ?: $this->input, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		foreach($matches as $match) {
			// Current offset is relative to the parent's offset,
			// so we add remember the parent's offset and add it here
			$matchPos = $currentOffset + $match[1][1];
			// Recurse
			$this->replaceNestedTags($match[1][0], $currentDepth + 1, $matchPos);
			
			// Convert syntax for nested tags
			$original = substr($this->input, $matchPos - 1, strlen($match[1][0]) + 2);
			if($currentDepth > 0 && preg_match('/^' . $lDelim . '([A-Za-z0-9_]+)(\b.*)' . $rDelim . '$/', $original, $tagMatch)) {
				$before      = substr($this->input, 0, $matchPos - 1);
				$after       = substr($this->input, $matchPos + 1 + strlen($match[1][0]));
				// We keep the string length equal to make things easier (e.g. "{config "route_name"}"
				// will become "config( "route_name")". Dwoo ignores the unnecessary whitespace.
				$replacement = $tagMatch[1] . '(' . $tagMatch[2] . ')';
				$this->input = $before . $replacement . $after;
			}
		}
	}
	
}