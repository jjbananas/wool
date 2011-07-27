<?php

// TOKEN types
define('SQL_KEYWORD', 1);
define('SQL_IDENTIFIER', 2);
define('SQL_OPERATOR', 3);
define('SQL_STRING', 4);
define('SQL_BRACKETS', 5);
define('SQL_FUNCTIONCALL', 6);
define('SQL_NUMBER', 7);
define('SQL_PLACEHOLDER', 8);

class SqlLexer {	
	public $token;
	public $type;
	
	private $pos = 0;
	private $sql = '';
	private $ch = '';
	private $length = 0;
	
	private $linePos = 0;
	private $line = 1;
	
	private $lastPos = 0;
	private $lastTokenPos = 0;

	
	static $operators = array('.', ',', '!', '!=', '=', '<', '>', '<=', '>=', '*', '(', ')', '/', '-', 'like', 'rlike', 'not');
	static $keywords = array(
		'and', 'as', 'asc', 'between', 'by', 'count', 'desc', 'from', 'inner',
		'group', 'having', 'in', 'is', 'join', 'left', 'limit', 'max', 'null',
		'offset', 'on', 'or', 'order', 'right', 'select', 'where'
	);
	
	public function __construct($sql) {
		$this->sql = $sql;
		$this->length = strlen($sql);
		
		$this->getChar();
		$this->skipSpace();
	}
	
	public function nextToken() {
		$this->lastTokenPos = $this->pos-1;
		if ($this->ch === '') {
			$this->token = null;
			return false;
		}
		// Parameterised query placholder.
		else if ($this->ch === '?' || $this->ch === ':') {
			$this->getPlaceholder();
		}
		// Numbers
		else if (ctype_digit($this->ch)) {
			$this->getNum();
		}
		// Some kind of name, the function will determine what.
		else if (ctype_alpha($this->ch)) {
			$this->getName();
		}
		// Operators
		else if ($this->isOp($this->ch)) {
			$this->getOp();
		}
		// Quoted strings
		else if ($this->ch === '\'' || $this->ch === '"') {
			$this->getString();
		}
		// Backtick identifiers
		else if ($this->ch === '`') {
			$this->getIdentifier();
		}
		
		else {
			throw new Exception("Unrecognised character '{$this->ch}'");
		}
		
		$this->skipSpace();
		return true;
	}
	
	public function lineNum() {
		return $this->line;
	}
	
	public function linePos() {
		return $this->linePos-1;
	}
	
	public function getCopy($backup=false) {
		if ($this->token == null) {
			$backup = false;
		}
		$pos = $backup ? $this->lastTokenPos : $this->pos;
		$sql = substr($this->sql, $this->lastPos, $pos-$this->lastPos);
		$this->lastPos = $pos;
		return $sql;
	}
	
	private function getName() {
		$name = '';
		while (ctype_alnum($this->ch) || $this->ch == '_') {
			$name .= $this->ch;
			$this->getChar();
		}
		
		$lowName = strtolower($name);
		
		// Function calls must have the brackets immediately after the name.
		if ($this->ch === '(') {
			$this->skipTo(')', '(');
			$this->getChar();
			$name = $lowName . '(...)';
			$this->type = SQL_FUNCTIONCALL;
		} else {
			if (in_array($lowName, self::$keywords)) {
				$name = $lowName;
				$this->type = SQL_KEYWORD;
			} else if (in_array($lowName, self::$operators)) {
				$name = $lowName;
				$this->type = SQL_OPERATOR;
			} else {
				$this->type = SQL_IDENTIFIER;
			}
		}
		
		$this->token = $name;
	}
	
	// Not actually part of SQL, but we need to handle them because we parse the
	// pre-transformed query.
	private function getPlaceholder() {
		$name = $this->ch;
		$this->getChar();
		while (ctype_alnum($this->ch) || $this->ch == '_') {
			$name .= $this->ch;
			$this->getChar();
		}
		
		$this->type = SQL_PLACEHOLDER;
		$this->token = $name;
	}
	
	private function getNum() {
		$num = '';
		while (ctype_digit($this->ch) || $this->ch === '.' || $this->ch === '+'
			|| $this->ch === '-' || $this->ch === 'e') 
		{
			$num .= $this->ch;
			$this->getChar();
		}
		
		$this->type = SQL_NUMBER;
		$this->token = $num;
	}
	
	private function getString() {
		$q = $prev = $this->ch;
		$string = '';
		$this->getChar();
		while ($this->ch !== '' && $this->ch !== $q) {
			$string .= $this->ch;
			$prev = $this->ch;
			$this->getChar();
		}
		
		$this->getChar();
		$this->type = SQL_STRING;
		$this->token = $string;
	}
	
	private function getIdentifier() {
		$q = $this->ch;
		$identifier = '';
		$this->getChar();
		while ($this->ch !== '' && $this->ch !== $q) {
			$identifier .= $this->ch;
			$this->getChar();
		}
		
		$this->type = SQL_IDENTIFIER;
		$this->token = $identifier;
	}
	
	private function isOp() {
		if (in_array($this->ch, self::$operators)) {
			return true;
		}
		
		return false;
	}
	
	private function getOp() {
		$op = '';
		
		while ($this->ch && in_array($op . $this->ch, self::$operators)) {
			$op .= $this->ch;
			$this->getChar();
		}
		$this->type = SQL_OPERATOR;
		$this->token = $op;
	}
	
	private function skipSpace() {
		while (in_array($this->ch, array(" ", "\t", "\n", "\r", "\0"))) {
			if ($this->ch == "\n") {
				$this->linePos = 0;
				$this->line++;
			}
			$this->getChar();
		}
	}
	
	private function skipTo($ch, $inCh) {
		$inner = 0;
		while ($this->ch !== '') {
			if ($this->ch === $ch) {
				$inner--;
			}
			
			if ($this->ch === $inCh) {
				$inner++;
			}
			
			if ($inner <= 0) {
				break;
			}
			
			if ($this->ch == "\n") {
				$this->linePos = 0;
				$this->line++;
			}
			$this->getChar();
		}
	}
	
	private function getChar() {
		if ($this->pos > $this->length-1) {
			$this->ch = '';
		} else {
			$this->ch = $this->sql[$this->pos];
			$this->pos++;
			$this->linePos++;
		}
	}
}

/*
	An SQL parser that implments enough of the select syntax to determine all
	selected columns and their source tables.
*/
class SqlParser {
	public $selects = array();
	public $sourceTables = array();
	
	public $sqlParts = array();
	public $paramParts = array();
	
	private $lexer;
	private $parameterCount = 0;
	
	public function __construct($sql) {
		$this->lexer = new SqlLexer($sql);
	}
	
	public function parse() {
		$this->nextToken();
		$this->select();
	}
	
	public function nextToken() {
		$this->lexer->nextToken();
		$this->token = $this->lexer->token;
		$this->type = $this->lexer->type;
	}
	
	private function copyTo($name, $backup=true) {
		$this->sqlParts[$name] = $this->lexer->getCopy($backup);
		$this->paramParts[$name] = $this->parameterCount;
		$this->parameterCount = 0;
	}
	
	private function accept($type, $sym=null) {
		if ($this->type === $type) {
			if ($sym && $this->token !== $sym) {
				return false;
			}
			$this->nextToken();
			return true;
		}
		return false;
	}
	
	private function expect($type, $sym=null) {
		if ($this->type == $type) {
			if ($sym && $this->token !== $sym) {
				$this->error("Unexpected symbol: expected '{$sym}' found '{$this->token}'");
			}
			return true;
		}
		$this->error("Unexpected symbol: expected '{$sym}' found '{$this->token}'");
	}
	
	private function error($str) {
		throw new Exception(sprintf("%s at line %d:[%d]", $str, $this->lexer->lineNum(), $this->lexer->linePos()));
	}
	
	private function select() {
		$this->expect(SQL_KEYWORD, 'select');
		$this->nextToken();
		
		if ($this->accept(SQL_KEYWORD, 'all')
			|| $this->accept(SQL_KEYWORD, 'distinct')
			|| $this->accept(SQL_KEYWORD, 'distinctrow'))
		{
			// We don't care, but these are acceptable here.
		}
		
		$this->selectExpr();
		$this->copyTo("select");
		$this->from();
		$this->copyTo("from");
		
		if ($this->accept(SQL_KEYWORD, 'where')) {
			$this->whereCondition();
		}
		$this->copyTo("where");
		
		if ($this->accept(SQL_KEYWORD, 'group')) {
			$this->expect(SQL_KEYWORD, 'by');
			$this->nextToken();
			$this->groupBy();
		}
		$this->copyTo("group by");
		
		if ($this->accept(SQL_KEYWORD, 'having')) {
			$this->whereCondition();
		}
		$this->copyTo("having");
		
		if ($this->accept(SQL_KEYWORD, 'order')) {
			$this->expect(SQL_KEYWORD, 'by');
			$this->nextToken();
			$this->orderBy();
		}
		$this->copyTo("order");
		
		if ($this->accept(SQL_KEYWORD, 'limit')) {
			$this->limit();
		}
		$this->copyTo("limit", false);
	}
	
	private function selectExpr() {
		if ($this->accept(SQL_OPERATOR, '*')) {
			$this->selects[] = array('source'=>'*', 'table'=>null, 'alias'=>null);
		}
		else {
			$this->selectSublist();
		}
	}
	
	// TODO: This function could be improved. It should skip a large portion of
	// un-needed / un-recognised terms but not everything that might appear here.
	private function selectSublist() {
		$source = $this->token;
		$table = null;
		$alias = false;
		
		if ($this->accept(SQL_IDENTIFIER)) {
			if ($this->accept(SQL_OPERATOR, '.')) {
				if ($this->accept(SQL_OPERATOR, '*')) {
					$table = $source;
					$source = '*';
				}
				else if ($this->expect(SQL_IDENTIFIER)) {
					$table = $source;
					$source = $this->token;
					$this->nextToken();
				}
			}
			
			if ($this->type === SQL_OPERATOR && (
				$this->token === '*' || $this->token === '/' || 
				$this->token === '+' || $this->token === '-'
			)) {
				$this->skipSelectSublist();
				return;
			}
			else if ($this->accept(SQL_OPERATOR, '(')) {
				$this->skipBracketedExpression();			
			}
			$alias = $this->selectAlias();
		}
		else if ($this->accept(SQL_FUNCTIONCALL)) {
			$this->skipSelectSublist();
			$source = null;
		}
		else if ($this->accept(SQL_NUMBER) || $this->accept(SQL_STRING)) {
			$this->skipSelectSublist();
		}
		else if ($this->accept(SQL_OPERATOR, '(')) {
			$this->skipBracketedExpression();
			$this->selectAlias();
		}
		else {
			throw new Exception("Missing select expression: '{$this->token}'");
		}
		
		if ($source) {
			$this->selects[] = array('source'=>$source, 'table'=>$table, 'alias'=>$alias);
		}
		
		if ($this->accept(SQL_OPERATOR, ',')) {
			$this->selectSublist();
		}
	}
	
	// We can currently skip entire sublists because we can't write back if a
	// select doesn't come from a single source anyway.
	private function skipSelectSublist() {
		while ($this->token && (
			($this->type !== SQL_OPERATOR || $this->token !== ',')
			&& ($this->type !== SQL_KEYWORD || $this->token !== 'from')
		))	{
			$this->nextToken();
		}

		if ($this->accept(SQL_OPERATOR, ',')) {
			$this->selectSublist();
		}
	}
	
	private function selectAlias() {
		if ($this->accept(SQL_KEYWORD, 'as') && $this->expect(SQL_IDENTIFIER)) {
			$alias = $this->token;
			$this->nextToken();
			return $alias;
		}
		else if ($this->type === SQL_IDENTIFIER) {
			$alias = $this->token;
			$this->nextToken();
			return $alias;
		}
		return false;
	}
	
	private function from() {
		$this->expect(SQL_KEYWORD, 'from');
		$this->nextToken();
		
		$this->tableFactor();
		
		$this->join();
	}
	
	private function tableFactor() {
		if ($this->accept(SQL_OPERATOR, '(')) {
			if (!$this->subquery()) {
				$this->tableFactor();
				$this->expect(SQL_OPERATOR, ')');
				$this->nextToken();
			} else {
				$this->tableAlias();
			}
		}
		
		else if ($this->type === SQL_IDENTIFIER) {
			$source = $this->token;
			$this->nextToken();
			$alias = $this->tableAlias();
			$this->sourceTables[] = array('source'=>$source, 'alias'=>$alias);
		}
		
		else {
			$this->error("Missing table");
		}
	}
	
	private function subquery() {
		if ($this->type === SQL_KEYWORD && $this->token === 'select') {
			$this->skipSubquery();
			return true;
		}
		return false;
	}
	
	private function skipSubquery() {
		// We are never going to use the sub-queries, so I won't parse them.
		$this->skipBracketedExpression();
	}
	
	private function skipBracketedExpression() {
		$depth = 1;
		
		while ($depth && $this->token) {
			if ($this->type === SQL_OPERATOR && $this->token === ')') {
				$depth--;
			}
			else if ($this->type == SQL_PLACEHOLDER) {
				$this->parameterCount++;
			}
			else if ($this->token === '(') {
				$depth++;
			}
			$this->nextToken();
		}
		
		$this->nextToken();
	}
	
	private function tableAlias() {
		if ($this->accept(SQL_KEYWORD, 'as') && $this->expect(SQL_IDENTIFIER)) {
			$alias = $this->token;
			$this->nextToken();
			return $alias;
		}
		else if ($this->type === SQL_IDENTIFIER) {
			$alias = $this->token;
			$this->nextToken();
			return $alias;
		}
		
		return false;
	}
	
	private function join() {
		if ($this->accept(SQL_KEYWORD, 'left')) {
		}
		else if ($this->accept(SQL_KEYWORD, 'right')) {
		}
		else if ($this->accept(SQL_KEYWORD, 'inner')) {
		}
		
		if ($this->type !== SQL_KEYWORD || $this->token !== 'join') {
			return;
		}
		$this->nextToken();
		
		$this->tableFactor();
		
		if ($this->accept(SQL_KEYWORD, 'on')) {
			$this->joinCondition();
		}
		
		$this->join();
	}
	
	private function joinCondition() {
		if ($this->accept(SQL_OPERATOR, '(')) {
			if (!$this->subquery()) {
				$this->joinCondition();
				$this->expect(SQL_OPERATOR, ')');
				$this->nextToken();
			}
		}
		else {
			$this->conditionalExpr();
			$this->predicate();
		}
		
		if ($this->accept(SQL_KEYWORD, 'and')) {
			$this->joinCondition();
		}
		else if ($this->accept(SQL_KEYWORD, 'or')) {
			$this->joinCondition();
		}
	}
	
	private function predicate() {
		if ($this->accept(SQL_OPERATOR, 'not')) {
			$this->predicate();
		}
		else if ($this->token !== ')' && $this->token !== '(' && $this->accept(SQL_OPERATOR)) {
			$this->conditionalExpr();
		}
		else if ($this->accept(SQL_KEYWORD, 'is')) {
			$this->accept(SQL_OPERATOR, 'not');
			$this->expect(SQL_KEYWORD, 'null');
			$this->nextToken();
		}
		else if ($this->accept(SQL_KEYWORD, 'between')) {
			$this->conditionalExpr();
			$this->expect(SQL_KEYWORD, 'and');
			$this->nextToken();
			$this->conditionalExpr();
		}
		else if ($this->accept(SQL_KEYWORD, 'in')) {
			$this->conditionalExpr();
		}
	}
	
	private function inOperator() {
		$this->conditionalExpr();
		if ($this->accept(SQL_OPERATOR, ',')) {
			$this->inOperator();
		}
	}
	
	private function identifier() {
		$this->expect(SQL_IDENTIFIER);
		$this->nextToken();
		
		if ($this->accept(SQL_OPERATOR, '.')) {
			$this->expect(SQL_IDENTIFIER);
			$this->nextToken();
		}
	}
	
	private function whereCondition() {
		$this->joinCondition();
	}
	
	private function conditionalExpr() {
		if ($this->accept(SQL_STRING)) {
		}
		else if ($this->type === SQL_OPERATOR && $this->token === '-') {
			$this->nextToken();
			$this->expect(SQL_NUMBER);
			$this->nextToken();
		}
		else if ($this->accept(SQL_NUMBER)) {
		}
		else if ($this->accept(SQL_PLACEHOLDER)) {
			$this->parameterCount++;
		}
		else if ($this->accept(SQL_FUNCTIONCALL)) {
		}
		else if ($this->type === SQL_IDENTIFIER) {
			$this->identifier();
		}
		else if ($this->type === SQL_OPERATOR && ($this->token === '!' || $this->token === 'not')) {
			$this->nextToken();
			$this->conditionalExpr();
		}
		else if ($this->type === SQL_OPERATOR && $this->token === '(') {
			$this->nextToken();
			$this->skipBracketedExpression();
		}
		else {
			$this->error("Missing conditional expression, found: '{$this->token}'");
		}
	}
	
	private function groupBy() {
		return $this->orderBy();
	}
	
	private function orderBy() {
		if ($this->accept(SQL_NUMBER)) {
		}
		else if ($this->type === SQL_IDENTIFIER) {
			$this->identifier();
		}
		
		if ($this->accept(SQL_KEYWORD, 'asc')) {
		}
		else if ($this->accept(SQL_KEYWORD, 'desc')) {
		}
		
		if ($this->accept(SQL_OPERATOR, ',')) {
			$this->orderBy();
		}
	}
	
	private function limit() {
		$this->expect(SQL_NUMBER);
		$this->nextToken();
		
		if ($this->accept(SQL_OPERATOR, ',')) {
			$this->expect(SQL_NUMBER);
			$this->nextToken();
		}
		else if ($this->Accept(SQL_KEYWORD, 'offset')) {
			$this->expect(SQL_NUMBER);
			$this->nextToken();
		}
	}
}
