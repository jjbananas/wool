<?php
/**
 * A very simple BBCode Parser Class this is only for displaying not for printing. And the features available are limited. Storage and HTML Sanitisation will be performed on save
 */

define('MAX_FONT_SIZE_ALLOWED',250);

class BBCodeParser{
	/**
	 * Replace line endings with bbcode paragraphs. It is not the most elegent of functions
	 *
	 * @param unknown_type $string
	 */
	 function BBCreateParagraphs($bbString){
	 	//Append the front open paragraphs
	 	$bbString = "[p]".$bbString;
	 	//Remove all the ones in the middle, ridding ourselves of /r/n first then /r then /n
	 	$bbString = str_replace("\r\n","[/p][p]",$bbString);
	 	$bbString = str_replace("\r","[/p][p]",$bbString);
	 	$bbString = str_replace("\n","[/p][p]",$bbString);
	 	//Append the close tag
	 	$bbString .= "[/p]";
	 	return $bbString;
	 }
	 
	 
	 function BBParse($bbCodedString,$allowImages = false,$allowLinks=false) {
	 	//Deal with List Items - RegEx unescaped = [*](.+?)([p]|[/p]|[*]|[/list])
	 	while(preg_match_all('/\[\*\](.+?)(\[p\]|\[\/p\]|\[\*\]|\[\/list\])/',$bbCodedString,$listMatches)) foreach ($listMatches[0] as $key => $match){
	 		list($itemText, $endTag) = array($listMatches[1][$key],$listMatches[2][$key]);
	 		$replacement = sprintf("<li>%s</li>%s",$itemText,$endTag);
	 		$bbCodedString = str_replace($match,$replacement,$bbCodedString);
	 	}
	 	//Now deal with the rest
        while (preg_match_all('/\[(.+?)=?(.*?)\](.*?)\[\/\1\]/', $bbCodedString, $matches)) foreach ($matches[0] as $key => $match) {
            list($tag, $param, $innertext) = array($matches[1][$key], $matches[2][$key], $matches[3][$key]);
			$replacement = '';
            switch ($tag) {
                case 'b': $replacement = sprintf("<strong>%s</strong>",$innertext); break;
                case 'i': $replacement = sprintf("<em>%s</em>",$innertext); break;
                case 'size':
                	$replacement = "";
                	if(!empty($param) && is_numeric($param)){
                		if($param > MAX_FONT_SIZE_ALLOWED){$param = MAX_FONT_SIZE_ALLOWED;}
						$replacement = sprintf("<span style=\"font-size: %d%%;\">%s</span>",$param,$innertext); break;
                	}
                case 'u': $replacement = sprintf("<span style=\"text-decoration: underline;\">%s</span>",$innertext); break;
                case 'quote':
                	$replacement = '<div class="quoted-message stdpad">';
                	if(!empty($param)){
                		$replacement .= sprintf('Originally posted by <span class="username">%s</span>:<br />',$param);
                	}
                	$replacement .= sprintf('<span class="quote-text">%s</span>',$innertext);
                	$replacement .='</div>';
                	break;
                case 'code': $replacement = sprintf("<pre>%s</pre>",$innertext);break;
                case 'p': $replacement = sprintf("<p>%s</p>",$innertext);break;
                case 'list':
                	if(empty($param) === false && is_numeric($param)){
                		$replacement = sprintf("<ol start=\"%d\">%s</ol>",$param,$innertext);
                	}else{
                		$replacement = sprintf("<ul>%s</ul>",$innertext);
                	}
                	break;
                case 'img':
                	if($allowImages){
                		$replacement = sprintf('<img src="%s" />',$innertext);
                	}
                	break;
                case 'url':
                	if($allowLinks){
                		$replacement = sprintf('<a href="%s">%s</a>',$param,$innertext);
                	}
                	break;
            }
            $bbCodedString = str_replace($match, $replacement, $bbCodedString);
        }
        //Replace unnessesary paragraphs with linebreaks
        $bbCodedString = preg_replace('/<\/p>[\s]*<p>/','<br />',$bbCodedString);
        //Replace Linebreaks in lits
        while(preg_match_all('/(<ol>|<ul>|<\/li>)[\s]*<br \/>[\s]*(<li>|<\/ol>|<\/ul>)/',$bbCodedString,$breakMatches)) foreach ($breakMatches[0] as $key => $match){
        	list($openTag,$closeTag) = array($breakMatches[1][$key],$breakMatches[2][$key]);
        	$replacement = $openTag . $closeTag;
        	$bbCodedString = str_replace($match,$replacement,$bbCodedString);
        }
        return $bbCodedString;
    } 
	
}
?>