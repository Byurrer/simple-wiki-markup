<?php
/**
 * простая wiki разметка
 * PHP Version 7.1
 * 
 * @author Buturlin Vitaliy (Byurrer), email: byurrer@mail.ru
 * @copyright 2019 Buturlin Vitaliy
 * @license MIT https://opensource.org/licenses/mit-license.php
 */


class swm
{
	/*! массив данных списков
   [порядковый номер] => массив("символ для идентификации списка", "символ для регулярных вырежаений", "открывающий тег", "закрывающий тег")
  */
  protected static $m_aMarkers4Lists = [
  	0 => ["*", "\*", "<ul>", "</ul>"],
  	1 => ["#", "\#", "<ol>", "</ol>"],
	];

	//************************************************************************

	//! выравнивание параграфов
	protected static $m_sParagraphAlign = "justify";

	//************************************************************************

	//! класс для div блока оглаления
	protected static $m_sListHeader = "list_headers";
	
	//########################################################################

	//! генерация разметки в sString
	public static function markup($sString)
	{
		$sString = str_replace("\r\n", "\n", $sString);
		$sString = self::code($sString);
		$sString = self::header($sString);
		$sString = self::lists($sString);
		$sString = self::link($sString);
		$sString = self::paragraph($sString);
		$sString = self::bb($sString);
		$sString = self::special($sString);
		$sString = str_replace("\с", "\n", $sString);
		return $sString;
	}

	//########################################################################
	
	//! замена текста ссылок на теги
	public static function link($sString)
	{
	  $s = '/(https?\:\/\/(?:.[^\s]*))/ims';
		$d = '<a href="$1" target="_blank">$1</a>';
		$sString = preg_replace($s, $d, $sString);

		return $sString;
	}

	//************************************************************************

	//! замена [code lang="язык"]код[/code] на <pre class='brush: "язык"'>код</pre>
	public static function code($sString)
	{
		$sFullEndBB = "[/code]";
		$iLastPos = 0;
		
		while($iLastPos < strlen($sString) && preg_match("/\[code\s*lang=\"(.+?)\"\]/is", $sString, $aMatches, PREG_OFFSET_CAPTURE))
		{
			//preg_match("/\[code\s*lang=\"(.+?)\"\]/i", $sString, $aMatches, PREG_OFFSET_CAPTURE);
			$sFullStartBB = "[".$aMatches[0][0]."]";
			$sClass = $aMatches[1][0];
			$iLastPos = $aMatches[0][1];
			//header('Content-Type:text/plain');
			/*print_r($aMatches);
			exit(substr($sString, $iLastPos));*/

			$iLastPos2 = 0;
			if(($iLastPos2 = stripos($sString, $sFullEndBB, $iLastPos)) !== false)
			{
				$sStrCode = substr($sString, $iLastPos+strlen($sFullStartBB)-2, $iLastPos2 - ($iLastPos+strlen($sFullStartBB)-2));
				$sStrCode = htmlspecialchars($sStrCode);
				$sStrCode = str_replace("\n", "\с", $sStrCode);
				//exit("###".$sStrCode."###");
				$sString = substr($sString, 0, $iLastPos) . "<pre class='brush: ".$sClass."'>" . $sStrCode . "</pre>" . substr($sString, $iLastPos2 + strlen($sFullEndBB));
				$iLastPos = $iLastPos + strlen($sStrCode);
			}

			//exit($sString);
		}
		
		return $sString;
	}

	//************************************************************************

	//! замена \n\nТЕКСТ\n на тег p, выравнвиание текста указано в m_sParagraphAlign
	public static function paragraph($sString) 
	{
		$s = '/\n\n([^\=\*\[\n](?:.|\n)+)(?=\n\n)/uU';
		$d = '<p style="text-align: '.self::$m_sParagraphAlign.';">$1</p>';
		$sString = preg_replace($s, $d, $sString);

		return $sString;
	}

	//************************************************************************

	/*! замена ==текст== на <h2>текст</h2>, === на h3 и т.д. 
		если количество заголовков Ю 2 тогда будет генерироваться блок содержания 
		(div с классом указанным в m_sListHeader, элементы содержания в списке (ul, li))
	*/
	public static function header($sString)
	{
		$aHeaders = array();
		$aMatches = array();
		while(preg_match("/(\n)(=){1,6}.+?(=){1,6}/i", $sString, $aMatches, PREG_OFFSET_CAPTURE) === 1)
		{
			$iNumH = self::getCountMarkersH(trim($aMatches[0][0]));
			$sHeader = preg_replace("/\n?(=){1,6}/i", "", $aMatches[0][0]);
			$sText = "<a name='{$sHeader}'></a><h{$iNumH}>{$sHeader}</h{$iNumH}>";
			$sString = substr_replace($sString, $sText, $aMatches[0][1], strlen($aMatches[0][0]));
			$aHeaders[] = array($iNumH, $sHeader);
		}
		
		$sList = "";
		
		if(count($aHeaders) > 2)
		{
			$sList .= "<div class='".self::$m_sListHeader."'><p>Содержание</p><ul>";
			//2 потому что первое что должно быть это h2 который начинается с ==
			$iCountMarksPrev = 2;
			
			//нумерация для каждого заголовка
			$aNumeric = array(0, 0, 0, 0, 0, 0);
			
			for($i=0, $il=count($aHeaders); $i<$il; ++$i)
			{
				//определение количества маркеров (последовательных)
				$iCountMarksCurr = $aHeaders[$i][0];
				
				// если нет маркеров значит конец списка
				if($iCountMarksCurr == 0)
					break;
				
				//увеличиваем нумерацию списка заголовков
				++$aNumeric[$iCountMarksCurr-2];
				
				//если на текущей строке больше маркеров 
				if($iCountMarksPrev < $iCountMarksCurr)
				{
					//значит открываются новые списки
					for($k=0, $kl=$iCountMarksCurr-$iCountMarksPrev; $k<$kl; ++$k)
						$sList .= "<ul>";
					
					$aNumeric[$iCountMarksCurr-2] = 1;
				}
				//если текущих маркеров меньше чем предыдущих
				else if($iCountMarksPrev > $iCountMarksCurr)
				{
					//значит закрываются предыдущие списки и обнуляется нумерация закрытых заголовков
					for($k=0, $kl=$iCountMarksPrev-$iCountMarksCurr; $k<$kl; ++$k)
					{
						$sList .= "</ul>";
						$aNumeric[$iCountMarksPrev-($k + 2)] = 0;
					}
				}
				
				//в любом случае вставляем элемент списка
				$sList .= "<li><a href='#{$aHeaders[$i][1]}'>";
				
				for($k=0, $kl=$iCountMarksCurr-1; $k<$kl; ++$k)
						$sList .= $aNumeric[$k] . ".";
					
				$sList .= " {$aHeaders[$i][1]}</a></li>";
				
				
				//echo "<pre>" . print_r($aNumeric, true) . "</pre>";
				
				$iCountMarksPrev = $iCountMarksCurr;
			}
			
			//вставляем недостающие теги закрытия списков
			for($k=0, $kl=$iCountMarksPrev-2; $k<$kl; ++$k)
				$sList .= "</ul>";
			
			$sList .= "</ul></div>";
		}
		
		return $sList . $sString;
	}

	//! возвращает количество маркеров в строке
	protected static function getCountMarkersH($sString)
	{
		$iCount = 0;
		
		for($i=0, $il=strlen($sString); $i<$il; ++$i)
		{
			if($sString[$i] == '=')
				++$iCount;
			else
				break;
		}
		
		return $iCount;
	}

	//************************************************************************

	/*! генерация списков
		каждый элемент списка должен быть отделен новой строкой и начинаться с символов указанных в m_aMarkers4Lists
		1 символ соответсвует первому уровню вложенности, 2 второму:
		* первый уровень
		** второй уровень
	*/
	public static function lists($sString)
  {
		for($i=0; $i<2; ++$i)
		{
			$aMatches = array();
			
			$sSym = self::$m_aMarkers4Lists[0][1];
			$sReg = "/(\n{$sSym}+\s*)/is";

			// поочередно ищем каждое вхождение первого элемента списка
			while(preg_match($sReg, $sString, $aMatches, PREG_OFFSET_CAPTURE))
			{
				$iStart = $aMatches[0][1] + strlen(PHP_EOL);
				$sString = self::replaceLists($sString, $iStart, 0);
			}
		}
  	
  	return $sString;
  }
  
  //! замена списка на разметку, начиная с $iPos позиции в тексте $sString
  protected static function replaceLists($sString, $iPos, $iNumSym)
  {
  	$iStart = $iPos;
  	$iFinish = $iStart;
  	
  	//exit($sString[$iFinish]);
  	$sSym = self::$m_aMarkers4Lists[$iNumSym][1];
  	$sReg = "/{$sSym}+\s+/";
  	//! поиск конечной позиции текущего списка
  	while($iFinish < strlen($sString) && $sString[$iFinish] == self::$m_aMarkers4Lists[$iNumSym][0] && preg_match($sReg, substr($sString, $iFinish)) === 1)
  	{
  		$iFinish = stripos($sString, PHP_EOL, $iFinish);
  		$iFinish += strlen(PHP_EOL);
  		/*if()
  			break;*/
  	}
  	
  	//echo $iStart . "|" . $iFinish;
  	
  	
  	//вырезка списка из текста
  	$sTextList = substr($sString, $iStart, $iFinish - $iStart);
  	//exit($sTextList);
  	
  	//деление текста на строки
  	$aNLlist = explode(PHP_EOL, $sTextList);
  	
  	//генерация разметки, в том числе и для вложенных списков
  	//{
  	$sList = self::$m_aMarkers4Lists[$iNumSym][2];
  	$iCountMarksPrev = 1;
  	
  	for($i=0, $il=count($aNLlist); $i<$il; ++$i)
  	{
  		//определение количества маркеров (последовательных)
  		$iCountMarksCurr = self::getCountMarkers($aNLlist[$i], $iNumSym);
  		
  		// если нет маркеров значит конец списка
  		if($iCountMarksCurr == 0)
  			break;
  		
  		//если на текущей строке больше маркеров 
  		if($iCountMarksPrev < $iCountMarksCurr)
  		{
  			//значит открываются новые списки
  			for($k=0, $kl=$iCountMarksCurr-$iCountMarksPrev; $k<$kl; ++$k)
  				$sList .= self::$m_aMarkers4Lists[$iNumSym][2];
  		}
  		//если текущих маркеров меньше чем предыдущих
  		else if($iCountMarksPrev > $iCountMarksCurr)
  		{
  			//значит закрываются предыдущие списки
  			for($k=0, $kl=$iCountMarksPrev-$iCountMarksCurr; $k<$kl; ++$k)
  				$sList .= self::$m_aMarkers4Lists[$iNumSym][3];
  		}
  		
  		//в любом случае вставляем элемент списка
  		$sSym = self::$m_aMarkers4Lists[$iNumSym][1];
  		$sList .= "<li>" . (preg_replace("/^{$sSym}+(\s)+/", "", $aNLlist[$i])) . "</li>";
  		
  		$iCountMarksPrev = $iCountMarksCurr;
  	}
  	
  	$sList .= self::$m_aMarkers4Lists[$iNumSym][3];
  	//}
  	
  	$sString = substr_replace($sString, $sList, $iStart, $iFinish - $iStart);
  	
  	return $sString;
  }
  
  //! возвращает количество маркеров в строке
  protected static function getCountMarkers($sString, $iNumSym)
  {
  	$iCount = 0;
  	
  	for($i=0, $il=strlen($sString); $i<$il; ++$i)
  	{
  		if($sString[$i] == self::$m_aMarkers4Lists[$iNumSym][0])
  			++$iCount;
  		else
  			break;
  	}
  	
  	return $iCount;
	}
	
	//************************************************************************

	//! замена bb кодов на теги
	public static function bb($sString) 
	{
		$aSourceCode = [
			"#\[b\](.*)\[\/b\]#isU",
			"#\[i\](.*)\[\/i\]#isU",
			"#\[u\](.*)\[\/u\]#isU",
			"#\[d\](.*)\[\/d\]#isU",
			"#\[left\](.*)\[\/left\]#isU",
			"#\[center\](.*)\[\/center\]#isU",
			"#\[right\](.*)\[\/right\]#isU",
			"#\[sub\](.*)\[\/sub\]#isU",
			"#\[sup\](.*)\[\/sup\]#isU",
			"#\[big\](.*)\[\/big\]#isU",
			"#\[small\](.*)\[\/small\]#isU",
		];

		$aHTMLcode = [
			"<b>$1</b>",
			"<i>$1</i>",
			"<u>$1</u>",
			"<del>$1</del>",
			"<p style='text-align: left;'>$1</p>",
			"<p style='text-align: center;'>$1</p>",
			"<p style='text-align: right;'>$1</p>",
			"<sub>$1</sub>",
			"<sup>$1</sup>",
			"<big>$1</big>",
			"<small>$1</small>",
		];

		return preg_replace($aSourceCode, $aHTMLcode, $sString);
	}

	//************************************************************************

	//! замена специальных кодов на теги
	public static function special($sString) 
	{
		$aSourceCode = [
			"/\(\((.*)\)\)/isU",
			"/\"\"(.*)\"\"/isU",
			"/\'\'(.*)\'\'/isU",
			"/##(.*)##/isU",
		];

		$aHTMLcode = [
			"<small>$1</small>",
			"<sup>$1</sup>",
			"<sub>$1</sub>",
			"<b>$1</b>",
		];

		return preg_replace($aSourceCode, $aHTMLcode, $sString);
	}
};
